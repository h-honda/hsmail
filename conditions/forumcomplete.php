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
 * Forum Complete.
 * @package block_hsmail
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/hsmail/hsmailbase.php' );
require_once($CFG->dirroot . '/lib/completionlib.php' );
/**
 * Forum complete class
 * @author h-honda
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumcomplete extends hsmailbase {

    /**
     * Constrcut
     */
    public function __construct() {
        $this->conditionname = 'forumcomplete';
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
            $forumselect = count ( $planvaluearray );

            $forumsql = <<< SQL
SELECT id FROM {forum} WHERE course= ? order by id
SQL;

            $foruminfos = $DB->get_records_sql ( $forumsql, array (
                    $courseid
            ) );
            $forumnumber = count ( $foruminfos );
            $forumunselect = $forumnumber - $forumselect;
        }

        if ($planvalue [0] == 'c') { // Forum completed.

            $sql = <<< SQL
SELECT cmc.userid FROM {course_modules_completion} AS cmc
INNER JOIN {course_modules} AS cm ON cmc.coursemoduleid = cm.id AND (cmc.completionstate =1 OR cmc.completionstate =2)
INNER JOIN {modules} AS m ON cm.module = m.id AND m.name='forum'
WHERE cm.course = ? AND cm.instance IN (?) AND cmc.userid NOT IN (
SELECT cmc.userid FROM {course_modules_completion} AS cmc
INNER JOIN {course_modules} AS cm ON cmc.coursemoduleid = cm.id AND (cmc.completionstate =1 OR cmc.completionstate =2)
INNER JOIN {modules} AS m ON cm.module = m.id AND m.name='forum'
WHERE cm.course = ? AND cm.instance NOT IN (?)
GROUP BY cmc.userid
) AND cmc.userid IN (SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} AS con ON con.id = ra.contextid
INNER JOIN {role} AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = ?)
GROUP BY cmc.userid
HAVING count(cmc.userid) = ?
SQL;
            $param = array($courseid, $planvalue[1], $courseid, $planvalue[1], $courseid, $forumselect);
        } else if ($planvalue [0] == 'i') { // Forum not completed.

            if ($forumunselect == 0) {
                $sql = <<< SQL
SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} AS con ON con.id = ra.contextid
INNER JOIN {role} AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = ?  AND ra.userid NOT IN
(SELECT cmc.userid FROM {course_modules_completion} cmc
INNER JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'forum'
INNER JOIN {forum} f ON f.id = cm.instance
WHERE cmc.completionstate = 1 OR cmc.completionstate = 2 AND cm.course = ?)
GROUP BY ra.userid
SQL;
                $param = array($courseid, $courseid);
            } else {
                $sql = <<< SQL
SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} AS con ON con.id = ra.contextid
INNER JOIN {role} AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = ? AND ra.userid NOT IN
(
SELECT cmc.userid FROM {course_modules_completion} cmc
INNER JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'forum'
INNER JOIN {forum} f ON f.id = cm.instance
WHERE f.id in(?) AND (cmc.completionstate = 1 OR cmc.completionstate = 2) AND cm.course = ?
GROUP BY cmc.userid
HAVING count(cmc.userid) = ?
)
SQL;
                $param = array($courseid, $planvalue[1], $courseid, $forumselect);
            }
        } else { // No setting.
            $sql = <<< SQL
SELECT T2.userid AS userid FROM {block_hsmail_temp} AS T2
SQL;
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
        if (isset ( $formdata->forumcomplete )) {
            if (isset ( $formdata->forumname )) {
                $tmp = array (
                        'forumcomplete' => array (
                                $formdata->forumcomplete,
                                implode ( ',', $formdata->forumname )
                        )
                );
            } else {
                $tmp = array (
                        'forumcomplete' => $formdata->forumcomplete
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
     * @param mixed $data
     * @param object $files
     * @param array $errormsg
     */
    public function validation($data, $files, &$errormsg) {
        if ($data ['forumcomplete'] != 'a') {
            if (! isset ( $data ['forumname'] )) {
                $errormsg ['forumname'] = get_string ( 'forumcomplete_error', 'block_hsmail' );
            }
        }
        return $errormsg;
    }
}
/**
 * forumcomplete form class
 * @author h-honda
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forumcomplete_form extends moodleform {

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
        global $DB, $COURSE;

        // Fourm completed status.
        $options = array (
                'a' => '-',
                'c' => get_string ( 'forumcomplete_c', 'block_hsmail' ),
                'i' => get_string ( 'forumcomplete_i', 'block_hsmail' )
        );

        $select = $mform->addElement ( 'select', 'forumcomplete', get_string ( 'forumcomplete', 'block_hsmail' ), $options );

        $mform->closeHeaderBefore ( 'forumcomplete_condition' );
        $mform->addRule ( 'forumcomplete', get_string ( 'required' ), 'required', '', 'client' );
        $mform->setType ( 'id', PARAM_INT );
        $mform->setType ( 'forumcomplete', PARAM_TEXT );

        if ($defaultdata === null) {
            $defaults = array (
                    'forumcomplete' => 'a'
            );
        } else {
            $defaults = array (
                    'forumcomplete' => $defaultdata ['planvalue'] [0]
            );
        }
        $mform->setDefaults ( $defaults );

        // Forum name.
        $sql = <<< SQL
SELECT  f.id, f.name FROM {forum} f
INNER JOIN {course_modules} cm ON cm.instance = f.id AND ( completion = 1 OR completion = 2)
INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'forum'
WHERE f.course = ?
ORDER BY f.id
SQL;

        $foruminfos = $DB->get_records_sql ( $sql, array (
                $COURSE->id
        ) );
        $forumnameoptions = array ();
        foreach ($foruminfos as $foruminfo) {
            $forumnameoptions [$foruminfo->id] = $foruminfo->name;
        }

        $select = $mform->addElement ( 'select', 'forumname', get_string ( 'forumname', 'block_hsmail' ), $forumnameoptions );
        $select->setMultiple ( true );

        $mform->closeHeaderBefore ( 'forumname_condition' );
        $mform->setType ( 'id', PARAM_INT );
        $mform->setType ( 'forumname', PARAM_TEXT );

        if ($defaultdata === null) {
            // Set default data (if any).
            $defaults = array (
                    'forumname' => 'a'
            );
        } else if (isset ( $defaultdata ['planvalue'] [1] )) {
            // Edit.
            $defaults = array (
                    'forumname' => explode ( ',', $defaultdata ['planvalue'] [1] )
            );
        }
        $mform->disabledIf ( 'forumname', 'forumcomplete', 'eq', 'a' );
        $mform->setDefaults ( $defaults );
    }
}