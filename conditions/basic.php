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

defined ( 'MOODLE_INTERNAL' ) || die ();
global $CFG;
require_once( $CFG->dirroot . '/blocks/hsmail/hsmailbase.php' );

/**
 *
 * @author h-honda
 *
 */
class basic extends hsmailbase {

    public function __construct() {
        $this->conditionname = 'basic';
    }
    /**
     * Return the SQL statement of the user list that matches this condition.
     *
     * @param unknown $courseid
     * @param unknown $planvalue
     * @return string
     */
    public function regist_users_sql($courseid, $planvalue) {
        global $DB, $CFG;

        $sql = <<< SQL
SELECT T2.userid AS userid FROM {block_hsmail_temp} AS T2
SQL;
        return $sql;
    }

    // Generate configuration array.
    /**
     *
     * {@inheritDoc}
     * @see hsmailbase::make_plan_data()
     */
    public function make_plan_data($formdata) {
        return array (
                'basic' => ''
        );
    }

    // Acquire setting value.
    /**
     *
     * {@inheritDoc}
     * @see hsmailbase::get_planvalue()
     */
    public function get_planvalue($hsmailid = 0) {
        global $DB;
        if ( $hsmailid == 0 ) {
            return false;
        }

        $ret = $DB->get_record ( 'block_hsmail', array (
                'id' => $hsmailid
        ), 'id,executedatetime,jobtitle,mailtitle,mailbody,instantly' );
        $tmp = false;
        foreach ($ret as $key => $value) {
            $tmp [$key] = $value;
        }
        return $tmp;
    }
}
/**
 *
 * @author h-honda
 *
 */
class basic_form extends moodleform {

    public function definition() {
    }
    /**
     * Setting screen.
     * @param unknown $mform
     * @param unknown $defaultdata
     */
    public function build_form(&$mform, $defaultdata = null) {
        global $CFG, $COURSE;

        $mform->addElement ( 'hidden', 'id', $COURSE->id );
        $mform->addElement ( 'hidden', 'jobid', '0' );
        $mform->addElement ( 'hidden', 'sesskey', sesskey());

        $mform->addElement ( 'text', 'condition_title', get_string ( 'head_title', 'block_hsmail' ), array (
                'size' => '40'
        ) );

        $mform->addElement ( 'header', 'detail_mail', get_string ( 'detail_mail', 'block_hsmail' ) );
        $mform->addElement ( 'text', 'mail_title', get_string ( 'head_mail', 'block_hsmail' ), array (
                'size' => '40'
        ) );
        $mform->addElement ( 'textarea', 'mailbody', get_string ( 'mailbody', 'block_hsmail' ),
                'wrap="virtual" rows="20" cols="50"' );
        $mform->addHelpButton ( 'mailbody', 'mailbody', 'block_hsmail' );

        $mform->addElement ( 'header', 'detail_condition', get_string ( 'detail_condition', 'block_hsmail' ) );

        $mform->addElement ( 'hidden', 'timing', '0' );
        $mform->addElement ( 'hidden', 'repeatinterval', '0' );

        $mform->addElement ( 'date_time_selector', 'datetime', get_string ( 'date_time_selector', 'block_hsmail' ) );
        // 2014-06-04 Immediate delivery.
        $mform->addElement ( 'checkbox', 'instantly', '', get_string ( 'instantly', 'block_hsmail' ) );

        $mform->disabledIf ( 'datetime', 'instantly', 'checked' );

        $mform->setExpanded ( 'detail_condition' );

        $mform->addRule ( 'condition_title', get_string ( 'required' ), 'required', '', 'client' );
        $mform->addRule ( 'mail_title', get_string ( 'required' ), 'required', '', 'client' );
        $mform->addRule ( 'mailbody', get_string ( 'required' ), 'required', '', 'client' );

        $mform->setType ( 'id', PARAM_INT );
        $mform->setType ( 'jobid', PARAM_INT );
        $mform->setType ( 'condition_title', PARAM_TEXT );
        $mform->setType ( 'timing', PARAM_INT );
        $mform->setType ( 'repeatinterval', PARAM_INT );
        $mform->setType ( 'mail_title', PARAM_TEXT );
        $mform->setType ( 'mailbody', PARAM_TEXT );
        $mform->setType ( 'instantly', PARAM_INT );

        // Set default data (if any).
        $defaults = array (
                'condition_title' => '',
                'timing' => 0,
                'repeatinterval' => 0,
                'mail_title' => '',
                'mailbody' => '',
                'datetime' => '',
                'instantly' => '0'
        );
        if ( $defaultdata !== null ) {
            // Make existing data the default value.
            $defaults = array (
                    'jobid' => $defaultdata ['id'],
                    'condition_title' => $defaultdata ['jobtitle'],
                    'timing' => 0,
                    'repeatinterval' => 0,
                    'datetime' => array (
                            'day' => date ( 'd', $defaultdata ['executedatetime'] ),
                            'month' => date ( 'm', $defaultdata ['executedatetime'] ),
                            'year' => date ( 'Y', $defaultdata ['executedatetime'] ),
                            'hour' => date ( 'H', $defaultdata ['executedatetime'] ),
                            'minute' => date ( 'i', $defaultdata ['executedatetime'] )
                    ),
                    'mail_title' => $defaultdata ['mailtitle'],
                    'mailbody' => $defaultdata ['mailbody'],
                    'instantly' => $defaultdata ['instantly']
            );
            $mform->setDefaults ( $defaults );
        }
    }
}