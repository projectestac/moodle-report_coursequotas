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
 * Calculate all course sizes and related info.
 *
 * @package    report_coursequotas
 * @copyright  TICxCAT <info@ticxcat.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_coursequotas\task;

/**
 * Calculate all course sizes and related info.
 */
class course_sizes extends \core\task\scheduled_task {

	/**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('name_task_course_sizes', 'report_coursequotas');
    }

    /**
     * Performs the task
     */
    public function execute() {
        global $CFG, $DB;
        include_once($CFG->dirroot.'/report/coursequotas/constants.php');
        require_once($CFG->dirroot.'/report/coursequotas/locallib.php');

        $courses = $DB->get_records('course', null, '', 'id, fullname, visible, category');
        foreach ($courses as $courseId => $course) {
            $courseContext = \context_course::instance($courseId);
            $courseSize = report_coursequotas_get_contextsize($courseContext);
            
            // Update or insert record
            $dataObject = $DB->get_record(COURSESIZE_TABLENAME, array(COURSESIZE_FIELDCOURSEID=>$courseId), '*', IGNORE_MULTIPLE);
            if ($dataObject) {
                $dataObject->{COURSESIZE_FIELDQUOTA} = $courseSize;
                $DB->update_record(COURSESIZE_TABLENAME, $dataObject);
            } else {
                $dataObject = new \stdClass();
                $dataObject->{COURSESIZE_FIELDCOURSEID} = $courseId;
                $dataObject->{COURSESIZE_FIELDQUOTA} = $courseSize;
                $DB->insert_record(COURSESIZE_TABLENAME, $dataObject);
            }  
        }

        mtrace('report_coursequotas -> course_sizes task -> Completed: '.count($courses).' Courses reviewed');
    }

}