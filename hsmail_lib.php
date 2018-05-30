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
 * Detail form.
 * @package   block_hsmail
 * @copyright 2013 Human Science CO., Ltd.  {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php' );

/**
 * Hsmail detailed form.
 * @author h-honda
 * @copyright 2013 Human Science CO., Ltd.  {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hsmail_detailform extends moodleform {
    /**
     * Job id
     * @var integer
     */
    protected $jobid = 0;
    /**
     * Initializing variables.
     * @param number $jobid
     */
    public function __construct($jobid = 0) {
        $this->jobid = $jobid;
        parent::__construct();
    }
    /**
     * Contents definition.
     */
    protected function definition() {
        global $CFG;

        $hsmailobj = new hsmail_lib ();

        $mform = $this->_form; // Don't forget the underscore!

        // Basic information.
        global $CFG;
        require_once( $CFG->dirroot . '/blocks/hsmail/conditions/basic.php' );
        $basicplan = new basic ();
        if ($this->jobid != 0) {
            $defaultdata = $basicplan->get_planvalue ( $this->jobid );
        } else {
            $defaultdata = null;
        }
        $basicobj = new basic_form ();
        $basicobj->build_form ( $mform, $defaultdata );

        // Display logic of detailed setting screen other than basic.
        foreach ($hsmailobj->conditionfiles as $tmp) {
            if ( $tmp == 'basic' ) {
                continue;
            }
            $classfilename = $tmp . '.php';
            require_once( $CFG->dirroot . '/blocks/hsmail/conditions/' . $classfilename );
            $classplan = new $tmp ();
            if ( $this->jobid != 0 ) {
                $defaultdata = $classplan->get_planvalue ( $this->jobid );
            } else {
                $defaultdata = null;
            }

            $classname = $tmp . "_form";
            $tmpobj = new $classname ();
            $tmpobj->build_form ( $mform, $defaultdata );
        }

        $this->add_action_buttons ();
    }

    /**
     * Get Data.
     * @return unknown
     */
    public function get_data() {
        $data = parent::get_data ();
        if ( !is_null ( $data ) && ! isset ( $data->instantly ) ) {
            $data->instantly = 0; // 0 for immediate delivery check box off.
        }
        return $data;
    }

    /**
     * When there is an individual check.
     * @param unknown $data
     * @param unknown $files
     */
    public function validation($data, $files) {
        global $CFG;
        $errormsg = array ();
        $hsmailobj = new hsmail_lib ();
        // Display logic of detailed setting screen other than basic.
        foreach ($hsmailobj->conditionfiles as $tmp) {
            if ( $tmp == 'basic' ) {
                continue;
            }
            $classfilename = $tmp . '.php';
            require_once($CFG->dirroot . '/blocks/hsmail/conditions/' . $classfilename );

            $classname = $tmp;
            $tmpobj = new $classname ();
            if (method_exists ( $tmpobj, 'validation' )) {
                $tmpobj->validation ( $data, $files, $errormsg );
            }
        }
        return $errormsg;
    }
}

/**
 * Mail Sender Class.
 * @author h-honda
 * @copyright 2013 Human Science CO., Ltd.  {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hsmail_lib {
    /**
     * Location of condition file.
     * @var unknown
     */
    protected $conditiondir;

    /**
     * List of condition files.
     * @var unknown
     */
    public $conditionfiles;

    /**
     * Total number of data.
     * @var unknown
     */
    protected $totalcount;

    /**
     * Number of pages displayed per page.
     * Default value: Overwritten by block instance setting value.
     * @var integer
     */
    protected $perpage = 20;

    /**
     * Page number.
     * @var integer
     */
    protected $page = 0;

    /**
     * Construct
     */
    public function __construct() {
        global $CFG;

        $this->conditiondir = $CFG->dirroot . '/blocks/hsmail/conditions/';
        $resdir = opendir ( $this->conditiondir );
        // Get file name list.
        while ( $filename = readdir ( $resdir ) ) {
            if ( is_dir ( $filename )) {
                continue;
            }
            $work = str_replace ( '.php', '', $filename );
            $this->conditionfiles [] = $work;
        }
    }

    /**
     * Change content of job
     * @param unknown $data
     * @param unknown $plan
     * @throws moodle_exception
     */
    public function update_job($data, $plan) {
        global $DB, $USER;
        // Check jobid.
        if ( $data->jobid == 0 ) {
            return;
        }

        // Check if it is running.
        $basicdata = $DB->get_record ( 'block_hsmail', array (
                'id' => $data->jobid
        ) );
        if ( $basicdata->executeflag == 2 ) {
            // Display a message that can not be changed if it is running.
            throw new moodle_exception ( get_string ( 'err_already', 'block_hsmail' ) );
            return;
        }

        // Transaction from here.
        $transaction = null;
        try {
            $transaction = $DB->start_delegated_transaction ();
            // Change of setting information.
            // Register basic information.
            $now = date ( 'U' );
            if ( $data->timing == 1 ) {
                $settiming = ($data->repeatinterval == 0) ? 0 : $now;
            } else {
                $settiming = $data->datetime;
            }

            $dataobject = array (
                    'id' => $data->jobid,
                    'executedatetime' => $settiming,
                    'jobtitle' => $data->condition_title,
                    'mailtitle' => $data->mail_title,
                    'mailbody' => $data->mailbody,
                    'timemodified' => $now,
                    'modifieduser' => $USER->id,
                    'instantly' => $data->instantly
            );
            $DB->update_record ( 'block_hsmail', $dataobject );

            $id = $data->jobid;
            // Registering each condition.
            foreach ($plan as $key => $value) {
                if ( is_null ( $key )) {
                    continue;
                }
                $ret = $DB->get_record ( 'block_hsmail_plan', array (
                        'hsmail' => $id,
                        'plan' => $key
                ) );
                if ( $ret === false ) {
                    throw new moodle_exception ( get_string ( 'err_planid', 'block_hsmail' ) . "->{$id}->{$key}" );
                }
                // Plan.
                $dataobject = array (
                        'id' => $ret->id,
                        'hsmail' => $id,
                        'plan' => $key,
                        'planvalue' => base64_encode ( serialize ( $value ) ),
                        'timemodified' => $now
                );
                $DB->update_record ( 'block_hsmail_plan', $dataobject );
            }

            $transaction->allow_commit ();
        } catch ( Exception $e ) {
            $transaction->rollback ( $e );
        }
        // Transaction end so far.
    }

    /**
     * Delete job.
     * @param unknown $jobid
     * @throws moodle_exception
     */
    public function delete_job($jobid) {
        global $DB;
        // Check jobid.
        if ( $jobid == 0 ) {
            return;
        }

            // Check if it is running.
        $basicdata = $DB->get_record ( 'block_hsmail', array (
                'id' => $jobid
        ) );
        if ( $basicdata->executeflag == 2 ) {
            // Display a message that can not be changed if it is running.
            throw new moodle_exception ( get_string ( 'err_already', 'block_hsmail' ) );
            return;
        }
        // Transaction from here.
        $transaction = null;
        try {
            $transaction = $DB->start_delegated_transaction ();

            $ret = $DB->get_records ( 'block_hsmail_plan', array (
                    'hsmail' => $jobid
            ), 'id' );
            // Deletion of each condition.
            foreach ($ret as $tmp) {
                $DB->delete_records ( 'block_hsmail_plan', array (
                        'id' => $tmp->id
                ) );
            }

            // Delete outgoing job.
            $DB->delete_records ( 'block_hsmail', array (
                    'id' => $jobid
            ) );
            $transaction->allow_commit ();
        } catch ( Exception $e ) {
            $transaction->rollback ( $e );
        }
        // Transaction end so far.
    }
    /**
     * Insert jobs.
     * @param unknown $data
     * @param unknown $plan
     * @throws moodle_exception
     */
    public function insert_job($data, $plan = null) {
        global $USER, $COURSE, $DB;

        if ( ! is_array ( $plan ) ) {
            throw new moodle_exception ( "hsmail plan is not arrayl" );
        }

        foreach ($plan as $key => $value) {
            if (is_numeric ( $key )) {
                throw new moodle_exception ( "hsmail planvalue is null. You should set the planvalue." );
            }
        }
        // Transaction from here.
        $transaction = null;
        try {
            $transaction = $DB->start_delegated_transaction ();
            // Register basic information.
            $now = date ( 'U' );
            if ( $data->timing == 1 ) {
                $settiming = ($data->repeatinterval == 0) ? 0 : $now;
            } else {
                $settiming = $data->datetime;
            }

            $dataobject = array (
                    'course' => $COURSE->id,
                    'category' => $COURSE->category,
                    'executedatetime' => $settiming,
                    'repeatinterval' => $data->repeatinterval,
                    'jobtitle' => $data->condition_title,
                    'mailtitle' => $data->mail_title,
                    'mailbody' => $data->mailbody,
                    'executeflag' => 0,
                    'timecreated' => $now,
                    'createuser' => $USER->id,
                    'timemodified' => $now,
                    'modifieduser' => $USER->id,
                    'instantly' => $data->instantly
            );

            $id = $DB->insert_record ( 'block_hsmail', $dataobject, true );

            // Registering each condition.
            foreach ($plan as $key => $value) {
                if (is_null ( $key )) {
                    continue;
                }
                    // Plan.
                $dataobject = array (
                        'hsmail' => $id,
                        'plan' => $key,
                        'planvalue' => base64_encode ( serialize ( $value ) ),
                        'timecreated' => $now,
                        'timemodified' => $now
                );
                $DB->insert_record ( 'block_hsmail_plan', $dataobject );
            }
            $transaction->allow_commit ();
        } catch ( Exception $e ) {
            $transaction->rollback ( $e );
        }
    }

    /**
     * Set paging data to set 2014-04-18.
     * @param unknown $reservation
     * @param unknown $flag
     */
    public function set_paging_data($reservation, $flag) {
        global $DB, $COURSE;

        // Total number acquisition.
        $sql = <<< SQL
SELECT COUNT(*) AS cnt
FROM {block_hsmail} AS bh
INNER JOIN {user} AS u ON bh.createuser = u.id
WHERE bh.course = ? AND bh.category = ? AND executeflag <= ? {$reservation}
SQL;
        $datacount = $DB->get_record_sql ( $sql, array (
                $COURSE->id,
                $COURSE->category,
                $flag
        ) ); // Total number acquisition.
        $this->totalcount = $datacount->cnt;

        $context = context_course::instance ( $COURSE->id );

        // Acquire number of items per page.
        $sql = "SELECT configdata FROM {block_instances} WHERE blockname='hsmail' AND parentcontextid=?";
        $blockconfigdata = $DB->get_record_sql ( $sql, array (
                $context->id
        ) );
        if ( ! empty ( $blockconfigdata->configdata ) ) {
            $blockconfigdata = unserialize ( base64_decode ( $blockconfigdata->configdata ) );
            if ( isset ( $blockconfigdata->perpage ) ) {
                $this->perpage = $blockconfigdata->perpage;
            }
        }

        $this->page = optional_param ( 'page', 0, PARAM_INT ); // Page number.
    }

    /**
     * Create paging HTML 2014-04-18.
     * @param unknown $url
     * @return string
     */
    public function get_paging($url) {
        global $COURSE, $OUTPUT;
        $baseurl = new moodle_url ( $url, array (
                'id' => $COURSE->id
        ) );
        $pagingbar = new paging_bar ( $this->totalcount, $this->page, $this->perpage, $baseurl );
        return $OUTPUT->render ( $pagingbar );
    }

    /**
     * Acquire the currently registered job list.
     * @param number $flag
     */
    public function get_job_list($flag = 0) {
        global $DB, $COURSE;

        $sortorder = ($flag == 0) ? 'ASC' : 'DESC';
        $reservation = '';
        if ( $flag != 0 ) {
            $reservation = 'AND executeflag != 0';
            $orderinstantly = '';
        } else {
            $orderinstantly = 'bh.instantly DESC,';
        }

        // 2014-04-18 Set paging data as set.
        $this->set_paging_data ( $reservation, $flag );

        $sql = <<< SQL
select bh.id, bh.course, bh.category, bh.executedatetime,
bh.jobtitle,bh.mailtitle,bh.executeflag, bh.timecreated,
bh.createuser, bh.instantly, u.lastname, u.firstname
FROM {block_hsmail} AS bh
INNER JOIN {user} AS u ON bh.createuser = u.id
WHERE bh.course = ? AND bh.category = ? AND executeflag <= ? {$reservation}
ORDER BY {$orderinstantly} bh.executedatetime {$sortorder}
SQL;

        $list = $DB->get_records_sql ( $sql, array (
                $COURSE->id,
                $COURSE->category,
                $flag
        ), $this->page * $this->perpage, $this->perpage ); // 2014-04-16 Paging support.
        return $list;
    }

    /**
     * Return a delivered user.
     * @return NULL[]
     */
    public function get_sent_list() {
        global $DB;
        $sql = <<< SQL
SELECT hsmail,count(*) AS c FROM {block_hsmail_userlog} GROUP BY hsmail ORDER BY hsmail DESC
SQL;
        $ret = $DB->get_records_sql ( $sql );

        $list = array ();
        foreach ($ret as $value) {
            $list [$value->hsmail] = $value->c;
        }
        return $list;
    }

    /**
     * Return undelivered users.
     * @return NULL[]
     */
    public function get_send_list() {
        global $DB;
        // Retrieve the number of remaining mails from the queue.
        $sql = <<< SQL
SELECT hsmail,count(*) AS c FROM {block_hsmail_queue}
GROUP BY hsmail
SQL;
        $ret = $DB->get_records_sql ( $sql );
        $list = array ();
        foreach ($ret as $value) {
            $list [$value->hsmail] = $value->c;
        }
        return $list;
    }

    /**
     * Return the number of undelivered courses.
     * @param unknown $courseid
     * @return number
     */
    public function get_course_send_list($courseid) {
        global $DB;
        // Retrieve the number of remaining mails from the queue.
        $sql = <<< SQL
SELECT count(*) AS cnt FROM {block_hsmail_queue} AS T1
INNER JOIN {block_hsmail} AS T2 ON T1.hsmail=T2.id WHERE T2.course=?;
SQL;
        $ret = $DB->get_record_sql ( $sql, array (
                $courseid
        ) );

        if ( isset ( $ret->cnt ) ) {
            return $ret->cnt;
        } else {
            return 0;
        }
    }

    /**
     * Delivery start, return completion.
     */
    public function get_mail_start_end() {
        global $DB;
        $sql = <<< SQL
SELECT hsmail,min(timecreated) AS min, max(timecreated) AS max FROM {block_hsmail_userlog}
GROUP BY hsmail
ORDER BY hsmail DESC
SQL;
        $ret = $DB->get_records_sql ( $sql );
        $list = array ();
        foreach ($ret as $value) {
            $list [$value->hsmail] ['start'] = $value->min;
            $list [$value->hsmail] ['end'] = $value->max;
        }
        return $list;
    }

    /**
     * Retrieve mail list addressed to you.
     */
    public function get_mail_list() {
        global $DB, $COURSE, $USER;

        // 2014-04-18 Set paging data as set.
        $this->set_paging_data_mail_list ();

        $sql = <<< SQL
SELECT ul.*, bh.mailtitle, bh.mailbody, u.lastname, u.firstname
FROM {block_hsmail_userlog} AS ul
INNER JOIN {block_hsmail} AS bh ON ul.hsmail=bh.id
INNER JOIN {user} AS u ON bh.createuser=u.id
WHERE bh.course = ? AND bh.category = ? AND ul.userid=?
ORDER BY ul.id DESC
SQL;

        $list = $DB->get_records_sql ( $sql, array (
                $COURSE->id,
                $COURSE->category,
                $USER->id
        ), $this->page * $this->perpage, $this->perpage ); // 2014-04-16 Paging support.

        return $list;
    }

    /**
     * Set paging data as set.
     */
    public function set_paging_data_mail_list() {
        global $DB, $COURSE, $USER;

        // Total number acquisition.
        $sql = <<< SQL
SELECT COUNT(*) AS cnt
FROM {block_hsmail_userlog} AS ul
INNER JOIN {block_hsmail} AS bh ON ul.hsmail=bh.id
INNER JOIN {user} AS u ON bh.createuser=u.id
WHERE bh.course = ? AND bh.category = ? AND ul.userid=?
SQL;
        $datacount = $DB->get_record_sql ( $sql, array (
                $COURSE->id,
                $COURSE->category,
                $USER->id
        ) ); // Total number acquisition.
        $this->totalcount = $datacount->cnt;

        $context = context_course::instance ( $COURSE->id );

        // Acquire number of items per page.
        $sql = "SELECT configdata FROM {block_instances} WHERE blockname='hsmail' AND parentcontextid=?";
        $blockconfigdata = $DB->get_record_sql ( $sql, array (
                $context->id
        ) );
        if ( ! empty ( $blockconfigdata->configdata ) ) {
            $blockconfigdata = unserialize ( base64_decode ( $blockconfigdata->configdata ) );
            if ( isset ( $blockconfigdata->user_perpage ) ) {
                $this->perpage = $blockconfigdata->user_perpage;
            }
        }

        $this->page = optional_param ( 'page', 0, PARAM_INT ); // Page number.
    }

    /**
     * Retrieve mail list addressed to you.
     * @return mixed|boolean
     */
    public function get_mail_detail() {
        global $DB, $COURSE, $USER;

        $mailid = optional_param ( 'mailid', 0, PARAM_INT );

        $sql = <<< SQL
SELECT ul.*, bh.mailtitle, bh.mailbody, u.lastname, u.firstname
FROM {block_hsmail_userlog} AS ul
INNER JOIN {block_hsmail} AS bh ON ul.hsmail=bh.id
INNER JOIN {user} AS u ON bh.createuser=u.id
WHERE bh.id = ? AND bh.course = ? AND bh.category = ? AND ul.userid=?
SQL;

        $maildetail = $DB->get_record_sql ( $sql, array (
                $mailid,
                $COURSE->id,
                $COURSE->category,
                $USER->id
        ) );

        return $maildetail;
    }
}