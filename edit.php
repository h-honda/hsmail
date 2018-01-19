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

require_once(dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php');

$id = optional_param ( 'id', 0, PARAM_INT );
$jobid = required_param ( 'jobid', PARAM_INT );

if ($id) {
    if (! $course = $DB->get_record ( 'course', array (
            'id' => $id
    ) )) {
        error ( 'Course is misconfigured' );
    }
} else {
    error ( 'Course ID error' );
}

require_login ( $course );

$context = context_course::instance ( $id );
if ( ! has_capability ( 'block/hsmail:addcondition', $context ) ) {
    throw new moodle_exception ( 'You dont have capability' );
}

$PAGE->set_url ( '/blocks/hsmail/edit.php', array (
        'id' => $id,
        'jobid' => $jobid
) ); // このファイルのURLを設定
$PAGE->set_title ( get_string ( 'condition1', 'block_hsmail' ) ); // ブラウザのタイトルバーに表示されるタイトル
$PAGE->set_heading ( $course->shortname ); // ヘッダーに表示する文字列
$PAGE->set_pagelayout ( 'incourse' );
$PAGE->navbar->add ( get_string ( 'condition1', 'block_hsmail' ), "?id=$id&jobid=$jobid" );

require_once( 'hsmail_lib.php' );
$mform = new hsmail_detailform ( $jobid );

if ( $mform->is_cancelled () ) {
    // Handle form cancel operation, if cancel button is present on form.
    redirect ( new moodle_url ( '/blocks/hsmail/jobsetting.php', array (
            'id' => $id
    ) ) );
} else if ( $fromform = $mform->get_data () ) {

    $obj = new hsmail_lib ();
    $plan = array ();
    // プラン設定生成
    global $CFG;
    foreach ($obj->conditionfiles as $tmp) {
        $classfilename = $tmp . '.php';
        require_once( $CFG->dirroot . '/blocks/hsmail/conditions/' . $classfilename );
        $tmpobj = new $tmp ();
        $plan = $plan + $tmpobj->make_plan_data ( $fromform );
    }
    // DBへの登録
    $obj->update_job ( $fromform, $plan );
    redirect ( new moodle_url ( '/blocks/hsmail/jobsetting.php', array (
            'id' => $id
    ) ) );
} else {
    // ヘッダー出力
    echo $OUTPUT->header ();
    // Replace the following lines with you own code
    echo $OUTPUT->heading ( get_string ( 'condition1', 'block_hsmail' ) );
    $mform->display ();
    echo $OUTPUT->footer ();
}