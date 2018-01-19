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

defined('MOODLE_INTERNAL') || die();
require_once( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php' );
require_once($CFG->libdir . '/formslib.php' );

class hsmail_detailform extends moodleform {
    protected $jobid = 0;
    /**
     *
     * @param number $jobid
     */
    public function __construct($jobid = 0) {
        $this->jobid = $jobid;
        parent::__construct ();
    }
    /**
     *
     */
    public function definition() {
        global $CFG, $COURSE;

        $hsmailobj = new hsmail_lib ();

        $mform = $this->_form; // Don't forget the underscore!

        // 基本情報
        global $CFG;
        require_once( $CFG->dirroot . '/blocks/hsmail/conditions/basic.php' );
        $basicplan = new basic ();
        if ($this->jobid != 0) {
            $defaultdata = $basicplan->get_planvalue ( $this->jobid );
        } else {
            $defaultdata = null;
        }
        $basicobj = new basic_form ();
        $basicobj->build_form ( $mform, $defaultdata );

        // 基本以外の詳細設定画面の表示ロジック
        foreach ($hsmailobj->conditionfiles as $tmp) {
            if ( $tmp == 'basic' ) {
                continue;
            }
            $classfilename = $tmp . '.php';
            require_once( $CFG->dirroot . '/blocks/hsmail/conditions/' . $classfilename );
            $classplan = new $tmp ();
            if ( $this->jobid != 0 ) {
                $defaultdata = $classplan->get_planvalue ( $this->jobid );
            } else {
                $defaultdata = null;
            }

            $classname = $tmp . "_form";
            $tmpobj = new $classname ();
            $tmpobj->build_form ( $mform, $defaultdata );
        }

        $this->add_action_buttons ();
    }

    /**
     *
     * @return unknown
     */
    public function get_data() {
        $data = parent::get_data ();
        if ( !is_null ( $data ) && ! isset ( $data->instantly ) ) {
            $data->instantly = 0; // 「即時配信」チェックボックスoffの場合は 0
        }
        return $data;
    }

    /**
     * 個別のチェックがある場合
     * @param unknown $data
     * @param unknown $files
     */
    public function validation($data, $files) {
        global $CFG;
        $errormsg = array ();
        $hsmailobj = new hsmail_lib ();
        // 基本以外の詳細設定画面の表示ロジック
        foreach ($hsmailobj->conditionfiles as $tmp) {
            if ( $tmp == 'basic' ) {
                continue;
            }
            $classfilename = $tmp . '.php';
            require_once($CFG->dirroot . '/blocks/hsmail/conditions/' . $classfilename );

            $classname = $tmp;
            $tmpobj = new $classname ();
            if (method_exists ( $tmpobj, 'validation' )) {
                $tmpobj->validation ( $data, $files, $errormsg );
            }
        }
        return $errormsg;
    }
}

class hsmail_lib {
    // 条件ファイルの位置
    protected $conditiondir;

    // 条件ファイルの一覧
    public $conditionfiles;

    // データ総数
    protected $totalcount;

    // ページ毎表示件数
    protected $perpage = 20; // デフォルト値：ブロックインスタンス設定値で上書きされる

    // ページ番号
    protected $page = 0;

    public function __construct() {
        global $CFG;

        $this->conditiondir = $CFG->dirroot . '/blocks/hsmail/conditions/';
        $resdir = opendir ( $this->conditiondir );
        // ファイル名一覧を取得
        while ( $filename = readdir ( $resdir ) ) {
            if ( is_dir ( $filename )) {
                continue;
            }
            $work = str_replace ( '.php', '', $filename );
            $this->conditionfiles [] = $work;
        }
    }

    /**
     * ジョブの内容変更
     * @param unknown $data
     * @param unknown $plan
     * @throws moodle_exception
     */
    public function update_job($data, $plan) {
        global $DB, $USER;
        // jobidのチェック
        if ( $data->jobid == 0 ) {
            return;
        }

            // 実行中でないかチェック
        $basicdata = $DB->get_record ( 'block_hsmail', array (
                'id' => $data->jobid
        ) );
        if ( $basicdata->executeflag == 2 ) {
            // 実行中の場合は変更できないメッセージを表示
            throw new moodle_exception ( get_string ( 'err_already', 'block_hsmail' ) );
            return;
        }

        // トランザクション　ここから
        $transaction = null;
        try {
            $transaction = $DB->start_delegated_transaction ();
            // 設定情報の変更
            // 基本情報の登録
            $now = date ( 'U' );
            if ( $data->timing == 1 ) {
                $settiming = ($data->repeatinterval == 0) ? 0 : $now;
            } else {
                $settiming = $data->datetime;
            }

            $dataobject = array (
                    'id' => $data->jobid,
                    'executedatetime' => $settiming,
                    'jobtitle' => $data->condition_title,
                    'mailtitle' => $data->mail_title,
                    'mailbody' => $data->mailbody,
                    'timemodified' => $now,
                    'modifieduser' => $USER->id,
                    'instantly' => $data->instantly
            );
            $DB->update_record ( 'block_hsmail', $dataobject );

            $id = $data->jobid;
            // 各条件の登録
            foreach ($plan as $key => $value) {
                if ( is_null ( $key )) {
                    continue;
                }
                $ret = $DB->get_record ( 'block_hsmail_plan', array (
                        'hsmail' => $id,
                        'plan' => $key
                ) );
                if ( $ret === false ) {
                    throw new moodle_exception ( get_string ( 'err_planid', 'block_hsmail' ) . "->{$id}->{$key}" );
                }
                // プラン
                $dataobject = array (
                        'id' => $ret->id,
                        'hsmail' => $id,
                        'plan' => $key,
                        'planvalue' => base64_encode ( serialize ( $value ) ),
                        'timemodified' => $now
                );
                $DB->update_record ( 'block_hsmail_plan', $dataobject );
            }

            $transaction->allow_commit ();
        } catch ( Exception $e ) {
            $transaction->rollback ( $e );
        }
        // 　トランザクション　ここまで
    }

    // ジョブの削除
    public function delete_job($jobid) {
        global $DB;
        // jobidのチェック
        if ( $jobid == 0 ) {
            return;
        }

            // 実行中でないかチェック
        $basicdata = $DB->get_record ( 'block_hsmail', array (
                'id' => $jobid
        ) );
        if ( $basicdata->executeflag == 2 ) {
            // 実行中の場合は変更できないメッセージを表示
            throw new moodle_exception ( get_string ( 'err_already', 'block_hsmail' ) );
            return;
        }
        // トランザクション　ここから
        $transaction = null;
        try {
            $transaction = $DB->start_delegated_transaction ();

            $ret = $DB->get_records ( 'block_hsmail_plan', array (
                    'hsmail' => $jobid
            ), 'id' );
            // 各条件の削除
            foreach ($ret as $tmp) {
                $DB->delete_records ( 'block_hsmail_plan', array (
                        'id' => $tmp->id
                ) );
            }

            // 送信Jobの削除
            $DB->delete_records ( 'block_hsmail', array (
                    'id' => $jobid
            ) );
            $transaction->allow_commit ();
        } catch ( Exception $e ) {
            $transaction->rollback ( $e );
        }
        // 　トランザクション　ここまで
    }
    public function insert_job($data, $plan = null) {
        global $USER, $COURSE, $DB;

        if ( ! is_array ( $plan ) ) {
            throw new moodle_exception ( "hsmail plan is not arrayl" );
        }

        foreach ($plan as $key => $value) {
            if (is_numeric ( $key )) {
                throw new moodle_exception ( "hsmail planvalue is null. You should set the planvalue." );
            }
        }
        // トランザクション　ここから
        $transaction = null;
        try {
            $transaction = $DB->start_delegated_transaction ();
            // 基本情報の登録
            $now = date ( 'U' );
            if ( $data->timing == 1 ) {
                $settiming = ($data->repeatinterval == 0) ? 0 : $now;
            } else {
                $settiming = $data->datetime;
            }

            $dataobject = array (
                    'course' => $COURSE->id,
                    'category' => $COURSE->category,
                    'executedatetime' => $settiming,
                    'repeatinterval' => $data->repeatinterval,
                    'jobtitle' => $data->condition_title,
                    'mailtitle' => $data->mail_title,
                    'mailbody' => $data->mailbody,
                    'executeflag' => 0,
                    'timecreated' => $now,
                    'createuser' => $USER->id,
                    'timemodified' => $now,
                    'modifieduser' => $USER->id,
                    'instantly' => $data->instantly
            );

            $id = $DB->insert_record ( 'block_hsmail', $dataobject, true );

            // 各条件の登録
            foreach ($plan as $key => $value) {
                if (is_null ( $key )) {
                    continue;
                }
                    // plan
                $dataobject = array (
                        'hsmail' => $id,
                        'plan' => $key,
                        'planvalue' => base64_encode ( serialize ( $value ) ),
                        'timecreated' => $now,
                        'timemodified' => $now
                );
                $DB->insert_record ( 'block_hsmail_plan', $dataobject );
            }
            $transaction->allow_commit ();
        } catch ( Exception $e ) {
            $transaction->rollback ( $e );
        }
    }

    // ページング用データをset 2014-04-18
    public function set_paging_data($reservation, $flag) {
        global $CFG, $DB, $COURSE;

        // 総数取得
        $sql = <<< SQL
SELECT COUNT(*) AS cnt
FROM {$CFG->prefix}block_hsmail AS bh
INNER JOIN {$CFG->prefix}user AS u ON bh.createuser = u.id
WHERE bh.course = ? AND bh.category = ? AND executeflag <= ? {$reservation}
SQL;
        $datacount = $DB->get_record_sql ( $sql, array (
                $COURSE->id,
                $COURSE->category,
                $flag
        ) ); // 総数取得
        $this->totalcount = $datacount->cnt;

        $context = context_course::instance ( $COURSE->id );

        // ページ毎表示件数取得
        $sql = "SELECT configdata FROM {$CFG->prefix}block_instances WHERE blockname='hsmail' AND parentcontextid=?";
        $blockconfigdata = $DB->get_record_sql ( $sql, array (
                $context->id
        ) );
        if ( ! empty ( $blockconfigdata->configdata ) ) {
            $blockconfigdata = unserialize ( base64_decode ( $blockconfigdata->configdata ) );
            if ( isset ( $blockconfigdata->perpage ) ) {
                $this->perpage = $blockconfigdata->perpage;
            }
        }

        $this->page = optional_param ( 'page', 0, PARAM_INT ); // page番号
    }

    // ページングHTML作成 2014-04-18
    public function get_paging($url) {
        global $COURSE, $OUTPUT;
        $baseurl = new moodle_url ( $url, array (
                'id' => $COURSE->id
        ) );
        $pagingbar = new paging_bar ( $this->totalcount, $this->page, $this->perpage, $baseurl );
        return $OUTPUT->render ( $pagingbar );
    }

    // 今現在登録されているJob一覧を取得する
    public function get_job_list($flag = 0) {
        global $CFG, $DB, $COURSE;

        $sortorder = ($flag == 0) ? 'ASC' : 'DESC';
        $reservation = '';
        if ( $flag != 0 ) {
            $reservation = 'AND executeflag != 0';
            $orderinstantly = '';
        } else {
            $orderinstantly = 'bh.instantly DESC,';
        }

        // 2014-04-18 ページング用データをset
        $this->set_paging_data ( $reservation, $flag );

        $sql = <<< SQL
select bh.id, bh.course, bh.category, bh.executedatetime,
bh.jobtitle,bh.mailtitle,bh.executeflag, bh.timecreated,
bh.createuser, bh.instantly, u.lastname, u.firstname
FROM {$CFG->prefix}block_hsmail AS bh
INNER JOIN {$CFG->prefix}user AS u ON bh.createuser = u.id
WHERE bh.course = ? AND bh.category = ? AND executeflag <= ? {$reservation}
ORDER BY {$orderinstantly} bh.executedatetime {$sortorder}
SQL;

        // $list = $DB->get_records_sql($sql,array( $COURSE->id, $COURSE->category, $flag));
        $list = $DB->get_records_sql ( $sql, array (
                $COURSE->id,
                $COURSE->category,
                $flag
        ), $this->page * $this->perpage, $this->perpage ); // 2014-04-16 ページング対応
        return $list;
    }

    // 配信済みユーザを返す
    public function get_sent_list() {
        global $DB, $CFG;
        $sql = <<< SQL
SELECT hsmail,count(*) AS c FROM {$CFG->prefix}block_hsmail_userlog GROUP BY hsmail ORDER BY hsmail DESC
SQL;
        $ret = $DB->get_records_sql ( $sql );

        $list = array ();
        while ( list ( $key, $value ) = each ( $ret ) ) {
            $list [$value->hsmail] = $value->c;
        }
        return $list;
    }
    // 未配信ユーザを返す
    public function get_send_list() {
        global $DB, $CFG;
        // キューから残りのメール数を取得
        $sql = <<< SQL
SELECT hsmail,count(*) AS c FROM {$CFG->prefix}block_hsmail_queue
GROUP BY hsmail
SQL;
        $ret = $DB->get_records_sql ( $sql );
        $list = array ();
        while ( list ( $key, $value ) = each ( $ret ) ) {
            $list [$value->hsmail] = $value->c;
        }
        return $list;
    }

    // コースの未配信数を返す
    public function get_course_send_list($courseid) {
        global $DB, $CFG;
        // キューから残りのメール数を取得
        $sql = <<< SQL
SELECT count(*) AS cnt FROM {$CFG->prefix}block_hsmail_queue AS T1 INNER JOIN {$CFG->prefix}block_hsmail AS T2 ON T1.hsmail=T2.id WHERE T2.course=?;
SQL;
        $ret = $DB->get_record_sql ( $sql, array (
                $courseid
        ) );

        if ( isset ( $ret->cnt ) ) {
            return $ret->cnt;
        } else {
            return 0;
        }
    }

    // 配信開始、完了を返す
    public function get_mail_start_end() {
        global $DB, $CFG;
        $sql = <<< SQL
SELECT hsmail,min(timecreated) AS min, max(timecreated) AS max FROM {$CFG->prefix}block_hsmail_userlog
GROUP BY hsmail
ORDER BY hsmail DESC
SQL;
        $ret = $DB->get_records_sql ( $sql );
        $list = array ();
        while ( list ( $key, $value ) = each ( $ret ) ) {
            $list [$value->hsmail] ['start'] = $value->min;
            $list [$value->hsmail] ['end'] = $value->max;
        }
        return $list;
    }

    // 自分宛てのメール一覧を取得する
    public function get_mail_list() {
        global $CFG, $DB, $COURSE, $USER;

        // 2014-04-18 ページング用データをset
        $this->set_paging_data_mail_list ();

        $sql = <<< SQL
SELECT ul.*, bh.mailtitle, bh.mailbody, u.lastname, u.firstname
FROM {$CFG->prefix}block_hsmail_userlog AS ul
INNER JOIN {$CFG->prefix}block_hsmail AS bh ON ul.hsmail=bh.id
INNER JOIN {$CFG->prefix}user AS u ON bh.createuser=u.id
WHERE bh.course = ? AND bh.category = ? AND ul.userid=?
ORDER BY ul.id DESC
SQL;

        $list = $DB->get_records_sql ( $sql, array (
                $COURSE->id,
                $COURSE->category,
                $USER->id
        ), $this->page * $this->perpage, $this->perpage ); // 2014-04-16 ページング対応

        return $list;
    }

    // ページング用データをset
    public function set_paging_data_mail_list() {
        global $CFG, $DB, $COURSE, $USER;

        // 総数取得
        $sql = <<< SQL
SELECT COUNT(*) AS cnt
FROM {$CFG->prefix}block_hsmail_userlog AS ul
INNER JOIN {$CFG->prefix}block_hsmail AS bh ON ul.hsmail=bh.id
INNER JOIN {$CFG->prefix}user AS u ON bh.createuser=u.id
WHERE bh.course = ? AND bh.category = ? AND ul.userid=?
SQL;
        $datacount = $DB->get_record_sql ( $sql, array (
                $COURSE->id,
                $COURSE->category,
                $USER->id
        ) ); // 総数取得
        $this->totalcount = $datacount->cnt;

        $context = context_course::instance ( $COURSE->id );

        // ページ毎表示件数取得
        $sql = "SELECT configdata FROM {$CFG->prefix}block_instances WHERE blockname='hsmail' AND parentcontextid=?";
        $blockconfigdata = $DB->get_record_sql ( $sql, array (
                $context->id
        ) );
        if ( ! empty ( $blockconfigdata->configdata ) ) {
            $blockconfigdata = unserialize ( base64_decode ( $blockconfigdata->configdata ) );
            if ( isset ( $blockconfigdata->user_perpage ) ) {
                $this->perpage = $blockconfigdata->user_perpage;
            }
        }

        $this->page = optional_param ( 'page', 0, PARAM_INT ); // page番号
    }

    // 自分宛てのメール一覧を取得する
    public function get_mail_detail() {
        global $CFG, $DB, $COURSE, $USER;

        $mailid = optional_param ( 'mailid', 0, PARAM_INT );

        $sql = <<< SQL
SELECT ul.*, bh.mailtitle, bh.mailbody, u.lastname, u.firstname
FROM {$CFG->prefix}block_hsmail_userlog AS ul
INNER JOIN {$CFG->prefix}block_hsmail AS bh ON ul.hsmail=bh.id
INNER JOIN {$CFG->prefix}user AS u ON bh.createuser=u.id
WHERE bh.id = ? AND bh.course = ? AND bh.category = ? AND ul.userid=?
SQL;

        $maildetail = $DB->get_record_sql ( $sql, array (
                $mailid,
                $COURSE->id,
                $COURSE->category,
                $USER->id
        ) );

        return $maildetail;
    }
}