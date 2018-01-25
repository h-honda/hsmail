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
namespace block_hsmail\task;
defined('MOODLE_INTERNAL') || die();

/**
 *
 * @author h-honda
 *
 */
class addcue_sentmail extends \core\task\scheduled_task {
    /**
     * Get Block name
     * {@inheritDoc}
     * @see \core\task\scheduled_task::get_name()
     */
    public function get_name() {
        return get_string('task_addcue_sentmail', 'block_hsmail');
    }

    /**
     * Execute task
     * {@inheritDoc}
     * @see \core\task\task_base::execute()
     */
    public function execute() {
        global $CFG;
        require_once( $CFG->dirroot. '/blocks/hsmail/block_hsmail.php');
        $obj = new \block_hsmail();
        $obj->cron();
    }
}