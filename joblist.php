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
 * Job List
 * @package   block_hsmail
 * @copyright 2013 Human Science CO., Ltd.  {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php');

$id = optional_param ( 'id', 0, PARAM_INT );
$clicktype = optional_param ( 'click_button', 0, PARAM_INT );

if ( $id ) {
    if ( ! $course = $DB->get_record ( 'course', array (
            'id' => $id
    ) )) {
        error ( 'Course is misconfigured' );
    }
} else {
    error ( 'Course ID error' );
}

require_sesskey();
require_login ( $course );
// Authority check.
$context = context_course::instance ( $id );
if ( !has_capability ( 'block/hsmail:addcondition', $context ) ) {
    throw new moodle_exception ( 'You dont have capability' );
}

// Display processing.
$PAGE->set_url ( '/blocks/hsmail/jobsetting.php', array (
        'id' => $id,
        'sesskey' => sesskey()
) ); // Set the URL of this file.
$PAGE->set_title ( get_string( 'hsmail_settings', 'block_hsmail' ) ); // Title displayed in browser's title bar.
$PAGE->set_heading ( $course->shortname ); // String to display in header.
$PAGE->set_pagelayout ( 'incourse' );
$PAGE->navbar->add ( get_string( 'hsmail_settings', 'block_hsmail' ),
        "?id=$id&sesskey=".sesskey() ); // Add item to header navigation.

// Read the current registration Job.
// Form generation.
require_once( 'lib.php' );
$mform = new hsmail_form ();

// Header output.
echo $OUTPUT->header ();

// Replace the following lines with you own code.
echo $OUTPUT->heading ( get_string( 'hsmail_settings', 'block_hsmail' ) ); // Title of main area.

$mform->display ();

echo $OUTPUT->footer ();