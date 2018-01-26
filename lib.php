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

require_once( $CFG->libdir . '/formslib.php' );

class hsmail_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE;

        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement ( 'hidden', 'id', $COURSE->id, 'id="courseid"' );
        $mform->setType ( 'id', PARAM_INT );

        $deleteconfirm = get_string ( 'delete_confirm', 'block_hsmail' );

        // Delete confirmation JavaScript.
        $js = <<< JS
<SCRIPT lang="JavaScript">
<!--
function check(){
        ret = confirm('{$deleteconfirm}');
        return ret;
}
// -->
</SCRIPT>
JS;
        $mform->addElement ( 'html', $js );

        require_once( $CFG->dirroot . '/blocks/hsmail/hsmail_lib.php' );
        $obj = new hsmail_lib ();
        $list = $obj->get_job_list ( 0 );

        // 2014-04-18 Paging support.
        $paging = $obj->get_paging ( '/blocks/hsmail/jobsetting.php' );
        $mform->addElement ( 'html', $paging );

        $table = new html_table ();
        $table->id = 'condition_list';
        $table->head = array ();
        $table->colclasses = array ();
        $table->attributes ['class'] = 'admintable generaltable table-center';

        $table->head [] = get_string ( 'head_create', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_title', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_mail', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_user', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_sent', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_action', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        foreach ($list as $tmp) {
            $urldelete = new moodle_url ( '/blocks/hsmail/delete.php', array (
                    'id' => $COURSE->id,
                    'jobid' => $tmp->id,
                    'sesskey' => sesskey()
            ) );
            $urledit = new moodle_url ( '/blocks/hsmail/edit.php', array (
                    'id' => $COURSE->id,
                    'jobid' => $tmp->id,
                    'sesskey' => sesskey()
            ) );

            $row = array ();
            $row [] = date ( 'Y/m/d', $tmp->timecreated );
            $row [] = html_writer::link ( $urledit, $tmp->jobtitle );
            $row [] = $tmp->mailtitle;
            $row [] = $tmp->lastname . ' ' . $tmp->firstname;

            if ( $tmp->instantly == 1 ) {
                $row [] = get_string ( 'instantly', 'block_hsmail' );
            } else {
                $row [] = date ( 'Y/m/d H:i', $tmp->executedatetime );
            }

            $actiondelete = html_writer::link ( $urldelete, get_string ( 'delete' ), array (
                    'onClick' => 'return check()'
            ) ) . ' ';
            $actionedit = html_writer::link ( $urledit, get_string ( 'edit' ) ) . ' ';

            if ( $tmp->executeflag == 0 ) {
                $action = $actiondelete . $actionedit; // Unsent.
            } else {
                $action = '';
            }
            $row [] = $action;

            $table->data [] = $row;
        }
        $html = html_writer::table ( $table );
        $mform->addElement ( 'html', $html );

        // 2014-04-18 Paging support.
        $mform->addElement ( 'html', $paging );
    }

    // Custom validation should be added here.
    public function validation($data = '', $files = '') {
        return array ();
    }
}
class hsmail_sent_form extends moodleform {
    public function definition() {
        global $CFG, $COURSE;
        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement ( 'hidden', 'id', $COURSE->id, 'id="courseid"' );
        $mform->setType ( 'id', PARAM_INT );

        require_once( $CFG->dirroot . '/blocks/hsmail/hsmail_lib.php' );
        $obj = new hsmail_lib ();
        $list = $obj->get_job_list ( 2 ); // Error and completion list.

        $sumi = $obj->get_sent_list (); // Delivered.
        // Undelivered user.
        $misumi = $obj->get_send_list ();
        // Start completed.
        $time = $obj->get_mail_start_end ();

        // Undelivered number calculation.
        $sum = 0;
        while ( list ( $key, $value ) = each ( $misumi ) ) {
            $sum += $value;
        }
        reset ( $misumi );

        // Number of undelivered courses.
        $misumicourse = $obj->get_course_send_list ( $COURSE->id );

        $mform->addElement ( 'html', "<div>" .
                get_string ( 'queue_count', 'block_hsmail', $COURSE->fullname ) . "：{$misumicourse}</div>" );
        $mform->addElement ( 'html', "<div>" .
                get_string ( 'queue_count_total', 'block_hsmail' ) . "：{$sum}</div>" );

        // 2014-04-18 Paging support.
        $paging = $obj->get_paging ( '/blocks/hsmail/sentlist.php' );
        $mform->addElement ( 'html', $paging );

        $table = new html_table ();
        $table->id = 'sent_list';
        $table->head = array ();
        $table->colclasses = array ();
        $table->attributes ['class'] = 'admintable generaltable table-center';

        $table->head [] = get_string ( 'head_create', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_title', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_mail', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_user', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_sent', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_sent_count', 'block_hsmail' );
        $table->colclasses [] = 'rightalign';

        $table->head [] = get_string ( 'head_sent_start', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_sent_end', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        foreach ($list as $tmp) {
            $row = array ();
            $row [] = date ( 'Y/m/d', $tmp->timecreated );
            $row [] = $tmp->jobtitle;
            $row [] = $tmp->mailtitle;
            $row [] = $tmp->lastname . ' ' . $tmp->firstname;

            if ( $tmp->instantly == 1 ) {
                $row [] = get_string ( 'instantly', 'block_hsmail' );
            } else {
                $row [] = date ( 'Y/m/d H:i', $tmp->executedatetime );
            }

            $tmpsumi = (array_key_exists ( $tmp->id, $sumi )) ? $sumi [$tmp->id] : 0;
            $tmpmisumi = (array_key_exists ( $tmp->id, $misumi )) ? $misumi [$tmp->id] : 0;
            $row [] = ($tmp->executeflag == 2) ? $tmpsumi + $tmpmisumi : 'error';
            $row [] = (array_key_exists ( $tmp->id, $time )) ? date ( 'Y/m/d H:i', $time [$tmp->id] ['start'] ) : '';
            if (! array_key_exists ( $tmp->id, $misumi )) {
                $buff = (array_key_exists ( $tmp->id, $time )) ? date ( 'Y/m/d H:i', $time [$tmp->id] ['end'] ) : '' ;
            }else{
                $buff = '';
            }
            $row [] = $buff;

            $table->data [] = $row;
        }
        $html = html_writer::table ( $table );
        $mform->addElement ( 'html', $html );

        // 2014-04-18 Paging support.
        $mform->addElement ( 'html', $paging );
    }
    public function validation($data = '', $files = '') {
        return array ();
    }
}

class hsmail_maillist_form extends moodleform {
    public function definition() {
        global $CFG, $COURSE, $OUTPUT;
        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement ( 'hidden', 'id', $COURSE->id, 'id="courseid"' );
        $mform->setType ( 'id', PARAM_INT );

        require_once( $CFG->dirroot . '/blocks/hsmail/hsmail_lib.php' );
        $obj = new hsmail_lib ();
        $list = $obj->get_mail_list (); // Mail list.

        // 2014-04-18 Paging support.
        $paging = $obj->get_paging ( '/blocks/hsmail/maillist.php' );
        $mform->addElement ( 'html', $paging );

        $table = new html_table ();
        $table->id = 'sent_list';
        $table->head = array ();
        $table->colclasses = array ();
        $table->attributes ['class'] = 'admintable generaltable table-center';

        $table->head [] = get_string ( 'assignment', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        $table->head [] = get_string ( 'head_mail', 'block_hsmail' );
        $table->colclasses [] = 'leftalign';

        foreach ($list as $tmp) {
            $row = array ();
            $row [] = date ( 'Y/m/d H:i', $tmp->timecreated );
            $url = new moodle_url ( '/blocks/hsmail/maildetail.php', array(
                    'id' => $COURSE->id,
                    'mailid' => $tmp->hsmail,
                    'page' => $obj->page,
                    'sesskey' => sesskey()
                    ));
            $link = html_writer::link ( $url, $tmp->mailtitle );
            $row [] = $link;
            $table->data [] = $row;
        }
        $html = html_writer::table ( $table );
        $mform->addElement ( 'html', $html );

        // 2014-04-18 Paging support.
        $mform->addElement ( 'html', $paging );
    }
    public function validation($data = '', $files = '') {
        return array ();
    }
}
class hsmail_maildetail_form extends moodleform {
    public function definition() {
        global $CFG, $COURSE, $OUTPUT;
        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement ( 'hidden', 'id', $COURSE->id, 'id="courseid"' );
        $mform->setType ( 'id', PARAM_INT );

        require_once( $CFG->dirroot . '/blocks/hsmail/hsmail_lib.php' );
        $obj = new hsmail_lib ();
        $maildetail = $obj->get_mail_detail (); // Mail details.

        $mform->addElement ( 'static', 'maildate', get_string ( 'assignment', 'block_hsmail' ),
                date ( 'Y/m/d H:i', $maildetail->timecreated ) );
        $mform->addElement ( 'static', 'mailtitle', get_string ( 'head_mail', 'block_hsmail' ),
                htmlspecialchars ( $maildetail->mailtitle ) );
        $mform->addElement ( 'static', 'mailbody', get_string ( 'mailbody', 'block_hsmail' ),
                nl2br ( htmlspecialchars ( $this->conv_placeholder ( $maildetail->mailbody ) ) ) );
    }
    public function validation($data, $files) {
        return array ();
    }

    // Place folder processing.
    public function conv_placeholder($body) {
        global $COURSE, $USER;

        $body = str_replace ( '[[user_name]]', "{$USER->lastname} {$USER->firstname}", $body );
        $body = str_replace ( '[[course_name]]', $COURSE->fullname, $body );

        return $body;
    }
}
