<?php
// This file is part of a plugin written to be used on the free teaching platform : Moodle
// Copyright (C) 2019 recit
// 
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.
//
// @package    format_treetopics
// @subpackage RECIT
// @copyright  RECIT {@link https://recitfad.ca}
// @author     RECIT {@link https://recitfad.ca}
// @license    {@link http://www.gnu.org/licenses/gpl-3.0.html} GNU GPL v3 or later
// @developer  Studio XP : {@link https://www.studioxp.ca}

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for format_treetopics
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_format_treetopics_upgrade($oldversion) {
    /*global $CFG, $DB;
    
    $dbman = $DB->get_manager();

    require_once($CFG->dirroot . '/course/format/treetopics/db/upgradelib.php');
    
    if ($oldversion < 2019041603) {

        // Define table format_treetopics_contract to be created.
        $table = new xmldb_table('format_treetopics_contract');

        // Adding fields to table format_treetopics_contract.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table format_treetopics_contract.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for format_treetopics_contract.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Treetopics savepoint reached.
        upgrade_plugin_savepoint(true, 2019041603, 'format', 'treetopics');
    }*/

    return true;
}
