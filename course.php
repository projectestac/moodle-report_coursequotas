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
 * Coursequotas report
 *
 * @package    report
 * @subpackage coursequotas
 * @copyright  TICxCAT <info@ticxcat.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/report/coursequotas/locallib.php');
require_once($CFG->dirroot.'/report/coursequotas/constants.php');
require_once($CFG->dirroot.'/report/coursequotas/classes/output/coursequotas_coursesize.php');

admin_externalpage_setup(REPORT_COURSEQUOTAS_NAME, '', null, '/report/coursequotas/course.php', array('pagelayout' => REPORT_COURSEQUOTAS_REPORTSTRING));
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
echo $OUTPUT->header();

// Check restricted hour
function_exists('require_not_rush_hour') && require_not_rush_hour();

$table = new \report_coursequotas\output\coursequotas_coursesize(COURSESIZE_TABLENAME);
$backuptab = '';
$backupusage = report_coursequotas_format_size(intval(get_config(REPORT_COMPONENTNAME, 'backup_usage')));
if ($backupusage->bytes > 0) {
    $backuptab = '<li><a href="'.$CFG->wwwroot.'/report/coursequotas/filemanager.php?backups=true&sort=filesize&dir=DESC">' . get_string('backups', 'report_coursequotas') . '</a></li>';
}
echo '<div id="coursequotas">
        <ul  class="nav nav-tabs">
            <li><a href="index.php">' . get_string('total_data', 'report_coursequotas') . '</a></li>
            <li><a href="category.php">' . get_string('category_data', 'report_coursequotas') . '</a></li>
            <li class="active"><a href="course.php" >' . get_string('larger_courses', 'report_coursequotas') . '</a></li>
            ' . $backuptab . '
        </ul>
        <div>';
$table->out(10, true);
echo '</div>';

echo $OUTPUT->footer();