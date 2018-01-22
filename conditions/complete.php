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
require_once($CFG->dirroot . '/blocks/hsmail/hsmailbase.php' );
require_once($CFG->dirroot . '/lib/completionlib.php' );

/**
 *
 * @author h-honda
 *
 */
class complete extends hsmailbase {

    public function __construct() {
        $this->conditionname = 'complete';
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

        if ( $planvalue == 'c' ) { // Course completed.
            $sql = <<< SQL
SELECT userid FROM {$CFG->prefix}course_completions WHERE course={$courseid} AND timecompleted>0
SQL;
        } else if ( $planvalue == 'i' ) { // Course not completed.
            $sql = <<< SQL
SELECT userid FROM {$CFG->prefix}block_hsmail_temp
WHERE userid NOT IN
(SELECT userid FROM {$CFG->prefix}course_completions
WHERE course={$courseid} AND timecompleted>0)
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
        if ( isset ( $formdata->complete ) ) {
            return array (
                    'complete' => $formdata->complete
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
class complete_form extends moodleform {

    public function definition() {
    }
    /**
     * Setting screen
     * @param unknown $mform
     * @param unknown $defaultdata
     */
    public function build_form(&$mform, $defaultdata = null) {
        global $CFG, $COURSE;

        $completioninfo = new completion_info ( $COURSE );
        if ( ! $completioninfo->is_enabled () ) {
            return; // Invalid when completion tracking is disabled.
        }

        $options = array (
                'a' => '-',
                'c' => get_string ( 'complete_c', 'block_hsmail' ),
                'i' => get_string ( 'complete_i', 'block_hsmail' )
        );

        $select = $mform->addElement ( 'select', 'complete', get_string ( 'complete', 'block_hsmail' ), $options );

        $mform->closeHeaderBefore ( 'complete_condition' );

        $mform->addRule ( 'complete', get_string ( 'required' ), 'required', '', 'client' );

        $mform->setType ( 'id', PARAM_INT );
        $mform->setType ( 'complete', PARAM_TEXT );

        if ( $defaultdata === null ) {
            // Set default data (if any).
            $defaults = array (
                    'complete' => 'a'
            );
        } else {
            // Edit.
            $defaults = array (
                    'complete' => $defaultdata ['planvalue']
            );
        }
        $mform->setDefaults ( $defaults );
    }
}