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
require_once( $CFG->dirroot . '/lib/completionlib.php' );

class coursedisaccess extends hsmailbase {

    public function __construct() {
        $this->conditionname = 'coursedisaccess';
    }
    /**
     * Return the SQL statement of the user list that matches this condition
     *
     * @param unknown $courseid
     * @param unknown $planvalue
     * @return string
     */
    public function regist_users_sql($courseid, $planvalue) {
        global $DB, $CFG;

        if ( $planvalue != 'a' ) {
            $disaccesstime = strtotime ( date ( "Y-m-d H:i:s", strtotime ( "-" . $planvalue . "day" ) ) );

            $sql = <<< SQL
(select ra.userid from {$CFG->prefix}role_assignments ra INNER JOIN
(select lsl.userid, max(lsl.timecreated) timecreated
FROM {$CFG->prefix}logstore_standard_log AS lsl
WHERE lsl.eventname = '\\core\\event\\course_viewed' AND lsl.action = 'viewed'
AND lsl.courseid = {$courseid} GROUP BY lsl.userid) AS maxtime ON maxtime.userid = ra.userid
INNER JOIN {$CFG->prefix}context AS con ON con.id = ra.contextid
INNER JOIN {$CFG->prefix}role AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = {$courseid} AND maxtime.timecreated < {$disaccesstime}
GROUP BY ra.userid)
UNION
(SELECT ra.userid FROM {$CFG->prefix}role_assignments AS ra
INNER JOIN {$CFG->prefix}context AS con ON con.id = ra.contextid
INNER JOIN {$CFG->prefix}role AS r ON r.id = ra.roleid AND archetype = 'student'
WHERE ra.modifierid >0 AND con.instanceid = {$courseid} AND ra.userid NOT IN
(SELECT lsl.userid FROM {$CFG->prefix}logstore_standard_log AS lsl
WHERE lsl.eventname = '\\core\\event\\course_viewed' AND lsl.action = 'viewed'
AND lsl.courseid = {$courseid})
GROUP BY ra.userid)
SQL;
        } else { // No setting.
            $sql = <<< SQL
SELECT T2.userid AS userid FROM {$CFG->prefix}block_hsmail_temp AS T2
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
        if ( isset ( $formdata->coursedisaccess ) ) {
            return array (
                    'coursedisaccess' => $formdata->coursedisaccess
            );
        } else {
            return array ();
        }
    }
}
/**
 *
 * @author h-honda
 *
 */
class coursedisaccess_form extends moodleform {

    public function definition() {
    }
    /**
     * Setting screen
     * @param unknown $mform
     * @param unknown $defaultdata
     */
    public function build_form(&$mform, $defaultdata = null) {
        global $CFG, $COURSE;

        $maxdays = 30;
        for ($i = 1; $i <= $maxdays; $i ++) {
            $days [] = $i;
        }
        $disaccesstime = array ();

        foreach ($days as $day) {
            $disaccesstime [$day] = $day . get_string( 'days', 'block_hsmail');
        }

        $options = array (
                'a' => '-'
        ) + $disaccesstime;
        $select = $mform->addElement ( 'select', 'coursedisaccess', get_string ( 'coursedisaccess', 'block_hsmail' ), $options );

        $mform->closeHeaderBefore ( 'coursedisaccess_condition' );
        $mform->addRule ( 'coursedisaccess', get_string ( 'required' ), 'required', '', 'client' );
        $mform->setType ( 'id', PARAM_INT );
        $mform->setType ( 'coursedisaccess', PARAM_TEXT );

        if ( $defaultdata === null ) {
            // Set default data (if any).
            $defaults = array (
                    'coursedisaccess' => 'a'
            );
        } else {
            // Edit.
            $defaults = array (
                    'coursedisaccess' => $defaultdata ['planvalue']
            );
        }
        $mform->setDefaults ( $defaults );
    }
}