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
 * HS mail language packe English
 * @package   block_hsmail
 * @copyright 2013 Human Science CO., Ltd.  {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['hsmail:addinstance'] = 'Add HS mail';
$string['hsmail:addcondition'] = 'Condition add authority';
$string['hsmail:viewmaillist'] = 'View mail list';

$string['condition1'] = 'E-mail transmitting conditions';
$string['hsmail_settings'] = 'HS mail settings';

$string['pluginname'] = 'HS mail';
$string['headerconfig'] = 'Config';
$string['descconfig'] = 'Config.';

$string['jobs_message'] = '{$a->num} job(s) that are registered';
$string['blocksettings'] = 'Mail transmission condition setting';
$string['transmissiontiming'] = 'Transmission timing';
$string['assignment'] = 'Sent date and time';
$string['now'] = 'now';
$string['send_repeat'] = 'Repeat Timing(s)';

$string['jobs'] = 'Send job';

$string['add'] = 'Distribution mail registration ';
$string['confirm'] = 'Distribution reserved mail confirm';

$string['sent_confirm'] = 'Distribution sent mail confirm';
$string['perpage'] = 'Items to show';
$string['user_perpage'] = 'Items to show (Student)';
$string['err_already'] = 'It is the email that was already sent';
$string['err_planid'] = 'Cannot acquire plan Id';
$string['sentlist'] = 'Distribution sent mail list';
$string['joblist'] = 'Distribution reserved mail list';
$string['mailmax'] = 'Simultaneous email transmission number';
$string['mailmax_desc'] = 'Set the number of e-mails to transmit in one cron';
$string['delete_confirm'] = 'Do you delete it?';
$string['head_create'] = 'Mail create date';
$string['head_title'] = 'Condition title';
$string['head_mail'] = 'Mail title';
$string['head_user'] = 'Author';
$string['head_sent'] = 'Delivery due date';
$string['head_action'] = 'Action';
$string['head_sent_count'] = 'The number of the users targeted for delivery';
$string['head_sent_start'] = 'Delivery starting date';
$string['head_sent_end'] = 'Delivery completion date';
$string['queue_count'] = 'The number of e-mails during the transmission processing ({$a})';
$string['queue_count_total'] = 'The total number of e-mails during the transmission processing (site)';
$string['mailbody'] = 'Mail body';
$string['mailbody_help'] = '[[course_name]] : course name<br>[[user_name]] : user name';
$string['detail_mail'] = 'Transmission contents';
$string['detail_condition'] = 'Delivery condition';
$string['date_time_selector'] = 'Delivery start date';
$string['target'] = ' Delivery target';
$string['target_s'] = 'Only as for the applicant';
$string['target_a'] = 'All users';
$string['target_c'] = 'Course enrolled users';
$string['complete'] = 'Course Completion status';
$string['complete_c'] = 'Course completed';
$string['complete_i'] = 'Course incompleted';
$string['ignore_mailmax'] = 'Ignore "Simultaneous email transmission number" upon instantly delivery';
$string['ignore_mailmax_desc'] = 'Send at once all mail by ignoring the setting of "Simultaneous email transmission number" upon instantly delivery';
$string['instantly'] = 'Instantly delivery';
$string['meildetail'] = 'Mail detail';

$string['quizcomplete'] = 'quiz complete status';
$string['quizcomplete_c'] = 'Quiz completed';
$string['quizcomplete_i'] = 'Quiz incompleted';
$string['quizname'] = 'quiz name';
$string['quizcomplete_error'] = 'You have to select some quizes';

$string['assigncomplete'] = 'assign complete status';
$string['assigncomplete_c'] = 'Assign completed';
$string['assigncomplete_i'] = 'Assign incompleted';
$string['assignname'] = 'assign name';
$string['assigncomplete_error'] = 'You have to select some assigns';

$string['forumcomplete'] = 'assign complete status';
$string['forumcomplete_c'] = 'Forum completed';
$string['forumcomplete_i'] = 'Forum incompleted';
$string['forumname'] = 'forum name';
$string['forumcomplete_error'] = 'You have to select some forums';

$string['coursedisaccess'] = 'how many days have not you accessed the course';

$string['footer'] = 'footer';
$string['footer_desc'] = 'Finally it will be inserted in all of the mail body';

$string['event_mail_added_desc'] = 'Register mail in queue';
$string['event_mail_added'] = 'Register {$a} e-mails in queue';
$string['event_mail_sent_desc'] = 'hsmail sent';
$string['event_mail_sent'] = 'sent {$a} e-mails';

$string['task_addcue_sentmail'] = 'Mail add queue & mail transmission schedule';
$string['days'] = 'day';