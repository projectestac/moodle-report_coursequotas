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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Report coursequotas tasks
 *
 * @package    report_coursequotas
 * @copyright  TICxCAT <info@ticxcat.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// List of tasks.
$tasks = array(
    array(
        'classname' => 'report_coursequotas\task\course_sizes',
        'blocking' => 0,
        'minute' => '1',
        'hour' => '*/12',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'report_coursequotas\task\chart_info',
        'blocking' => 0,
        'minute' => '1',
        'hour' => '*/12',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'report_coursequotas\task\category_sizes',
        'blocking' => 0,
        'minute' => '1',
        'hour' => '*/12',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    )
);