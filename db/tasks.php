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

$tasks = array(
        array(
                'classname' => 'block_hsmail\task\addcue_sentmail',
                'blocking' => 0,
                'minute' => '*/5',
                'hour' => '*',
                'day' => '*',
                'dayofweek' => '*',
                'month' => '*'
        )
);