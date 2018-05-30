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
 * Assign complete
 * @package block_hsmail
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined ( 'MOODLE_INTERNAL' ) || die ();
global $CFG;
require_once( $CFG->dirroot . '/blocks/hsmail/hsmailbase.php' );
require_once( $CFG->dirroot . '/lib/completionlib.php' );

/**
 * Assign complete SQL
 * @package block_hsmail
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assigncomplete extends hsmailbase {

    /**
     * Construct.
     */
    public function __construct () {
        $this->conditionname = 'assigncomplete';
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
        if ( is_array ( $planvalue ) && isset ( $planvalue [1] ) ) {
            $planvaluearray = explode ( ',', $planvalue [1] );
            $asignselect = count ( $planvaluearray );

            $assignsql = <<< SQL
SELECT id FROM {assign} WHERE course= ? order by id
SQL;

            $assigninfos = $DB->get_records_sql ( $assignsql, array (
                    $courseid
            ) );
            $assignnumber = count ( $assigninfos );
            $asignunselect = $assignnumber - $asignselect;
        }

        if ( $planvalue [0] == 'c' ) { // Assign completed.

            $sql = <<< SQL
SELECT cmc.userid FROM {course_modules_completion} AS cmc
INNER JOIN {course_modules} AS cm ON cmc.coursemoduleid = cm.id AND (cmc.completionstate =1 OR cmc.completionstate =2)
INNER JOIN {modules} AS m ON cm.module = m.id AND m.name='assign'
WHERE cm.course = ? AND cm.instance IN (?) AND cmc.userid NOT IN (
SELECT cmc.userid FROM {course_modules_completion} AS cmc
INNER JOIN {course_modules} AS cm ON cmc.coursemoduleid = cm.id AND (cmc.completionstate =1 OR cmc.completionstate =2)
INNER JOIN {modules} AS m ON cm.module = m.id AND m.name='assign'
WHERE cm.course = ? AND cm.instance NOT IN (?)
GROUP BY cmc.userid
) AND cmc.userid IN (SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} AS con ON con.id = ra.contextid
INNER JOIN {role} AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = ?)
GROUP BY cmc.userid
HAVING count(cmc.userid) = ?
SQL;
            $param = array($courseid, $planvalue[1], $courseid, $planvalue[1], $courseid, $asignselect);
        } else if ( $planvalue [0] == 'i' ) { // Assign not completed.

            if ( $asignunselect == 0 ) {
                $sql = <<< SQL
SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} AS con ON con.id = ra.contextid
INNER JOIN {role} AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = ?  AND ra.userid NOT IN
(SELECT cmc.userid FROM {course_modules_completion} cmc
INNER JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid AND cm.instance IN (?)
INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
INNER JOIN {assign} a ON a.id = cm.instance
WHERE cmc.completionstate = 1 OR cmc.completionstate = 2 AND cm.course = ?)
GROUP BY ra.userid
SQL;
                $param = array($courseid, $planvalue[1], $courseid);
            } else {
                $sql = <<< SQL
SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} AS con ON con.id = ra.contextid
INNER JOIN {role} AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = ?  AND ra.userid NOT IN
(
SELECT cmc.userid FROM {course_modules_completion} cmc
INNER JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
INNER JOIN {assign} a ON a.id = cm.instance
WHERE a.id in(?) AND (cmc.completionstate = 1 OR cmc.completionstate = 2) AND cm.course = ?
GROUP BY cmc.userid
HAVING count(cmc.userid) = ?
)
SQL;
                $param = array($courseid, $planvalue[1], $courseid, $asignselect);
            }
        } else { // No setting.
            $sql = <<< SQL
SELECT T2.userid AS userid FROM {block_hsmail_temp} AS T2
SQL;
        }

        return array($sql, $param);
    }

    /**
     * {@inheritDoc}
     * @see hsmailbase::make_plan_data()
     * @param unknown $formdata
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
     * Perform an individual error check
     * {@inheritDoc}
     * @see hsmailbase::validation()
     * @param unknown $data
     * @param unknown $files
     * @param unknown $errirmsg
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
 * Assign complete form
 * @package block_hsmail
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assigncomplete_form extends moodleform {

    /**
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
        global $DB, $COURSE;

        // Assign complete status.
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

        // Assign name.
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
            // Set default data (if any).
            $defaults = array (
                    'assignname' => 'a'
            );
        } else if ( isset ( $defaultdata ['planvalue'] [1] ) ) {
            // Edit.
            $defaults = array (
                    'assignname' => explode ( ',', $defaultdata ['planvalue'] [1] )
            );
        }
        $mform->disabledIf ( 'assignname', 'assigncomplete', 'eq', 'a' );
        $mform->setDefaults ( $defaults );
    }
}