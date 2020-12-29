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

include_once($CFG->dirroot.'/report/coursequotas/constants.php');

function xmldb_report_coursequotas_upgrade($oldversion) {
	global $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    $result = TRUE;

    $size_integer_10 = '10';
    $size_integer_20 = '20';
    $default_zero_value = '0';

    if ($oldversion < REPORT_COURSEQUOTAS_VERSION_CREATETABLE) {
    	echo $OUTPUT->notification('Creating new table coursequotas...', 'notifysuccess');

    	// Conditionally launch create table.
        if (!$dbman->table_exists(COURSESIZE_TABLENAME)) {
            // Creating table & Adding fields, keys and indexes.
            $table = new \xmldb_table(COURSESIZE_TABLENAME);
            $table->add_field(COURSESIZE_FIELDID, XMLDB_TYPE_INTEGER, $size_integer_10, null, true, true, null, null);
            $table->add_field(COURSESIZE_FIELDCOURSEID, XMLDB_TYPE_INTEGER, $size_integer_20, null, true, false, $default_zero_value, COURSESIZE_FIELDID);
            $table->add_field(COURSESIZE_FIELDQUOTA, XMLDB_TYPE_INTEGER, $size_integer_20, null, true, false, $default_zero_value, COURSESIZE_FIELDCOURSEID);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array(COURSESIZE_FIELDID));
            $table->add_index('courseid_idx', XMLDB_INDEX_NOTUNIQUE, array(COURSESIZE_FIELDCOURSEID));
            $dbman->create_table($table);          
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, REPORT_COURSEQUOTAS_VERSION_CREATETABLE, REPORT_COURSEQUOTAS_REPORTSTRING, REPORT_COURSEQUOTAS_NAME);
    }

    if ($oldversion < REPORT_CATEGORYQUOTAS_VERSION_CREATETABLE) {
        echo $OUTPUT->notification('Creating new table categoryquotas...', 'notifysuccess');

        // Conditionally launch create table.
        if (!$dbman->table_exists(CATEGORYSIZE_TABLENAME)) {
            // Creating table & Adding fields, keys and indexes.
            $table = new \xmldb_table(CATEGORYSIZE_TABLENAME);
            $table->add_field(CATEGORYSIZE_FIELDID, XMLDB_TYPE_INTEGER, $size_integer_10, null, true, true, null, null);
            $table->add_field(CATEGORYSIZE_FIELDCATEGORYID, XMLDB_TYPE_INTEGER, $size_integer_20, null, true, false, $default_zero_value, CATEGORYSIZE_FIELDID);
            $table->add_field(CATEGORYSIZE_FIELDQUOTA, XMLDB_TYPE_INTEGER, $size_integer_20, null, true, false, $default_zero_value, CATEGORYSIZE_FIELDCATEGORYID);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array(CATEGORYSIZE_FIELDID));
            $table->add_index('categoryid_idx', XMLDB_INDEX_NOTUNIQUE, array(CATEGORYSIZE_FIELDCATEGORYID));
            $dbman->create_table($table);          
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, REPORT_CATEGORYQUOTAS_VERSION_CREATETABLE, REPORT_COURSEQUOTAS_REPORTSTRING, REPORT_COURSEQUOTAS_NAME);
    }

    if ($oldversion < REPORT_COURSEQUOTAS_LASTVERSION) {
        // Savepoint reached.
        upgrade_plugin_savepoint(true, REPORT_COURSEQUOTAS_LASTVERSION, REPORT_COURSEQUOTAS_REPORTSTRING, REPORT_COURSEQUOTAS_NAME);
    }

    return $result;
}