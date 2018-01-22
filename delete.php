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

require_once(dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php');

$id = optional_param ( 'id', 0, PARAM_INT );
$jobid = required_param ( 'jobid', PARAM_INT );

if ( $id ) {
    if ( !$course = $DB->get_record ( 'course', array (
            'id' => $id
    ) )) {
        error ( 'Course is misconfigured' );
    }
} else {
    error ( 'Course ID error' );
}

require_sesskey();
require_login ( $course );

$context = context_course::instance ( $id );
if ( !has_capability ( 'block/hsmail:addcondition', $context ) ) {
    throw new moodle_exception ( 'You dont have capability' );
}

// Delete.
require_once( 'hsmail_lib.php' );
$obj = new hsmail_lib ();
$obj->delete_job ( $jobid );
redirect ( new moodle_url ( '/blocks/hsmail/jobsetting.php', array (
        'id' => $id, 'sesskey' => sesskey()
) ) );