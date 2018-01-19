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
require_once(dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . '/config.php');
global $CFG;
require_once( $CFG->dirroot . '/blocks/hsmail/hsmailbase.php' );
/**
 *
 * @author h-honda
 *
 */
class target extends hsmailbase {

    public function __construct() {
        $this->conditionname = 'target';
    }
    /**
     * この条件に一致するユーザ一覧のSQL文を返す
     *
     * @param unknown $courseid
     * @param unknown $planvalue
     * @return string
     */
    public function regist_users_sql($courseid, $planvalue) {
        global $DB, $CFG;

        if ($planvalue == 'a') { // サイト利用者
            $sql = <<< SQL
SELECT T2.userid AS userid FROM {$CFG->prefix}block_hsmail_temp AS T2
SQL;
        } else if ($planvalue == 'c') { // コース受講者
            $sql = <<< SQL
SELECT T2.userid AS userid FROM {$CFG->prefix}block_hsmail_temp AS T2
SQL;
        } else if (substr ( $planvalue, 0, 1 ) == 'g') { // グループ
            $groupid = ( int ) substr ( $planvalue, 1 );
            $sql = <<< SQL
SELECT userid FROM {$CFG->prefix}groups_members WHERE groupid={$groupid}
SQL;
        } else {
            // コーホート
            $sql = <<< SQL
SELECT userid FROM {$CFG->prefix}cohort_members AS cm
WHERE cm.cohortid = {$planvalue}
SQL;
        }

        return $sql;
    }

    /**
     * 設定配列の生成
     * {@inheritDoc}
     * @see hsmailbase::make_plan_data()
     */
    public function make_plan_data($formdata) {
        return array (
                'target' => $formdata->target
        );
    }
}
/**
 *
 * @author h-honda
 *
 */
class target_form extends moodleform {

    public function definition() {
    }
    /**
     * 設定画面
     * @param unknown $mform
     * @param unknown $defaultdata
     */
    public function build_form(&$mform, $defaultdata = null) {
        global $CFG, $COURSE;

        $options = array ();
        $options [''] = array ();

        if ($COURSE->id == SITEID) {
            $options [''] ['a'] = get_string ( 'target_a', 'block_hsmail' );
        } else {
            $options [''] ['c'] = get_string ( 'target_c', 'block_hsmail' );
        }

        // コーホートと結合
        $cohort = $this->get_cohort_name ();
        $options [get_string ( 'cohort', 'cohort' )] = $cohort;

        // グループ取得
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
     * コーホート名の取得
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
     * グループ名の取得
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