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

namespace block_hsmail\event;
defined('MOODLE_INTERNAL') || die();

class mail_sent extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r'; // Only the following characters, c(reate), r(ead), u(pdate), d(elete).
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('event_mail_sent_desc', 'block_hsmail');
    }

    public function get_description() {
        return  get_string('event_mail_sent', 'block_hsmail', $this->other['sentmail']);
    }

}