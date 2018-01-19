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

require_once( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php' );

$id = optional_param ( 'id', 0, PARAM_INT );
// $clicktype = optional_param('click_button', 0, PARAM_INT);

if ( $id ) {
    if ( ! $course = $DB->get_record ( 'course', array (
            'id' => $id
    ) )) {
        error ( 'Course is misconfigured' );
    }
} else {
    error ( 'Course ID error' );
}

require_login ( $course );
// 権限チェック
$context = context_course::instance ( $id );
if (! has_capability ( 'block/hsmail:viewmaillist', $context )) {
    throw new moodle_exception ( 'You dont have capability' );
}
// 表示処理
// 設定
// $PAGE->requires->js(new moodle_url('/blocks/hsmail/jobs.js'));

$PAGE->set_url ( '/blocks/hsmail/maillist.php', array (
        'id' => $id
) ); // このファイルのURLを設定
$PAGE->set_title ( get_string ( 'sentlist', 'block_hsmail' ) ); // ブラウザのタイトルバーに表示されるタイトル
$PAGE->set_heading ( $course->shortname ); // ヘッダーに表示する文字列
$PAGE->set_pagelayout ( 'incourse' );
$PAGE->navbar->add ( get_string ( 'sentlist', 'block_hsmail' ), "?id=$id" ); // ヘッダーのナビゲーションに項目追加

// 現在の登録Jobを読込み

// フォームの生成
require_once( 'lib.php' );
$mform = new hsmail_maillist_form ();

// ヘッダー出力
echo $OUTPUT->header ();

// Replace the following lines with you own code
echo $OUTPUT->heading ( get_string ( 'sentlist', 'block_hsmail' ) ); // メインエリアのタイトル

// / 表示 START

// echo '表示内容をここに記述';
echo $OUTPUT->container_start ( 'hsmail-view' );
$mform->display ();
echo $OUTPUT->container_end ();

echo $OUTPUT->footer ();