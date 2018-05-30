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
 * Edit form
 * @package   block_hsmail
 * @copyright 2013 Human Science CO., Ltd.  {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined ( 'MOODLE_INTERNAL' ) || die ();
/**
 * Block hsmail edit form
 * @author h-honda
 * @copyright 2013 Human Science CO., Ltd.  {@link http://www.science.co.jp}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_hsmail_edit_form extends block_edit_form {
    /**
     * Definistion
     * {@inheritDoc}
     * @see block_edit_form::specific_definition()
     * @param form $mform
     */
    protected function specific_definition($mform) {
        $mform->addElement ( 'header', 'configheader', get_string ( 'blocksettings', 'block' ) );
        $mform->addElement ( 'text', 'config_perpage', get_string ( 'perpage', 'block_hsmail' ), array (
                'size' => 2
        ) );
        $mform->setType ( 'config_perpage', PARAM_INT );
        $mform->setDefault ( 'config_perpage', 20 );
        $mform->addRule ( 'config_perpage', null, 'numeric', null, 'client' );

        $mform->addElement ( 'text', 'config_user_perpage', get_string ( 'user_perpage', 'block_hsmail' ), array (
                'size' => 2
        ) );
        $mform->setType ( 'config_user_perpage', PARAM_INT );
        $mform->setDefault ( 'config_user_perpage', 20 );
        $mform->addRule ( 'config_user_perpage', null, 'numeric', null, 'client' );
    }
}
