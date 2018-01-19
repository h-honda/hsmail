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
require_once( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . '/config.php' );
global $CFG;
require_once( $CFG->dirroot . '/blocks/hsmail/hsmailbase.php' );
require_once( $CFG->dirroot . '/lib/completionlib.php' );

/**
 *
 * @author h-honda
 *
 */
class assigncomplete extends hsmailbase {

    public function __construct () {
        $this->conditionname = 'assigncomplete';
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

        if ( is_array ( $planvalue ) && isset ( $planvalue [1] ) ) {
            $planvaluearray = explode ( ',', $planvalue [1] );
            $assignselectednumber = count ( $planvaluearray );

            $assignsql = <<< SQL
SELECT id FROM {$CFG->prefix}assign WHERE course= ? order by id
SQL;

            $assigninfos = $DB->get_records_sql ( $assignsql, array (
                    $courseid
            ) );
            $assignnumber = count ( $assigninfos );
            $assignunselectednumber = $assignnumber - $assignselectednumber;
        }

        if ( $planvalue [0] == 'c' ) { // 課題完了

            $sql = <<< SQL
SELECT cmc.userid FROM {$CFG->prefix}course_modules_completion AS cmc
INNER JOIN {$CFG->prefix}course_modules AS cm ON cmc.coursemoduleid = cm.id AND (cmc.completionstate =1 OR cmc.completionstate =2)
INNER JOIN {$CFG->prefix}modules AS m ON cm.module = m.id AND m.name='assign'
WHERE cm.course = {$courseid} AND cm.instance IN ({$planvalue[1]}) AND cmc.userid NOT IN (
SELECT cmc.userid FROM {$CFG->prefix}course_modules_completion AS cmc
INNER JOIN {$CFG->prefix}course_modules AS cm ON cmc.coursemoduleid = cm.id AND (cmc.completionstate =1 OR cmc.completionstate =2)
INNER JOIN {$CFG->prefix}modules AS m ON cm.module = m.id AND m.name='assign'
WHERE cm.course = {$courseid} AND cm.instance NOT IN ({$planvalue[1]})
GROUP BY cmc.userid
) AND cmc.userid IN (SELECT ra.userid FROM {$CFG->prefix}role_assignments AS ra
INNER JOIN {$CFG->prefix}context AS con ON con.id = ra.contextid
INNER JOIN {$CFG->prefix}role AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = {$courseid})
GROUP BY cmc.userid
HAVING count(cmc.userid) = {$assignselectednumber}
SQL;
        } else if ( $planvalue [0] == 'i' ) { // 課題未完了

            if ( $assignunselectednumber == 0 ) {
                $sql = <<< SQL
SELECT ra.userid FROM {$CFG->prefix}role_assignments AS ra
INNER JOIN {$CFG->prefix}context AS con ON con.id = ra.contextid
INNER JOIN {$CFG->prefix}role AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = {$courseid}  AND ra.userid NOT IN
(SELECT cmc.userid FROM {$CFG->prefix}course_modules_completion cmc
INNER JOIN {$CFG->prefix}course_modules cm ON cm.id = cmc.coursemoduleid
INNER JOIN {$CFG->prefix}modules m ON m.id = cm.module AND m.name = 'assign'
INNER JOIN {$CFG->prefix}assign a ON a.id = cm.instance
WHERE cmc.completionstate = 1 OR cmc.completionstate = 2 AND cm.course = {$courseid})
GROUP BY ra.userid
SQL;
            } else {
                $sql = <<< SQL
SELECT ra.userid FROM {$CFG->prefix}role_assignments AS ra
INNER JOIN {$CFG->prefix}context AS con ON con.id = ra.contextid
INNER JOIN {$CFG->prefix}role AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = {$courseid}  AND ra.userid NOT IN
(
SELECT cmc.userid FROM {$CFG->prefix}course_modules_completion cmc
INNER JOIN {$CFG->prefix}course_modules cm ON cm.id = cmc.coursemoduleid
INNER JOIN {$CFG->prefix}modules m ON m.id = cm.module AND m.name = 'assign'
INNER JOIN {$CFG->prefix}assign a ON a.id = cm.instance
WHERE a.id in({$planvalue[1]}) AND (cmc.completionstate = 1 OR cmc.completionstate = 2) AND cm.course = {$courseid}
GROUP BY cmc.userid
HAVING count(cmc.userid) = {$assignselectednumber}
)
SQL;
            }
        } else { // 設定なし
            $sql = <<< SQL
SELECT T2.userid AS userid FROM {$CFG->prefix}block_hsmail_temp AS T2
SQL;
        }

        return $sql;
    }

    /**
     *  設定配列の生成
     * {@inheritDoc}
     * @see hsmailbase::make_plan_data()
     */
    public function make_plan_data($formdata) {
        if ( isset ( $formdata->assigncomplete ) ) {
            if ( isset ( $formdata->assignname ) ) {
                $tmp = array (
                        'assigncomplete' => array (
                                $formdata->assigncomplete,
                                implode ( ',', $formdata->assignname )
                        )
                );
            } else {
                $tmp = array (
                        'assigncomplete' => $formdata->assigncomplete
                );
            }
            return $tmp;
        } else {
            return array ();
        }
    }

    /**
     * 個別のエラーチェックをする
     * {@inheritDoc}
     * @see hsmailbase::validation()
     */
    public function validation($data, $files, &$errormsg) {
        if ( $data ['assigncomplete'] != 'a' ) {
            if ( ! isset ( $data ['assignname'] ) ) {
                $errormsg ['assignname'] = get_string ( 'assigncomplete_error', 'block_hsmail' );
            }
        }
        return $errormsg;
    }
}
/**
 *
 * @author h-honda
 *
 */
class assigncomplete_form extends moodleform {

    public function definition() {
    }

    /**
     * 設定画面
     * @param unknown $mform
     * @param unknown $defaultdata
     */
    public function build_form(&$mform, $defaultdata = null) {
        global $CFG, $DB, $COURSE;

        // 課題完了ステータス
        $options = array (
                'a' => '-',
                'c' => get_string ( 'assigncomplete_c', 'block_hsmail' ),
                'i' => get_string ( 'assigncomplete_i', 'block_hsmail' )
        );

        $select = $mform->addElement ( 'select', 'assigncomplete', get_string ( 'assigncomplete', 'block_hsmail' ), $options );

        $mform->closeHeaderBefore ( 'assigncomplete_condition' );
        $mform->addRule ( 'assigncomplete', get_string ( 'required' ), 'required', '', 'client' );
        $mform->setType ( 'id', PARAM_INT );
        $mform->setType ( 'assigncomplete', PARAM_TEXT );

        if ( $defaultdata === null ) {
            // Set default data (if any).
            $defaults = array (
                    'assigncomplete' => 'a'
            );
        } else {
            // Edit.
            $defaults = array (
                    'assigncomplete' => $defaultdata ['planvalue'] [0]
            );
        }
        $mform->setDefaults ( $defaults );

        // 課題名
        $sql = <<< SQL
SELECT  a.id, a.name FROM {assign} a
INNER JOIN {course_modules} cm ON cm.instance = a.id AND ( completion = 1 OR completion = 2)
INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
WHERE a.course = ?
ORDER BY a.id
SQL;

        $assigninfos = $DB->get_records_sql ( $sql, array (
                $COURSE->id
        ) );
        $assignnameoptions = array ();
        foreach ($assigninfos as $assigninfo) {
            $assignnameoptions [$assigninfo->id] = $assigninfo->name;
        }

        $select = $mform->addElement ( 'select', 'assignname', get_string ( 'assignname', 'block_hsmail' ), $assignnameoptions );
        $select->setMultiple ( true );

        $mform->closeHeaderBefore ( 'assignname_condition' );
        $mform->setType ( 'id', PARAM_INT );
        $mform->setType ( 'assignname', PARAM_TEXT );

        if ( $defaultdata === null ) {
            // Set default data (if any)
            $defaults = array (
                    'assignname' => 'a'
            );
        } else if ( isset ( $defaultdata ['planvalue'] [1] ) ) {
            // Edit
            $defaults = array (
                    'assignname' => explode ( ',', $defaultdata ['planvalue'] [1] )
            );
        }
        $mform->disabledIf ( 'assignname', 'assigncomplete', 'eq', 'a' );
        $mform->setDefaults ( $defaults );
    }
}