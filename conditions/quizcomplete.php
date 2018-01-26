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

global $CFG;
require_once( $CFG->dirroot . '/blocks/hsmail/hsmailbase.php' );
require_once( $CFG->dirroot . '/lib/completionlib.php' );
/**
 *
 * @author h-honda
 *
 */
class quizcomplete extends hsmailbase {

    public function __construct() {
        $this->conditionname = 'quizcomplete';
    }
    /**
     * Return the SQL statement of the user list that matches this condition
     *
     * @param unknown $courseid
     * @param unknown $planvalue
     * @return string
     */
    public function regist_users_sql($courseid, $planvalue) {
        global $DB;

        $param = array();
        if (is_array ( $planvalue ) && isset ( $planvalue [1] )) {
            $planvaluearray = explode ( ',', $planvalue [1] );
            $quizselectednumber = count ( $planvaluearray );

            $quizsql = <<< SQL
SELECT id FROM {quiz} WHERE course= ? order by id
SQL;
            $quizinfos = $DB->get_records_sql ( $quizsql, array (
                    $courseid
            ) );
            $quiznumber = count ( $quizinfos );
            $quizunselectednumber = $quiznumber - $quizselectednumber;
        }

        if ($planvalue [0] == 'c') { // Quiz completed.
            $sql = <<< SQL
SELECT cmc.userid FROM {course_modules_completion} AS cmc
INNER JOIN {course_modules} AS cm ON cmc.coursemoduleid = cm.id AND (cmc.completionstate =1 OR cmc.completionstate =2)
INNER JOIN {modules} AS m ON cm.module = m.id AND m.name='quiz'
WHERE cm.course = ? AND cm.instance IN (?) AND cmc.userid NOT IN (
SELECT cmc.userid FROM {course_modules_completion} AS cmc
INNER JOIN {course_modules} AS cm ON cmc.coursemoduleid = cm.id AND (cmc.completionstate =1 OR cmc.completionstate =2)
INNER JOIN {modules} AS m ON cm.module = m.id AND m.name='quiz'
WHERE cm.course = ? AND cm.instance NOT IN (?)
GROUP BY cmc.userid
) AND cmc.userid IN (SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} AS con ON con.id = ra.contextid
INNER JOIN {role} AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = ?)
GROUP BY cmc.userid
HAVING count(cmc.userid) = {$quizselectednumber}
SQL;
            $param = array($courseid, $planvalue[1], $courseid, $planvalue[1], $courseid, $quizselectednumber);
        } else if ($planvalue [0] == 'i') { // Quiz not completed.

            if ($quizunselectednumber == 0) {
                $sql = <<< SQL
SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} AS con ON con.id = ra.contextid
INNER JOIN {role} AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = ?  AND ra.userid NOT IN
(SELECT cmc.userid FROM {course_modules_completion} cmc
INNER JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
INNER JOIN {quiz} q ON q.id = cm.instance
WHERE cmc.completionstate = 1 OR cmc.completionstate = 2 AND cm.course = ?)
GROUP BY ra.userid
SQL;
                $param = array($courseid, $courseid);
            } else {
                $sql = <<< SQL
SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} AS con ON con.id = ra.contextid
INNER JOIN {role} AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = {$courseid}  AND ra.userid NOT IN
(
SELECT cmc.userid FROM {course_modules_completion} cmc
INNER JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
INNER JOIN {quiz} q ON q.id = cm.instance
WHERE q.id in({$planvalue[1]}) AND (cmc.completionstate = 1 OR cmc.completionstate = 2) AND cm.course = {$courseid}
GROUP BY cmc.userid
HAVING count(cmc.userid) = {$quizselectednumber}
)
SQL;
                $param = array($courseid, $planvalue[1], $courseid, $quizselectednumber )
            }
        } else { // No setting.
            $sql = <<< SQL
SELECT T2.userid AS userid FROM {block_hsmail_temp} AS T2
SQL;
        }
        return $sql;
    }

    /**
     * Generate configuration array
     * {@inheritDoc}
     * @see hsmailbase::make_plan_data()
     */
    public function make_plan_data($formdata) {
        if (isset ( $formdata->quizcomplete )) {
            if (isset ( $formdata->quizname )) {
                $tmp = array (
                        'quizcomplete' => array (
                                $formdata->quizcomplete,
                                implode ( ',', $formdata->quizname )
                        )
                );
            } else {
                $tmp = array (
                        'quizcomplete' => $formdata->quizcomplete
                );
            }
            return $tmp;
        } else {
            return array ();
        }
    }

    /**
     * Perform an individual error check
     * {@inheritDoc}
     * @see hsmailbase::validation()
     */
    public function validation($data, $files, &$errormsg) {
        if ($data ['quizcomplete'] != 'a') {
            if (! isset ( $data ['quizname'] )) {
                $errormsg ['quizname'] = get_string ( 'quizcomplete_error', 'block_hsmail' );
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
class quizcomplete_form extends moodleform {

    public function definition() {
    }
    /**
     * Setting screen
     * @param unknown $mform
     * @param unknown $defaultdata
     */
    public function build_form(&$mform, $defaultdata = null) {
        global $DB, $COURSE;

        // Quiz complete status.
        $options = array (
                'a' => '-',
                'c' => get_string ( 'quizcomplete_c', 'block_hsmail' ),
                'i' => get_string ( 'quizcomplete_i', 'block_hsmail' )
        );

        $select = $mform->addElement ( 'select', 'quizcomplete', get_string ( 'quizcomplete', 'block_hsmail' ), $options );

        $mform->closeHeaderBefore ( 'quizcomplete_condition' );
        $mform->addRule ( 'quizcomplete', get_string ( 'required' ), 'required', '', 'client' );
        $mform->setType ( 'id', PARAM_INT );
        $mform->setType ( 'quizcomplete', PARAM_TEXT );

        if ($defaultdata === null) {
            $defaults = array (
                    'quizcomplete' => 'a'
            );
        } else {
            $defaults = array (
                    'quizcomplete' => $defaultdata ['planvalue'] [0]
            );
        }
        $mform->setDefaults ( $defaults );

        // Quiz name.
        $sql = <<< SQL
SELECT  q.id, q.name FROM {quiz} q
INNER JOIN {course_modules} cm ON cm.instance = q.id AND ( completion = 1 OR completion = 2)
INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
WHERE q.course = ?
ORDER BY q.id
SQL;
        $quizinfos = $DB->get_records_sql ( $sql, array (
                $COURSE->id
        ) );
        $quiznameoptions = array ();
        foreach ($quizinfos as $quizinfo) {
            $quiznameoptions [$quizinfo->id] = $quizinfo->name;
        }

        $select = $mform->addElement ( 'select', 'quizname', get_string ( 'quizname', 'block_hsmail' ), $quiznameoptions );
        $select->setMultiple ( true );

        $mform->closeHeaderBefore ( 'quizname_condition' );
        $mform->setType ( 'id', PARAM_INT );
        $mform->setType ( 'quizname', PARAM_TEXT );

        if ($defaultdata === null) {
            // Set default data (if any).
            $defaults = array (
                    'quizname' => 'a'
            );
        } else if (isset ( $defaultdata ['planvalue'] [1] )) {
            // Edit.
            $defaults = array (
                    'quizname' => explode ( ',', $defaultdata ['planvalue'] [1] )
            );
        }
        $mform->disabledIf ( 'quizname', 'quizcomplete', 'eq', 'a' );
        $mform->setDefaults ( $defaults );
    }
}