<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 *
 * @package block_hsmail
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once( $CFG->dirroot.'/blocks/moodleblock.class.php' );

class block_hsmail extends block_base {
    public function init() {
        $this->title = get_string ( 'pluginname', 'block_hsmail' );
    }

    // ブロックの設置場所の有効範囲
    public function applicable_formats() {
        return array (
                'site-index' => true,
                'course-view' => true,
                'course-view-social' => false
        );
    }
    public function get_content() {
        global $CFG, $COURSE, $USER, $DB;

        if ( $this->content !== null ) {
            return $this->content;
        }

        $text = '';
        $this->content = new stdClass ();
        $this->content->text = $text;

        if ( $USER->id != 0 ) {
            $sql = <<< SQL
SELECT count(*) FROM {$CFG->prefix}block_hsmail
WHERE
executeflag <= ?
SQL;
            $context = context_course::instance ( $COURSE->id );
            if ( has_capability ( 'block/hsmail:addcondition', $context ) ) {
                $this->content->text = $text;
                $url = new moodle_url ( '/blocks/hsmail/add.php?id=' . $COURSE->id );
                $link = html_writer::link ( $url, get_string ( 'add', 'block_hsmail' ) );
                $this->content->text = $link . '<br />';
                $url = new moodle_url ( '/blocks/hsmail/jobsetting.php?id=' . $COURSE->id );
                $link = html_writer::link ( $url, get_string ( 'confirm', 'block_hsmail' ) );
                $this->content->text .= $link;
            } else if ( has_capability ( 'block/hsmail:viewmaillist', $context ) ) {
                $url = new moodle_url ( '/blocks/hsmail/maillist.php?id=' . $COURSE->id );
                $link = html_writer::link ( $url, get_string ( 'sentlist', 'block_hsmail' ) );
                $this->content->text = $link;
            }

            $this->content->footer = '';
        }
        return $this->content;
    }

    // 個別設定有効
    public function instance_allow_multiple() {
        return false;
    }
    public function instance_allow_config() {
        return true;
    }

    // 全体設定有効
    public function has_config() {
        return true;
    }

    // メールキュー登録および送信
    public function cron() {
        global $DB, $CFG, $USER, $COURSE;

        echo "start\n";
        require_once( $CFG->dirroot . '/blocks/hsmail/hsmail_lib.php' );
        // hsmaillib object 作成
        $objhsmaillib = new hsmail_lib ();
        mtrace ( "hsmail target user listing..." );
        // fromアドレスの取得
        $mailfrom = get_config('core', 'supportemail');

        $now = date ( 'U' );
        // 条件の取得
        $sql = <<< SQL
SELECT * FROM {$CFG->prefix}block_hsmail
WHERE executeflag = 0 AND (executedatetime <= {$now} OR instantly = 1)
ORDER BY instantly DESC, executedatetime ASC
SQL;
        $course = $DB->get_records_sql ( $sql );

        reset ( $course );
        // 作業テーブルの削除
        $DB->delete_records ( 'block_hsmail_temp' );
        $addnum = 0;
        while ( list ( $key, $value ) = each ( $course ) ) {

            // ジョブのステータス変更 実行中：2に変更
            $dataobject = new stdClass ();
            $dataobject->id = $value->id;
            $dataobject->executeflag = 2; // キューにたまった時点で編集・削除不可とする
            $DB->update_record ( 'block_hsmail', $dataobject );

            // トランザクション　ここから
            $transaction = null;
            try {
                $transaction = $DB->start_delegated_transaction ();

                // 作業テーブルの削除
                $DB->delete_records ( 'block_hsmail_temp' );
                if ( $value->course == 1 ) {
                    // サイトトップで設定された場合
                    $admins = get_admins (); // サイトアドミンを取得
                    $tmpadmins = array ();
                    foreach ($admins as $admin) {
                        $tmpadmins [] = $admin->id;
                    }
                    $admins = implode ( ',', $tmpadmins );
                    $sql = <<< SQL
INSERT INTO {$CFG->prefix}block_hsmail_temp
(
SELECT u.id AS userid, u.email, u.firstname, u.lastname FROM {$CFG->prefix}user As u
WHERE u.id != 1 AND u.id NOT IN ({$admins}) AND u.deleted=0
ORDER BY u.id
)
SQL;
                } else {
                    // 作業テーブルのへ受講者を登録(学生ロール)
                    $sql = <<< SQL
INSERT INTO {$CFG->prefix}block_hsmail_temp
(
SELECT u.id AS userid, u.email, u.firstname, u.lastname, u.alternatename, u.middlename FROM {$CFG->prefix}role_assignments AS ra
INNER JOIN {$CFG->prefix}context AS c ON ra.contextid = c.id AND contextlevel = 50 AND instanceid = ?
INNER JOIN {$CFG->prefix}user AS u ON ra.userid = u.id
WHERE ra.roleid = 5
)
SQL;
                }

                $DB->execute ( $sql, array (
                        $value->course
                ) );

                // 該当コースの条件一覧を取得
                $plan = $DB->get_records ( 'block_hsmail_plan', array (
                        'hsmail' => $value->id
                ) );

                foreach ($plan as $tmp) {
                    // コース受講者一覧の作成
                    foreach ($objhsmaillib->conditionfiles as $cf) {
                        if ( $tmp->plan != $cf ) {
                            continue;
                        }
                        require_once($CFG->dirroot . '/blocks/hsmail/conditions/' . $cf . '.php');
                        $objwork = new $cf ();
                        // 条件にあうユーザ取得SQLの生成
                        $planvalue = unserialize ( base64_decode ( $tmp->planvalue ) );
                        $tmpsql = $objwork->regist_users_sql ( $value->course, $planvalue );

                        // 条件に一致するユーザへの絞込
                        try {
                            $tmpuser = $DB->get_records_sql ( $tmpsql );

                            reset( $tmpuser );
                            $idstmp = array();
                            while (list ( $key2, $value2 ) = each ( $tmpuser) ) {
                                $idstmp[] = $value2->userid;
                            }
                            $ids = implode(',', $idstmp);

                            if ( $ids == "" ) {
                                $sql = <<< SQL
DELETE FROM {$CFG->prefix}block_hsmail_temp
SQL;
                                $DB->execute( $sql );
                                break 2;
                            } else {
                                $sql = <<< SQL
DELETE FROM {$CFG->prefix}block_hsmail_temp WHERE userid NOT IN ({$ids})
SQL;
                                $DB->execute( $sql );
                            }
                        } catch ( Exception $e ) {
                            throw new moodle_exception ( $e->getMessage () );
                        }
                        $objwork = null;
                    }
                }

                // フッターを追加
                $config = get_config ( 'block_hsmail' );
                if ( $config->footer != '' ) {
                    $value->mailbody .= "\n\r" . $config->footer;
                }

                // 条件に合ったユーザをキューに登録
                $sql = <<< SQL
INSERT INTO {$CFG->prefix}block_hsmail_queue
(hsmail, userid, timesend, title, body, mailfrom, mailto,timecreated, timemodified, instantly)
(SELECT
'{$value->id}' AS hsmail,
userid,
'{$value->executedatetime}' AS timesend,
? AS title,
? As body,
? AS mailfrom,
email AS mailto,
'{$now}' AS timecreated,
'{$now}' AS timemodified,
'{$value->instantly}'AS instantly
FROM {$CFG->prefix}block_hsmail_temp)
SQL;
                $DB->execute ( $sql, array (
                        $value->mailtitle,
                        $value->mailbody,
                        $mailfrom
                ) );
                $dataobject = null;

                // 追加するメールの件数の取得
                $addnum += $DB->count_records( 'block_hsmail_temp' );

                $transaction->allow_commit ();

            } catch ( Exception $e ) {
                // ジョブのステータス変更 実行中：0に変更
                $dataobject = new stdClass ();
                $dataobject->id = $value->id;
                $dataobject->executeflag = 1; // 失敗状態とする
                $DB->update_record ( 'block_hsmail', $dataobject );
                // Rollback
                $transaction->rollback ( $e );
            }
        }

        // メール登録ログ
        if ( $addnum != 0 ) {
            // メールがキューに登録された時のみログへ出力
            $event = \block_hsmail\event\mail_added::create(array(
                    'context' => context_course::instance($COURSE->id),
                    'userid' => $USER->id,
                    'courseid' => $COURSE->id,
                    'other' => array( 'addmail' => $addnum )
            ));
            $event->trigger();
        }
        // 作業テーブルの削除
        $DB->delete_records ( 'block_hsmail_temp' );

        mtrace ( 'hsmail sending mail ... ' );

        $transaction = null;
        try {
            require_once( 'hsmaillib_wrapper.php' );
            $mailobj = new hsmaillib_wrapper ();

            // トランザクション
            $transaction = $DB->start_delegated_transaction ();
            // 一度に送るユーザ数を取得
            $config = get_config ( 'block_hsmail' );
            $mailmax = (int)($config->mailmax); // 同時メール送信数
            $ignoremailmax = $config->ignore_mailmax; // 即時配信時に同時メール送信数を無視

            // 即時配信時に同時メール送信数を無視 ON
            if ( $ignoremailmax == 1 ) {
                // 即時配信メール
                $sql = "SELECT * FROM {$CFG->prefix}block_hsmail_queue WHERE instantly=1";
                $tagetmailsinstantly = $DB->get_records_sql ( $sql );

                // キューからメールの削除
                $sql = "DELETE FROM {$CFG->prefix}block_hsmail_queue WHERE instantly=1";
                $DB->execute ( $sql, array () );

                // 通常メール
                // 指定数分のキューの取得
                $now = (int)(date ( 'U' ));
                $sql = <<< SQL
SELECT * FROM {$CFG->prefix}block_hsmail_queue
WHERE
timesend <= ? AND instantly = 0
ORDER BY id ASC
SQL;
                $tagetmails = $DB->get_records_sql ( $sql, array ( $now ), 0, $mailmax );

                // キューからメールの削除
                reset( $tagetmails );
                while ( list ( $key3, $value3 ) = each ( $tagetmails )) {
                    $DB->delete_records( 'block_hsmail_queue', array ( 'id' => $value3->id ) );
                }

                $tagetmails = array_merge ( $tagetmailsinstantly, $tagetmails ); // 即時配信メールと通常メールマージ
                // 即時配信時に同時メール送信数を無視 OFF
            } else {
                // 指定数分のキューの取得
                $now = (int)(date ( 'U' ));
                $sql = <<< SQL
SELECT * FROM {$CFG->prefix}block_hsmail_queue
WHERE
timesend <= ? OR instantly = 1
ORDER BY instantly DESC, id ASC
SQL;
                $tagetmails = $DB->get_records_sql ( $sql, array ( $now ), 0, $mailmax );

                // キューからメールの削除
                reset( $tagetmails );
                while ( list ( $key4, $value4 ) = each ( $tagetmails )) {
                    $DB->delete_records( 'block_hsmail_queue', array ( 'id' => $value4->id ) );
                }
            }

            if ( count ( $tagetmails ) == 0 ) {
                $transaction->allow_commit ();
                return true; // 送信メールがない場合処理を終了
            }
            // 送信処理
            $sentmail = 0;
            reset ( $tagetmails );
            while ( list ( $key, $value ) = each ( $tagetmails ) ) {
                // メール生成
                $recipients = $value->mailto;
                // メール送信者表示名
                $headers ['FromName'] = '';
                $headers ['From'] = $value->mailfrom;
                $headers ['To'] = $value->mailto;
                $headers ['Subject'] = $value->title;

                // プレースフォルダ処理
                $body = $this->conv_placeholder ( $value );
                // 送信
                $mailobj->send ( $recipients, $headers, $body );
                $sentmail++;

                // 送信ログの登録
                $retlogid = $DB->get_record ( 'block_hsmail_log', array (
                        'hsmail' => $value->hsmail
                ) );
                if ( $retlogid === false ) {
                    // ログテーブルへ追加
                    $dataobject = new stdClass ();
                    $dataobject->hsmail = $value->hsmail;
                    $dataobject->timecreated = $now;
                    $dataobject->timemodified = $now;
                    $logid = $DB->insert_record ( 'block_hsmail_log', $dataobject );
                    $dataobject = null;
                } else {
                    $logid = $retlogid->id;
                }
                $dataobject = new stdClass ();
                $dataobject->hsmaillog = $logid;
                $dataobject->hsmail = $value->hsmail;
                $dataobject->userid = $value->userid;
                $dataobject->timecreated = $now;
                $dataobject->timemodified = $now;
                $DB->insert_record ( 'block_hsmail_userlog', $dataobject );
                $dataobject = null;
            }
            mtrace ( 'hsmail sent mail ' );

            // コミット
            $transaction->allow_commit ();
            mtrace ( 'hsmail processing end.' );
            // ログに記録
            $event = \block_hsmail\event\mail_sent::create(array(
                    'context' => context_course::instance($COURSE->id),
                    'userid' => $USER->id,
                    'courseid' => $COURSE->id,
                    'other' => array('sentmail' => $sentmail)
            ));
            $event->trigger();

        } catch ( Exception $e ) {
            mtrace ( $e->getMessage () );
            $transaction->rollback ( $e );
        }
        return true;
    }

    // プレースフォルダ処理
    public function conv_placeholder($value) {
        global $DB, $CFG;

        $body = $value->body;

        // ユーザ氏名
        $sql = "SELECT firstname, lastname FROM {$CFG->prefix}user WHERE id=?";
        $userinfo = $DB->get_record_sql ( $sql, array (
                $value->userid
        ) );
        $body = str_replace ( '[[user_name]]', "{$userinfo->lastname} {$userinfo->firstname}", $body );

        // コース名＋URL
        $sql = <<< SQL
SELECT T2.id, T2.fullname FROM {block_hsmail} AS T1
INNER JOIN {course} AS T2 ON T1.course=T2.id WHERE T1.id=?
SQL;
        $courseinfo = $DB->get_record_sql ( $sql, array (
                $value->hsmail
        ) );
        $url = $CFG->wwwroot . '/course/view.php?id=' . $courseinfo->id;
        $body = str_replace ( '[[course_name]]', $courseinfo->fullname . ' ' . $url, $body );

        return $body;
    }
}
