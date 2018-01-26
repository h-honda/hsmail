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
 * @package   block_hsmail
 * @copyright 2013 Human Science CO., Ltd.  {@link http://www.science.co.jp}
 */
defined ( 'MOODLE_INTERNAL' ) || die ();
/**
 * Upgrade function.
 * @param unknown $oldversion
 * @return boolean
 */
function xmldb_block_hsmail_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager ();

    $result = true;

    if ( $oldversion < 2014061200 ) {

        // Define table ouj_apply_course_commit to be created.
        $table = new xmldb_table ( 'block_hsmail' );

        // Define field instantly to be added to block_hsmail.
        $table = new xmldb_table ( 'block_hsmail' );
        $field = new xmldb_field ( 'instantly', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'modifieduser' );

        // Conditionally launch add field instantly.
        if ( !$dbman->field_exists ( $table, $field ) ) {
            $dbman->add_field ( $table, $field );
        }

        // Define field instantly to be added to block_hsmail_queue.
        $table = new xmldb_table ( 'block_hsmail_queue' );
        $field = new xmldb_field ( 'instantly', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timemodified' );

        // Conditionally launch add field instantly.
        if ( !$dbman->field_exists ( $table, $field ) ) {
            $dbman->add_field ( $table, $field );
        }

        // Ouj_tsushin_admin savepoint reached.
        upgrade_block_savepoint ( true, 2014061200, 'hsmail' );
    }

    return $result;
}
