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
namespace block_hsmail\event;
defined('MOODLE_INTERNAL') || die();

/**
 *
 * @author h-honda
 *
 */
class mail_added extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c'; // Only the following characters, c(reate), r(ead), u(pdate), d(elete).
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }
    /**
     * plugin name
     * @return string
     */
    public static function get_name() {
        return get_string('event_mail_added_desc', 'block_hsmail');
    }

    /**
     * Description
     * {@inheritDoc}
     * @see \core\event\base::get_description()
     */
    public function get_description() {
        return  get_string('event_mail_added', 'block_hsmail', $this->other['addmail']);
    }

}