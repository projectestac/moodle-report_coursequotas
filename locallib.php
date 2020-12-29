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
 * Coursequotas report library
 *
 * @package    report
 * @subpackage coursequotas
 * @copyright  TICxCAT <info@ticxcat.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function report_coursequotas_diskinfo($chartinfo) {
    global $CFG;

    $hasinfo = function_exists('is_agora') && is_agora() && function_exists('getDiskInfo');
    $info = false;

    if ($hasinfo) {
        // Get diskSpace and diskConsume from Agoraportal (might be out-of-date).
        $tempinfo = getDiskInfo($CFG->dnscentre, 'moodle2');

        $info = new StdClass();
        $info->space = round($tempinfo['diskSpace']); // In MB.
        $info->consumed = $tempinfo['diskConsume'] / 1024; // Originally in kB.

        // If disk info is not avalaible...
        if ($info->consumed == 0) {
            $info->consumed += report_coursequotas_get_charinfo_total($chartinfo);
        }
        $info->consumed = round($info->consumed);
    }
    return $info;
}

/**
 * Formats a size figure and adds unit information
 *
 * @author Pau Ferrer (pau@moodle.com)
 * @param int $size file size to be formatted
 *
 * @return object number and units
 */
function report_coursequotas_format_size($size) {
    $formatted = new StdClass();
    $formatted->bytes = $size;

    $suffixes = array('Bytes', 'kB', 'MB', 'GB', 'TB');

    $suffix = 0;
    while ($size > 1024 && $suffix < count($suffixes)) {
        $size = $size / 1024;
        $suffix++;
    }

    $formatted->number = number_format($size, 2, ',', '.');
    $formatted->unit = $suffixes[$suffix];

    return $formatted;
}

/**
 * Format a size figure and adds unit information in text
 * @param int $size file size to be formatted
 *
 * @return string number and units
 */
function report_coursequotas_format_size_text($size) {
    $size = report_coursequotas_format_size($size);
    return $size->number . ' ' . $size->unit;
}

function report_course_quotas_get_chart_info() {
    global $CFG;
    require_once($CFG->dirroot.'/report/coursequotas/constants.php');
    $chartinfo = array();

    // Get quota used in courses.
    $chartinfo['course'] = report_coursequotas_format_size(intval(get_config(REPORT_COMPONENTNAME, 'course_usage')));

    // Get quota used in backups.
    $chartinfo['backup'] = report_coursequotas_format_size(intval(get_config(REPORT_COMPONENTNAME, 'backup_usage')));

    // Get quota used by users.
    $chartinfo['user'] = report_coursequotas_format_size(intval(get_config(REPORT_COMPONENTNAME, 'user_usage')));

    // Get quota used in H5P libraries.
    $chartinfo['h5plib'] = report_coursequotas_format_size(intval(get_config(REPORT_COMPONENTNAME, 'h5plib_usage')));

    // Get quota used in repositories.
    $chartinfo['repository'] = report_coursequotas_format_size(floatval(get_config(REPORT_COMPONENTNAME, 'repositories_usage')));

    // Get quota used in files in temp and trash directories.
    $chartinfo['temp'] = report_coursequotas_format_size(floatval(get_config(REPORT_COMPONENTNAME, 'tempdir_usage')));
    $chartinfo['trash'] = report_coursequotas_format_size(floatval(get_config(REPORT_COMPONENTNAME, 'trashdir_usage')));

    return $chartinfo;
}

/**
 * Creates a tree of categories with size information.
 *
 * @author Pau Ferrer (pau@moodle.com)
 * @global array $DB
 *
 * @return array Tree with data (see description)
 */
function report_coursequotas_get_category_sizes() {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/report/coursequotas/constants.php');

    // Step 1: build category tree.
    $catrecords = $DB->get_records('course_categories', array(), 'depth, id', 'id, name, parent, visible');
    $cattree = report_coursequotas_build_category_tree($catrecords);

    // Add fake front page category.
    $cat = new StdClass();
    $cat->id = 0;
    $cat->name = get_string('front_page', 'report_coursequotas');
    $courseid = $DB->get_field('course', 'id', array('category' => 0));
    $size = $DB->get_field(COURSESIZE_TABLENAME, COURSESIZE_FIELDQUOTA, array(COURSESIZE_FIELDCOURSEID => intval($courseid)));
    $cat->categorysize = intval($size);
    $cat->visible = 1;
    $cat->subcategories = false;
    $cattree[0] = $cat;

    return $cattree;
}

/**
 * Creates a tree data structure wich contains, only, category information. Iterates recursively.
 *
 * @author Toni Ginard (aginard@xtec.cat)
 * @param array $catrecords Contains all the categories info from the data base
 * @param int $parent ID of the category where to start
 *
 * @return array Tree with data (see description)
 */
function report_coursequotas_build_category_tree(&$catrecords, $parent = 0) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/report/coursequotas/constants.php');

    $cattree = array();

    // Find categories with the same parent and add them.
    foreach ($catrecords as $catid => $record) {
        if ($record->parent == $parent) {
            $cat = new StdClass();
            $cat->id = $catid;
            $cat->name = $record->name;
            $catcontext = context_coursecat::instance($catid);
            $cat->categorysize = intval($DB->get_field(CATEGORYSIZE_TABLENAME, CATEGORYSIZE_FIELDQUOTA, array(CATEGORYSIZE_FIELDCATEGORYID => $catid)));
            $cat->visible = $record->visible;
            $cattree[$catid] = $cat;

            // Effiency improvement: Once the category is added to the tree, it won't be added again.
            unset($catrecords[$catid]);
        }
    }

    if (empty($cattree)) {
        return false;
    }

    // Find categories with the same parent and add them. This is done in a second iteration to avoid repeating the same level.
    foreach ($cattree as $catid => $cat) {
        // Recursive call to find subcategories.
        $cat->subcategories = report_coursequotas_build_category_tree($catrecords, $catid);
    }

    return $cattree;
}

/**
 * Sum all files from a given context and its children.
 * @param  Object $context Containing path and instanceid.
 * @return int             Sum of all total bytes of context.
 */
function report_coursequotas_get_contextsize($context) {
    $path = $context->path;
    $contextid = $context->id;

    // Calculate size of all the files inside the course avoiding duplicates.
    return get_coursequotas_filesize("(f.contextid = c.id AND c.path like '$path/%') OR f.contextid = $contextid", "{context} c");
}

/**
 * Transforms category tree in a string HTML-formatted to be sent to the browser. Iterates recursively.
 *
 * @author Pau Ferrer (pau@moodle.com)
 * @param array $cattree Category tree
 *
 * @return string HTML code to be sent to the browser
 */
function report_coursequotas_print_category_data($cattree) {
    global $CFG;

    $content = '<ul>';
    $managestr = get_string('manage', 'report_coursequotas');
    $canmanage = can_manage_files();

    foreach ($cattree as $catid => $category) {

        // Format size number adding unit information.
        $size = report_coursequotas_format_size($category->categorysize);

        // Build list content.
        $content .= '<li>';
        if ($catid == 0) {
            $content .= $category->name;
        } else {
            $dimmed = $category->visible ? "" : ' class="dimmed"';
            $content .= '<a href="'.$CFG->wwwroot.'/course/index.php?categoryid='.$catid.'" '.$dimmed.' target="_blank">'.$category->name.'</a>';
        }
        $content .= ' - ' . $size->number . ' ' . $size->unit;

        if ($canmanage && $size->bytes > 0) {
            $content .= ' - <a href="'.$CFG->wwwroot.'/report/coursequotas/filemanager.php?category='.$catid.'&children=true">'.$managestr.'</a>';
        }

        // Recursive call for subcategories.
        if (!empty($category->subcategories)) {
            $content .= report_coursequotas_print_category_data($category->subcategories);
        }
        $content .= '</li>';
    }
    $content .= '</ul>';

    return $content;
}

function can_manage_files() {
    return has_capability('report/coursequotas:manage', context_system::instance());
}

function get_backup_where_sql() {
    return "((f.component = 'backup' AND (f.filearea = 'activity' OR f.filearea = 'course' OR f.filearea = 'automated')) OR (f.component = 'user' AND f.filearea = 'backup'))";
}

function report_coursequotas_get_directory_size($directory) {
    $size = 0;

    if (file_exists($directory)) {
        $size = exec('du -sk ' . $directory);
        $size = explode('/', $size);
        $size = floatval($size[0]) * 1024; // Size in kB to bytes.
    }

    return $size;
}

function report_coursequotas_get_charinfo_total($chartinfo) {
    $total = 0;
    foreach ($chartinfo as $value) {
        $total += $value->bytes;
    }
    $total = $total / (1024 * 1024);

    return $total;
}

function report_coursequotas_print_chart($chartinfo, $consumed = false, $total = false) {
    global $CFG;

    $text = '';

    $consumedcalc = report_coursequotas_get_charinfo_total($chartinfo);

    if ($consumed && $total) {
        if (is_xtecadmin()) {
            $diffcalc = (int) ($consumed - $consumedcalc);
            if ($diffcalc != 0) {
                $text .= "<div class=\"well well-small\">Hi ha $diffcalc MB que s'escapen...</div>";
            }
        }

        if ($consumedcalc > $consumed) {
            $consumed = $consumedcalc;
        }

        // Protect the graph against data errors.
        $free = $total - $consumed > 0 ? $total - $consumed : 0;
    } else {
        $free = 0;
        $total = $consumed = $consumedcalc;
    }

    $colors = array(
        'course' => '#FDB45C',
        'backup' => '#46BFBD',
        'user' => '#4C86B9',
        'temp' => '#984298',
        'trash' => '#A4822D',
        'repository' => '#BB556F'
    );
    $highlights = array(
        'course' => '#FFC870',
        'backup' => '#5AD3D1',
        'user' => '#5B90BF',
        'temp' => '#D19ED1',
        'trash' => '#C79E37',
        'repository' => '#DF6A88'
    );

    $onepercent = (int) $total / 100;
    $consumedpercent = 0;
    $chartvalues = array();
    foreach ($chartinfo as $type => $value) {
        $value = $value->bytes / (1024 * 1024);
        if ($value > $onepercent) {
            $chartvalue = new StdClass();
            $chartvalue->label = get_string('disk_used_'.$type, 'report_coursequotas');
            $chartvalue->value = $value;
            $chartvalue->percent = round($value / $total * 100, 1);
            $chartvalue->color = $colors[$type];
            $chartvalue->highlight = $highlights[$type];
            $chartvalues[] = $chartvalue;
            $consumed -= $value;
            $consumedpercent += $chartvalue->percent;
        }
    }

    if ($consumed > 0) {
        $chartvalue = new StdClass();
        $chartvalue->label = get_string('disk_used_other', 'report_coursequotas');
        $chartvalue->value = $consumed;
        $chartvalue->percent = round($consumed / $total * 100, 1);
        $chartvalue->color = '#F7464A';
        $chartvalue->highlight = '#FF5A5E';
        $chartvalues[] = $chartvalue;
        $consumedpercent += $chartvalue->percent;
    }

    if ($consumedpercent < 100) {
        $chartvalue = new StdClass();
        $chartvalue->label = get_string('disk_free', 'report_coursequotas');
        $chartvalue->value = $free;
        $chartvalue->percent = round(100 - $consumedpercent, 1);
        $chartvalue->color = '#2C9C69';
        $chartvalue->highlight = '#4CCA91';
        $chartvalues[] = $chartvalue;
    }

    $text .= '<script src="'.$CFG->wwwroot.'/report/coursequotas/chartjs/Chart.min.js"></script>';
    $text .= '<div id="canvas-holder" style="text-align:center;"><canvas id="chart-area" width="300" height="300"/></div>';
    $text .= '<script>
        window.onload = function(){
            var ctx = document.getElementById("chart-area").getContext("2d");
            var data = [';

    foreach ($chartvalues as $value) {
        $text .= '{ value: '.$value->value.', label: "'.$value->label.' ('.$value->percent.'%)", color: "'.$value->color.'", highlight: "'.$value->highlight.'"},';
    }

    $text .= '];
            var options = {
                animateRotate : true,
                animateScale : true,
                tooltipTemplate: "<%if (label){%><%=label%><%} else {%><%= value %><%}%>"
            };
            window.pieChart = new Chart(ctx).Pie(data, options);
        };
    </script>';
    return $text;
}

/**
 * Get the sum of all filesize on a SQL from filesizes avoiding duplicates.
 * @param  string $where  where SQL on file table.
 * @param  string $tables Additional tables to check.
 * @return int            Sum of Bytes.
 */
function get_coursequotas_filesize($where = "", $tables = "") {
    global $DB;

    if (!empty($tables)) {
        $tables = ', '.$tables;
    }

    $where = 'WHERE '.$where.' AND filename != \'.\'';
    $sql = "SELECT sum(total) as total FROM (
       SELECT DISTINCT f.contenthash, f.filesize as total FROM {files} f $tables $where) t";
    $size = $DB->get_field_sql($sql);
    return $size ? $size : 0;
}

/**
 * Returns a list of files filtered.
 * @param  string  $filename        Filename to filter (LIKE).
 * @param  int     $userid          User owner to filter.
 * @param  int     $contextid       Context to filter
 * @param  boolean $addchildren     Add children of the context.
 * @param  string  $filearea        Filearea to filter
 * @param  string  $component       Component to filter.
 * @param  integer $size            Size (less or more to filter).
 * @param  integer $sizeselected    0 if more than, 1 if less than (size).
 * @param  boolean $showonlybackups Show only backup files.
 * @param  boolean $hidesamehash    Hide same hash files (only show one file per hash). It will cause warnings.
 * @param  string  $sort            Sort by field.
 * @param  string  $direction       Direction to sort by.
 * @param  integer $from            From which record return
 * @param  integer $limitnum        Limit number of records.
 * @return Object                   Object containing: files, count of files, filesize (disk usage), total (total sum of files).
 */
function get_filtered_files($filename = "" , $userid = null, $contextid = null, $addchildren = false, $filearea = null,
    $component = null, $size = 0, $sizeselected = 0, $showonlybackups = false, $hidesamehash = false, $sort = 'filename',
    $direction = 'ASC', $from = 0, $limitnum = 100) {
    global $DB;

    $tables = '{files} f';

    $filter = array("f.filename != '.'");
    if (!empty($filename)) {
        $filter[] = "f.filename LIKE '%$filename%'";
    }

    if ($userid) {
        $filter[] = 'f.userid = '.$userid;
    }

    if ($contextid) {
        if ($addchildren) {
            $ctxt = context::instance_by_id($contextid);
            $filter[] = "((f.contextid = c.id AND c.path LIKE '$ctxt->path/%') OR f.contextid = $contextid )";
            $tables .= ', {context} c';
        } else {
            $filter[] = 'f.contextid = '.$contextid;
        }
    }

    if ($filearea) {
        $filter[] = "f.filearea = '$filearea'";
    }

    if ($component) {
        $filter[] = "f.component = '$component'";
    }

    if ($showonlybackups) {
        $filter[] = get_backup_where_sql();
    }

    if ($size > 0) {
        $size *= 1024 * 1024;
        if ($sizeselected == 0) {
            $filter[] = "f.filesize >= $size";
        } else if ($sizeselected == 1) {
            $filter[] = "f.filesize <= $size";
        }
    }

    $avaiablesorts = array('filename', 'filearea', 'component', 'filesize');
    if (!in_array($sort, $avaiablesorts)) {
        $sort = 'filename';
    }
    $direction = strtolower($direction) == 'desc' ? 'DESC' : 'ASC';

    $where = implode(' AND ', $filter);
    $record = new StdClass();

    if ($hidesamehash) {
        $distinct = "DISTINCT f.contenthash, f.id";
    } else {
        $distinct = "DISTINCT f.id, f.contenthash";
    }
    $sql = "SELECT $distinct, f.filename, f.userid, f.contextid, f.filearea, f.component, f.filesize, f.pathnamehash, f.filepath, f.mimetype, f.timemodified FROM $tables WHERE $where ORDER BY f.$sort $direction";

    $record->files = @$DB->get_records_sql($sql, null, $from, $limitnum);

    if ($hidesamehash) {
        $sql = "SELECT count(DISTINCT f.contenthash) FROM $tables WHERE $where";
    } else {
        $sql = "SELECT count(DISTINCT f.id) FROM $tables WHERE $where";
    }
    $record->count = $DB->count_records_sql($sql);

    $sql = "SELECT sum(total) as total FROM (SELECT DISTINCT (f.contenthash), f.filesize as total FROM $tables WHERE $where) t";
    $size = $DB->get_field_sql($sql);
    $record->filesize  = $size ? $size : 0;

    if ($hidesamehash) {
        $record->total  = $record->filesize;
    } else {
        $sql = "SELECT sum(total) AS total FROM (SELECT DISTINCT (f.id), f.filesize as total FROM $tables WHERE $where) t";
        $size = $DB->get_field_sql($sql);
        $record->total  = $size ? $size : 0;
    }

    return $record;
}

/**
 * Get files by contenthash
 * @param  string  $hash      Hash of the content to filter.
 * @param  string  $sort      Sort by field.
 * @param  string  $direction Direction to sort by.
 * @param  integer $from      From which record return
 * @param  integer $limitnum  Limit number of records.
 * @return Object             Object containing: files, count of files, filesize (disk usage) = total (total sum of files).
 */
function get_contenthash_files($hash, $sort = 'filename', $direction = 'ASC', $from = 0, $limitnum = 100) {
    global $DB;

    $where = "f.contenthash = '$hash'";

    $avaiablesorts = array('filename', 'filearea', 'component', 'filesize');
    if (!in_array($sort, $avaiablesorts)) {
        $sort = 'filename';
    }
    $direction = strtolower($direction) == 'desc' ? 'DESC' : 'ASC';

    $record = new StdClass();
    $sql = "SELECT DISTINCT (f.id), f.filename, f.userid, f.contextid, f.filearea, f.component, f.filesize, f.contenthash, f.pathnamehash, f.filepath, f.mimetype, f.timemodified FROM {files} f WHERE $where ORDER BY f.$sort $direction";
    $record->files = $DB->get_records_sql($sql, null, $from, $limitnum);
    $sql = "SELECT count(DISTINCT f.id) FROM {files} f WHERE $where";
    $record->count = $DB->count_records_sql($sql);

    $sql = "SELECT sum(total) AS total FROM (SELECT DISTINCT (f.id), f.filesize as total FROM {files} f WHERE $where) t";
    $size = $DB->get_field_sql($sql);
    $record->total  = $size ? $size : 0;

    $sql = "SELECT sum(total) as total FROM (SELECT DISTINCT (f.contenthash), f.filesize as total FROM {files} f WHERE $where) t";
    $size = $DB->get_field_sql($sql);
    $record->filesize  = $size ? $size : 0;

    return $record;
}

/**
 * Get all options to filter for.
 * @param  int $searchedcontext Contextid where we're searching.
 * @return object               Filters.
 */
function get_all_filter_options($searchedcontext) {
    global $DB;

    $filters = new StdClass();
    $users = $DB->get_records_sql('SELECT DISTINCT userid, firstname, lastname FROM {files} f, {user} u  WHERE f.userid = u.id ORDER BY lastname');
    $filters->users = [];
    foreach ($users as $userid => $user) {
        $filters->users[$userid] = $user->firstname.' '.$user->lastname;
    }
    $contexts = $DB->get_records_sql('SELECT DISTINCT contextid FROM {files} ORDER BY contextid');
    $filters->contexts = [];
    foreach ($contexts as $contextid => $context) {
        $filecontext = context::instance_by_id($contextid);
        if ($filecontext->contextlevel == CONTEXT_COURSE) {
            $filters->contexts[$contextid] = $filecontext->get_context_name();
        }
    }

    if ($searchedcontext && !isset($filters->contexts[$searchedcontext])) {
        $filecontext = context::instance_by_id($searchedcontext);
        $filters->contexts[$searchedcontext] = $filecontext->get_context_name();
    }

    $filters->fileareas = $DB->get_records_sql_menu('SELECT DISTINCT filearea AS f, filearea FROM {files} ORDER BY filearea');
    $filters->components = $DB->get_records_sql_menu('SELECT DISTINCT component AS c, component FROM {files} ORDER BY component');
    return $filters;
}
