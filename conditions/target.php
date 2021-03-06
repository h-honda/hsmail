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
 * Target.
 * @package block_hsmail
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once( $CFG->dirroot . '/blocks/hsmail/hsmailbase.php' );
/**
 * Target class
 * @author h-honda
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class target extends hsmailbase {

    /**
     * Construct
     */
    public function __construct() {
        $this->conditionname = 'target';
    }
    /**
     * Return the SQL statement of the user list that matches this condition
     *
     * @param unknown $courseid
     * @param unknown $planvalue
     * @return string
     */
    public function regist_users_sql($courseid, $planvalue) {
        $param = array();
        if ($planvalue == 'a') { // Site user.
            $sql = <<< SQL
SELECT T2.userid AS userid FROM {block_hsmail_temp} AS T2
SQL;
        } else if ($planvalue == 'c') { // Course participants.
            $sql = <<< SQL
SELECT T2.userid AS userid FROM {block_hsmail_temp} AS T2
SQL;
        } else if (substr ( $planvalue, 0, 1 ) == 'g') { // Group.
            $groupid = ( int ) substr ( $planvalue, 1 );
            $sql = <<< SQL
SELECT userid FROM {groups_members} WHERE groupid= ?
SQL;
            $param = array($groupid);
        } else {
            // Cohort.
            $sql = <<< SQL
SELECT userid FROM {cohort_members} AS cm
WHERE cm.cohortid = ?
SQL;
            $param = array($planvalue);
        }

        return array($sql, $param);
    }

    /**
     * Generate configuration array
     * {@inheritDoc}
     * @see hsmailbase::make_plan_data()
     * @param unknown $formdata
     */
    public function make_plan_data($formdata) {
        return array (
                'target' => $formdata->target
        );
    }
}
/**
 * Target form class
 * @author h-honda
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class target_form extends moodleform {

    /**
     * Definition
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
    }
    /**
     * Setting screen
     * @param unknown $mform
     * @param unknown $defaultdata
     */
    public function build_form(&$mform, $defaultdata = null) {
        global $COURSE;

        $options = array ();
        $options [''] = array ();

        if ($COURSE->id == SITEID) {
            $options [''] ['a'] = get_string ( 'target_a', 'block_hsmail' );
        } else {
            $options [''] ['c'] = get_string ( 'target_c', 'block_hsmail' );
        }

        // Combined with cohort.
        $cohort = $this->get_cohort_name ();
        $options [get_string ( 'cohort', 'cohort' )] = $cohort;

        // Get group name.
        $group = $this->get_group_name ();
        $options [get_string ( 'group' )] = $group;
        $mform->addElement ( 'selectgroups', 'target', get_string ( 'target', 'block_hsmail' ), $options );
        $mform->addRule ( 'target', get_string ( 'required' ), 'required', '', 'client' );

        $mform->closeHeaderBefore ( 'target_condition' );

        $mform->setType ( 'id', PARAM_INT );
        $mform->setType ( 'target', PARAM_TEXT );

        if ($defaultdata === null) {
            $defaults = array ();
        } else {
            $defaults = array (
                    'target' => $defaultdata ['planvalue']
            );
        }
        $mform->setDefaults ( $defaults );
    }
    /**
     * get of cohort name
     * @return NULL[]
     */
    private function get_cohort_name() {
        global $DB;
        $ret = $DB->get_records ( 'cohort' );
        $list = array ();
        foreach ($ret as $tmp) {
            $list [$tmp->id] = $tmp->name;
        }
        return $list;
    }
    /**
     * get group_name
     * @return NULL[]
     */
    private function get_group_name() {
        global $DB, $COURSE;
        $list = array ();
        $ret = $DB->get_records ( 'groups', array (
                'courseid' => $COURSE->id
        ), 'id' );
        foreach ($ret as $tmp) {
            $list ['g' . $tmp->id] = $tmp->name;
        }
        return $list;
    }
}