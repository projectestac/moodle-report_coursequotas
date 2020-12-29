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
 * Calculate the amount of bytes used in course and users backups.
 * Component equal to backup means "course level backup".
 * filearea equal to backup means "user level backup" which is not associated to any course.
 *
 * @package    report_coursequotas
 * @copyright  TICxCAT <info@ticxcat.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_coursequotas\task;

/**
 * Calculate all course sizes and related info.
 */
class chart_info extends \core\task\scheduled_task {

	/**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('name_task_chart_info', 'report_coursequotas');
    }

    /**
     * Performs the task
     */
    public function execute() {
        global $CFG, $DB;
        include_once($CFG->dirroot.'/report/coursequotas/constants.php');
        require_once($CFG->dirroot.'/report/coursequotas/locallib.php');

        // Calculate backup usage
        $size = get_coursequotas_filesize(get_backup_where_sql());
        set_config('backup_usage', $size, REPORT_COMPONENTNAME);

        mtrace('report_coursequotas -> backup_usage: '.$size.' '.REPORT_COURSEQUOTAS_BYTES_STRING);
        // End calculate backup usage

        // Calculate course usage
        $syscontext = \context_system::instance();
        $sql = "SELECT id, path
                  FROM {context}
                 WHERE depth = ? AND contextlevel = ? AND path LIKE ?";
        $params = array($syscontext->depth + 1, CONTEXT_COURSECAT, $syscontext->path.'/%');
        $contexts = $DB->get_records_sql_menu($sql, $params);

        $sitecourse = $DB->get_field('course', 'id', array('category' => 0));
        $context = \context_course::instance($sitecourse);
        $contexts[$context->id] = $context->path;

        $sqlparts = array();
        foreach ($contexts as $contexid => $path) {
            $sqlparts[] = "(f.contextid = c.id AND c.path like '$path/%')";
        }
        $sqlparts[] = 'f.contextid IN ('.implode(',', array_keys($contexts)).')';

        $sql = implode(' OR ', $sqlparts);

        // Exclude backup files.
        $sql = "($sql) AND (f.component != 'backup' OR (f.filearea != 'activity' AND f.filearea != 'course' AND f.filearea != 'automated'))";

        // Calculate size of all the files inside the course avoiding duplicates.
        $size = get_coursequotas_filesize($sql, "{context} c");

        set_config('course_usage', $size, REPORT_COMPONENTNAME);
        mtrace('report_coursequotas -> course_usage: '.$size.' '.REPORT_COURSEQUOTAS_BYTES_STRING);
        // End calculate course usage

        // Calculate quota used by users.
        $size = get_coursequotas_filesize("component = 'user' AND filearea != 'backup'");
        set_config('user_usage', $size, REPORT_COMPONENTNAME);
        mtrace('report_coursequotas -> user_usage: '.$size.' '.REPORT_COURSEQUOTAS_BYTES_STRING);
        // End calculate quota used by users.

        // Calculate quota used in H5P libraries.
        $size = get_coursequotas_filesize("(f.component = 'mod_hvp' AND f.filearea = 'libraries')");
        set_config('h5plib_usage', $size, REPORT_COMPONENTNAME);
        mtrace('report_coursequotas -> h5plib_usage: '.$size.' '.REPORT_COURSEQUOTAS_BYTES_STRING);
        // End calculate quota used in H5P libraries.

        // Calculate quota used in repositories.
        $size = report_coursequotas_get_directory_size($CFG->dataroot . '/repository/');
        set_config('repositories_usage', $size, REPORT_COMPONENTNAME);
        mtrace('report_coursequotas -> repositories_usage: '.$size.' '.REPORT_COURSEQUOTAS_BYTES_STRING);
        // End calculate quota used in repositories.

        // Calculate quota used in files in temp directories.
        $tempdir = isset($CFG->tempdir) ? $CFG->tempdir : $CFG->dataroot.'/temp';
        $size = report_coursequotas_get_directory_size($tempdir);
        set_config('tempdir_usage', $size, REPORT_COMPONENTNAME);
        mtrace('report_coursequotas -> tempdir_usage: '.$size.' '.REPORT_COURSEQUOTAS_BYTES_STRING);
        // End calculate quota used in files in temp directories.

        // Calculate quota used in files in trash directories.
        $trashdir = isset($CFG->trashdir) ? $CFG->trashdir : $CFG->dataroot.'/trashdir';
        $size = report_coursequotas_get_directory_size($trashdir);
        set_config('trashdir_usage', $size, REPORT_COMPONENTNAME);
        mtrace('report_coursequotas -> trashdir_usage: '.$size.' '.REPORT_COURSEQUOTAS_BYTES_STRING);
        // End calculate quota used in files in trash directories.

        mtrace('report_coursequotas -> chart_info task completed');
    }

}