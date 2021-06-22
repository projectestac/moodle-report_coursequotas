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
 * @copyright  2021 Agora Development Team (https://github.com/projectestac/agora)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../config.php';
require_once $CFG->libdir . '/adminlib.php';
require_once $CFG->dirroot . '/report/coursequotas/lib/local.lib.php';
require_once $CFG->dirroot . '/report/coursequotas/lib/util.lib.php';
require_once $CFG->dirroot . '/report/coursequotas/lib/calculate.lib.php';
require_once $CFG->dirroot . '/report/coursequotas/constants.php';

admin_externalpage_setup(REPORT_COURSEQUOTAS_NAME, '', null, '/report/coursequotas/index.php', array('pagelayout' => REPORT_COURSEQUOTAS_REPORTSTRING));

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');

// Check restricted hour
function_exists('require_not_rush_hour') && require_not_rush_hour();


// Get block size. Block size is the allocation unit of space in the file system. So if the block size is 4096, that means
//  that a file of 1 byte, uses 4096 bytes in the file system
$tempdir = isset($CFG->tempdir) ? $CFG->tempdir : $CFG->dataroot . '/temp';
$trashdir = isset($CFG->trashdir) ? $CFG->trashdir : $CFG->dataroot . '/trashdir';

$tempfile = $tempdir . '/test.txt';
file_put_contents($tempfile, REPORT_COMPONENTNAME);
$block_size = intval(exec('du ' . $tempfile . " | awk '{print $1}'")) * 1024;


// Update table with categories information
$categories = $DB->get_records('course_categories', [], 'depth, id', 'id');

foreach ($categories as $catid => $record) {
    $categoryContext = \context_coursecat::instance($catid);
    $categorySize = report_coursequotas_get_contextsize($categoryContext, $block_size);

    // Update or insert record
    $dataObject = $DB->get_record(CATEGORYSIZE_TABLENAME, [CATEGORYSIZE_FIELDCATEGORYID => $catid], '*', IGNORE_MULTIPLE);

    if ($dataObject) {
        $dataObject->{CATEGORYSIZE_FIELDQUOTA} = $categorySize;
        $DB->update_record(CATEGORYSIZE_TABLENAME, $dataObject);
    } else {
        $dataObject = new \stdClass();
        $dataObject->{CATEGORYSIZE_FIELDCATEGORYID} = $catid;
        $dataObject->{CATEGORYSIZE_FIELDQUOTA} = $categorySize;
        $DB->insert_record(CATEGORYSIZE_TABLENAME, $dataObject);
    }
}


// Update table with course information

$courses = $DB->get_records('course', null, '', 'id');
foreach ($courses as $course_id => $course) {
    $course_context = \context_course::instance($course_id);
    $course_size = report_coursequotas_get_contextsize($course_context, $block_size);

    $data_object = $DB->get_record(COURSESIZE_TABLENAME, [COURSESIZE_FIELDCOURSEID => $course_id], '*', IGNORE_MULTIPLE);

    if ($data_object) { // Update or insert record
        $data_object->{COURSESIZE_FIELDQUOTA} = $course_size;
        $DB->update_record(COURSESIZE_TABLENAME, $data_object);
    } else {
        $data_object = new \stdClass();
        $data_object->{COURSESIZE_FIELDCOURSEID} = $course_id;
        $data_object->{COURSESIZE_FIELDQUOTA} = $course_size;
        $DB->insert_record(COURSESIZE_TABLENAME, $data_object);
    }
}


// Update chart information

// Calculate backup usage
set_config('backup_usage', get_coursequotas_filesize(get_backup_where_sql(), '', $block_size), REPORT_COMPONENTNAME);

// Calculate course usage
$syscontext = \context_system::instance();
$params = [$syscontext->depth + 1, CONTEXT_COURSECAT, $syscontext->path . '/%'];

$sql = "SELECT id, path
        FROM {context}
        WHERE depth = ? AND contextlevel = ? AND path LIKE ?";
$contexts = $DB->get_records_sql_menu($sql, $params);

$sitecourse = $DB->get_field('course', 'id', ['category' => 0]);
$context_course = \context_course::instance($sitecourse);
$contexts[$context_course->id] = $context_course->path;

$sql_parts = [];
foreach ($contexts as $contexid => $path) {
    $sqlparts[] = "(f.contextid = c.id AND c.path LIKE '$path/%')";
}
$sql_parts[] = 'f.contextid IN (' . implode(',', array_keys($contexts)) . ')';

$sql_where = implode(' OR ', $sql_parts);

$sql_where = "($sql_where) AND (f.component != 'backup' OR (f.filearea != 'activity' AND f.filearea != 'course' AND f.filearea != 'automated'))"; // Exclude backup files.

$get_filesize = [
    [
        REPORT_COURSEQUOTAS_WHERE_STRING => $sql_where,
        REPORT_COURSEQUOTAS_TABLES_STRING => '{context} c',
        REPORT_COURSEQUOTAS_CONFIGNAME_STRING => 'course_usage',
    ],
    [
        REPORT_COURSEQUOTAS_WHERE_STRING => "component = 'user' AND filearea != 'backup'",
        REPORT_COURSEQUOTAS_TABLES_STRING => '',
        REPORT_COURSEQUOTAS_CONFIGNAME_STRING => 'user_usage',
    ],
    [
        REPORT_COURSEQUOTAS_WHERE_STRING => "(f.component = 'mod_hvp' AND f.filearea = 'libraries') OR (f.component = 'core_h5p' AND f.filearea = 'libraries')",
        REPORT_COURSEQUOTAS_TABLES_STRING => '',
        REPORT_COURSEQUOTAS_CONFIGNAME_STRING => 'h5plib_usage',
    ]
];

foreach ($get_filesize as $item) {
    set_config(
        $item[REPORT_COURSEQUOTAS_CONFIGNAME_STRING],
        get_coursequotas_filesize($item[REPORT_COURSEQUOTAS_WHERE_STRING], $item[REPORT_COURSEQUOTAS_TABLES_STRING], $block_size),
        REPORT_COMPONENTNAME
    );
}

$get_directory_size = [
    [
        REPORT_COURSEQUOTAS_DIRECTORY_STRING => $CFG->dataroot . '/repository/',
        REPORT_COURSEQUOTAS_CONFIGNAME_STRING => 'repositories_usage',
    ],
    [
        REPORT_COURSEQUOTAS_DIRECTORY_STRING => $tempdir,
        REPORT_COURSEQUOTAS_CONFIGNAME_STRING => 'tempdir_usage',
    ],
    [
        REPORT_COURSEQUOTAS_DIRECTORY_STRING => $trashdir,
        REPORT_COURSEQUOTAS_CONFIGNAME_STRING => 'trashdir_usage',
    ]
];

foreach ($get_directory_size as $item) {
    set_config(
        $item[REPORT_COURSEQUOTAS_CONFIGNAME_STRING],
        report_coursequotas_get_directory_size($item[REPORT_COURSEQUOTAS_DIRECTORY_STRING]),
        REPORT_COMPONENTNAME
    );
}

// Save timestamp
set_config('updated', time(), REPORT_COMPONENTNAME);

// Back to main page
redirect(
    new moodle_url('/report/coursequotas/index.php'),
    get_string('quotas_updated', REPORT_COMPONENTNAME),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
