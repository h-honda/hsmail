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
 * Admin setting
 * @package block_hsmail
 * @copyright 2013 Human Science Co., Ltd. {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ( $ADMIN->fulltree ) {
    // Setting form.
    $settings->add ( new admin_setting_heading ( 'headerconfig', get_string ( 'headerconfig', 'block_hsmail' ), '' ) );

    $settings->add ( new admin_setting_configtext ( 'block_hsmail/mailmax', get_string ( 'mailmax', 'block_hsmail' ),
            get_string ( 'mailmax_desc', 'block_hsmail' ), '100', PARAM_INT ) );
    $settings->add ( new admin_setting_configcheckbox ( 'block_hsmail/ignore_mailmax',
            get_string ( 'ignore_mailmax', 'block_hsmail' ),
            get_string ( 'ignore_mailmax_desc', 'block_hsmail' ), 0 ) );
    $settings->add ( new admin_setting_configtextarea ( 'block_hsmail/footer', get_string ( 'footer', 'block_hsmail' ),
            get_string ( 'footer_desc', 'block_hsmail' ), '' ) );
}