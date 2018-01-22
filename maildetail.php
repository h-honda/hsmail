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

require_once( dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php' );

$id = optional_param ( 'id', 0, PARAM_INT );
$mailid = optional_param ( 'mailid', 0, PARAM_INT );
$page = optional_param ( 'page', 0, PARAM_INT );

if ( $id ) {
    if (! $course = $DB->get_record ( 'course', array (
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
if ( ! has_capability ( 'block/hsmail:viewmaillist', $context ) ) {
    throw new moodle_exception ( 'You dont have capability' );
}
// Display processing.
$PAGE->set_url ( '/blocks/hsmail/maildetail.php', array (
        'id' => $id
) ); // Set the URL of this file.
$PAGE->set_title ( get_string ( 'meildetail', 'block_hsmail' ) ); // Title displayed in browser's title bar.
$PAGE->set_heading ( $course->shortname ); // String to display in header.
$PAGE->set_pagelayout ( 'incourse' );
$PAGE->navbar->add ( get_string ( 'sentlist', 'block_hsmail' ), "maillist.php?id=$id" ); // Add item to header navigation.

// Form generation.
require_once( 'lib.php' );
$mform = new hsmail_maildetail_form ();

// Header output.
echo $OUTPUT->header ();

// Replace the following lines with you own code.
echo $OUTPUT->heading ( get_string ( 'meildetail', 'block_hsmail' ) ); // Title of main area.

echo $OUTPUT->container_start ( 'hsmail-detail' );
$mform->display ();

$url = new moodle_url ( "/blocks/hsmail/maillist.php?id={$COURSE->id}&page={$page}" );
echo $OUTPUT->single_button ( $url, get_string ( 'back' ), 'get' );

echo $OUTPUT->container_end ();
echo $OUTPUT->footer ();