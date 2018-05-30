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
 * HSmail base
 * @package   block_hsmail
 * @copyright 2013 Human Science CO., Ltd.  {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined ( 'MOODLE_INTERNAL' ) || die ();

/**
 * HSmail base class
 * @author h-honda
 * @copyright 2013 Human Science CO., Ltd.  {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class hsmailbase {
    /**
     * Condition name
     * @var string
     */
    protected $conditionname;
    /**
     * Sending user registration.
     * @param int $courseid
     * @param array $planvalue
     */
    abstract public function regist_users_sql($courseid, $planvalue);

    /**
     * Generate plan setting value.
     * @param mixed $formdata
     */
    abstract public function make_plan_data($formdata);

    /**
     * Used when special input check is required.
     * @param string $data
     * @param string $files
     * @param array $errormsg Fill in the error message in the field name contents in the subscript.
     * @return array
     */
    public function validation($data = '', $files = '', &$errormsg) {
        $data = '';
        $files = '';
        return $errormsg;
    }
    /**
     * Get target course
     * @return mixed|boolean
     */
    public function get_target_course() {
        global $DB;
        $sql = <<< SQL
SELECT
    bh.id,
    bh.course,
    bh.category,
    bh.mailtitle,
    bh.mailbody,
    bhp.planvalue,
    bh.hsmail
FROM {block_hsmail} AS bh
INNER JOIN {block_hsmail_plan} AS bhp ON bh.id = bhp.hsmail
WHERE
bhp.plan = ?
AND bh.executeflag = 0
SQL;
        $ret1 = $DB->get_record_sql ( $sql, array (
                $this->conditionname
        ) );
        return $ret1;
    }

    /**
     * Return plug-in specific settings.
     * @param number $hsmailid
     * @return boolean|mixed|stdClass|false
     */
    public function get_planvalue($hsmailid = 0) {
        if ( $hsmailid == 0 ) {
            return false;
        }
        global $DB;

        $classname = get_class ( $this );
        $ret = $DB->get_record ( 'block_hsmail_plan', array (
                'plan' => $classname,
                'hsmail' => $hsmailid
        ), 'planvalue' );

        if ( $ret !== false ) {
            $ret->planvalue = unserialize ( base64_decode ( $ret->planvalue ) );
            $tmp = false;
            foreach ($ret as $key => $value) {
                $tmp [$key] = $value;
            }
            return $tmp;
        }
        return false;
    }
}