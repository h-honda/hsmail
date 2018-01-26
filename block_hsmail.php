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
 *
 * @package block_hsmail
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once( $CFG->dirroot.'/blocks/moodleblock.class.php' );

/**
 *
 * @author h-honda
 *
 */
class block_hsmail extends block_base {
    /**
     * init function
     */
    public function init() {
        $this->title = get_string ( 'pluginname', 'block_hsmail' );
    }

    /**
     * Effective range of block installation location.
     * {@inheritDoc}
     * @see block_base::applicable_formats()
     */
    public function applicable_formats() {
        return array (
                'site-index' => true,
                'course-view' => true,
                'course-view-social' => false
        );
    }

    /**
     * Get Content
     * {@inheritDoc}
     * @see block_base::get_content()
     */
    public function get_content() {
        global $COURSE, $USER;

        if ( $this->content !== null ) {
            return $this->content;
        }

        $text = '';
        $this->content = new stdClass ();
        $this->content->text = $text;

        if ( $USER->id != 0 ) {
            $context = context_course::instance ( $COURSE->id );
            if ( has_capability ( 'block/hsmail:addcondition', $context ) ) {
                $this->content->text = $text;
                $url = new moodle_url ( '/blocks/hsmail/add.php', array('id' => $COURSE->id, 'sesskey' => sesskey()) );
                $link = html_writer::link ( $url, get_string ( 'add', 'block_hsmail' ) );
                $this->content->text = $link . '<br />';
                $url = new moodle_url ( '/blocks/hsmail/jobsetting.php', array('id' => $COURSE->id, 'sesskey' => sesskey()) );
                $link = html_writer::link ( $url, get_string ( 'confirm', 'block_hsmail' ) );
                $this->content->text .= $link;
            } else if ( has_capability ( 'block/hsmail:viewmaillist', $context ) ) {
                $url = new moodle_url ( '/blocks/hsmail/maillist.php', array('id' => $COURSE->id, 'sesskey' => sesskey()) );
                $link = html_writer::link ( $url, get_string ( 'sentlist', 'block_hsmail' ) );
                $this->content->text = $link;
            }

            $this->content->footer = '';
        }
        return $this->content;
    }

    /**
     * Individual setting valid.
     * {@inheritDoc}
     * @see block_base::instance_allow_multiple()
     */
    public function instance_allow_multiple() {
        return false;
    }
    /**
     * Enable settings for each instance
     * {@inheritDoc}
     * @see block_base::instance_allow_config()
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Overall setting valid.
     * {@inheritDoc}
     * @see block_base::has_config()
     */
    public function has_config() {
        return true;
    }

    /**
     * Mail queue registration and transmission.
     * @throws moodle_exception
     * @return boolean
     */
    public function cron() {
        global $DB, $CFG, $USER, $COURSE;

        echo "start\n";
        require_once( $CFG->dirroot . '/blocks/hsmail/hsmail_lib.php' );
        // Generate hsmaillib object.
        $objhsmaillib = new hsmail_lib ();
        mtrace ( "hsmail target user listing..." );
        // Get from address.
        $mailfrom = get_config('core', 'supportemail');

        $now = date ( 'U' );
        // Get conditions.
        $sql = <<< SQL
SELECT * FROM {block_hsmail}
WHERE executeflag = 0 AND (executedatetime <= {$now} OR instantly = 1)
ORDER BY instantly DESC, executedatetime ASC
SQL;
        $course = $DB->get_records_sql ( $sql );

        reset ( $course );
        // Delete working table.
        $DB->delete_records ( 'block_hsmail_temp' );
        $addnum = 0;
        while ( list ( $key, $value ) = each ( $course ) ) {

            // Job status change in progress: changed to 2.
            $dataobject = new stdClass ();
            $dataobject->id = $value->id;
            $dataobject->executeflag = 2; // When editing / deletion is not possible at the time of accumulation in the queue.
            $DB->update_record ( 'block_hsmail', $dataobject );

            // Transaction from here.
            $transaction = null;
            try {
                $transaction = $DB->start_delegated_transaction ();

                // Delete working table.
                $DB->delete_records ( 'block_hsmail_temp' );
                if ( $value->course == 1 ) {
                    // When set at the site top.
                    $admins = get_admins (); // get site admin.
                    $tmpadmins = array ();
                    foreach ($admins as $admin) {
                        $tmpadmins [] = $admin->id;
                    }
                    $admins = implode ( ',', $tmpadmins );
                    $sql = <<< SQL
INSERT INTO {block_hsmail_temp}
(
SELECT u.id AS userid, u.email, u.firstname, u.lastname FROM {user} As u
WHERE u.id != 1 AND u.id NOT IN ({$admins}) AND u.deleted=0
ORDER BY u.id
)
SQL;
                } else {
                    // Register students to work table (student roll).
                    $sql = <<< SQL
INSERT INTO {block_hsmail_temp}
(
SELECT u.id AS userid, u.email, u.firstname, u.lastname, u.alternatename, u.middlename FROM {role_assignments} AS ra
INNER JOIN {context} AS c ON ra.contextid = c.id AND contextlevel = 50 AND instanceid = ?
INNER JOIN {user} AS u ON ra.userid = u.id
WHERE ra.roleid = 5
)
SQL;
                }

                $DB->execute ( $sql, array (
                        $value->course
                ) );

                // Get a list of conditions of the relevant course.
                $plan = $DB->get_records ( 'block_hsmail_plan', array (
                        'hsmail' => $value->id
                ) );

                foreach ($plan as $tmp) {
                    // Creating list of course participants.
                    foreach ($objhsmaillib->conditionfiles as $cf) {
                        if ( $tmp->plan != $cf ) {
                            continue;
                        }
                        require_once($CFG->dirroot . '/blocks/hsmail/conditions/' . $cf . '.php');
                        $objwork = new $cf ();
                        // Generate user acquisition SQL meeting conditions.
                        $planvalue = unserialize ( base64_decode ( $tmp->planvalue ) );
                        $tmpsql = $objwork->regist_users_sql ( $value->course, $planvalue );

                        // Refine to users who match the conditions.
                        try {
                            $tmpuser = $DB->get_records_sql ( $tmpsql );

                            reset( $tmpuser );
                            $idstmp = array();
                            foreach ( $tmpuser as $value2 ) {
                                $idstmp[] = $value2->userid;
                            }
                            $ids = implode(',', $idstmp);

                            if ( $ids == "" ) {
                                $sql = <<< SQL
DELETE FROM {block_hsmail_temp}
SQL;
                                $DB->execute( $sql );
                                break 2;
                            } else {
                                $sql = <<< SQL
DELETE FROM {block_hsmail_temp} WHERE userid NOT IN ({$ids})
SQL;
                                $DB->execute( $sql );
                            }
                        } catch ( Exception $e ) {
                            throw new moodle_exception ( $e->getMessage () );
                        }
                        $objwork = null;
                    }
                }

                // Add footer.
                $config = get_config ( 'block_hsmail' );
                if ( $config->footer != '' ) {
                    $value->mailbody .= "\n\r" . $config->footer;
                }

                // Queued users who meet the conditions.
                $sql = <<< SQL
INSERT INTO {block_hsmail_queue}
(hsmail, userid, timesend, title, body, mailfrom, mailto,timecreated, timemodified, instantly)
(SELECT
'{$value->id}' AS hsmail,
userid,
'{$value->executedatetime}' AS timesend,
? AS title,
? As body,
? AS mailfrom,
email AS mailto,
'{$now}' AS timecreated,
'{$now}' AS timemodified,
'{$value->instantly}'AS instantly
FROM {block_hsmail_temp})
SQL;
                $DB->execute ( $sql, array (
                        $value->mailtitle,
                        $value->mailbody,
                        $mailfrom
                ) );
                $dataobject = null;

                // Retrieve the number of e-mails to be added.
                $addnum += $DB->count_records( 'block_hsmail_temp' );

                $transaction->allow_commit ();

            } catch ( Exception $e ) {
                // Job status change in progress: changed to 0.
                $dataobject = new stdClass ();
                $dataobject->id = $value->id;
                $dataobject->executeflag = 1; // Make it in failure state.
                $DB->update_record ( 'block_hsmail', $dataobject );
                // Rollback.
                $transaction->rollback ( $e );
            }
        }

        // Mail registration log.
        if ( $addnum != 0 ) {
            // Output to log only when mail is registered in queue.
            $event = \block_hsmail\event\mail_added::create(array(
                    'context' => context_course::instance($COURSE->id),
                    'userid' => $USER->id,
                    'courseid' => $COURSE->id,
                    'other' => array( 'addmail' => $addnum )
            ));
            $event->trigger();
        }
        // Delete working table.
        $DB->delete_records ( 'block_hsmail_temp' );

        mtrace ( 'hsmail sending mail ... ' );

        $transaction = null;
        try {
            require_once( 'hsmaillib_wrapper.php' );
            $mailobj = new hsmaillib_wrapper ();

            // Transaction.
            $transaction = $DB->start_delegated_transaction ();
            // Get number of users sent at once.
            $config = get_config ( 'block_hsmail' );
            $mailmax = (int)($config->mailmax); // Number of simultaneous mail transmission.
            $ignoremailmax = $config->ignore_mailmax; // Ignore the number of simultaneous mail transmission at immediate delivery.

            // Ignore the number of simultaneous mail transmissions at immediate delivery ON.
            if ( $ignoremailmax == 1 ) {
                // Instant delivery email.
                $sql = "SELECT * FROM {block_hsmail_queue} WHERE instantly=1";
                $tagetmailsinstantly = $DB->get_records_sql ( $sql );

                // Delete mail from queue.
                $sql = "DELETE FROM {block_hsmail_queue} WHERE instantly=1";
                $DB->execute ( $sql, array () );

                // Regular mail.
                // Retrieve the specified number of queues.
                $now = (int)(date ( 'U' ));
                $sql = <<< SQL
SELECT * FROM {block_hsmail_queue}
WHERE
timesend <= ? AND instantly = 0
ORDER BY id ASC
SQL;
                $tagetmails = $DB->get_records_sql ( $sql, array ( $now ), 0, $mailmax );

                // Delete mail from queue.
                reset( $tagetmails );
                foreach ( $tagetmails as $value3 ) {
                    $DB->delete_records( 'block_hsmail_queue', array ( 'id' => $value3->id ) );
                }

                $tagetmails = array_merge ( $tagetmailsinstantly, $tagetmails ); // Immediate delivery mail and regular mail merge.
                // Ignore the number of simultaneous mail transmissions at immediate delivery OFF.
            } else {
                // Retrieve the specified number of queues.
                $now = (int)(date ( 'U' ));
                $sql = <<< SQL
SELECT * FROM {block_hsmail_queue}
WHERE
timesend <= ? OR instantly = 1
ORDER BY instantly DESC, id ASC
SQL;
                $tagetmails = $DB->get_records_sql ( $sql, array ( $now ), 0, $mailmax );

                // Delete mail from queue.
                reset( $tagetmails );
                foreach ($tagetmails as $value4) {
                    $DB->delete_records( 'block_hsmail_queue', array ( 'id' => $value4->id ) );
                }
            }

            if ( count ( $tagetmails ) == 0 ) {
                $transaction->allow_commit ();
                return true; // When there is no outgoing e-mail End processing.
            }
            // Transmission processing.
            $sentmail = 0;
            reset ( $tagetmails );
            while ( list ( $key, $value ) = each ( $tagetmails ) ) {
                // Mail generation.
                $recipients = $value->mailto;
                // Email sender display name.
                $headers ['FromName'] = '';
                $headers ['From'] = $value->mailfrom;
                $headers ['To'] = $value->mailto;
                $headers ['Subject'] = $value->title;

                // Place folder processing.
                $body = $this->conv_placeholder ( $value );
                // Send.
                $mailobj->send ( $recipients, $headers, $body );
                $sentmail++;

                // Create transmission log.
                $retlogid = $DB->get_record ( 'block_hsmail_log', array (
                        'hsmail' => $value->hsmail
                ) );
                if ( $retlogid === false ) {
                    // Add to log table.
                    $dataobject = new stdClass ();
                    $dataobject->hsmail = $value->hsmail;
                    $dataobject->timecreated = $now;
                    $dataobject->timemodified = $now;
                    $logid = $DB->insert_record ( 'block_hsmail_log', $dataobject );
                    $dataobject = null;
                } else {
                    $logid = $retlogid->id;
                }
                $dataobject = new stdClass ();
                $dataobject->hsmaillog = $logid;
                $dataobject->hsmail = $value->hsmail;
                $dataobject->userid = $value->userid;
                $dataobject->timecreated = $now;
                $dataobject->timemodified = $now;
                $DB->insert_record ( 'block_hsmail_userlog', $dataobject );
                $dataobject = null;
            }
            mtrace ( 'hsmail sent mail ' );

            // Commit.
            $transaction->allow_commit ();
            mtrace ( 'hsmail processing end.' );
            // Logging.
            $event = \block_hsmail\event\mail_sent::create(array(
                    'context' => context_course::instance($COURSE->id),
                    'userid' => $USER->id,
                    'courseid' => $COURSE->id,
                    'other' => array('sentmail' => $sentmail)
            ));
            $event->trigger();

        } catch ( Exception $e ) {
            mtrace ( $e->getMessage () );
            $transaction->rollback ( $e );
        }
        return true;
    }

    /**
     * Place folder processing.
     * @param unknown $value
     * @return mixed
     */
    public function conv_placeholder($value) {
        global $DB, $CFG;

        $body = $value->body;

        // User name.
        $sql = "SELECT firstname, lastname FROM {user} WHERE id=?";
        $userinfo = $DB->get_record_sql ( $sql, array (
                $value->userid
        ) );
        $body = str_replace ( '[[user_name]]', "{$userinfo->lastname} {$userinfo->firstname}", $body );

        // Course name + URL.
        $sql = <<< SQL
SELECT T2.id, T2.fullname FROM {block_hsmail} AS T1
INNER JOIN {course} AS T2 ON T1.course=T2.id WHERE T1.id=?
SQL;
        $courseinfo = $DB->get_record_sql ( $sql, array (
                $value->hsmail
        ) );
        $url = $CFG->wwwroot . '/course/view.php?id=' . $courseinfo->id;
        $body = str_replace ( '[[course_name]]', $courseinfo->fullname . ' ' . $url, $body );

        return $body;
    }
}
