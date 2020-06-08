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
 * Define plugin data upgrade.
 *
 * @package     format_treetopics
 * @copyright   RECITFAD
 * @author      RECITFAD
 * @license     {@link http://www.gnu.org/licenses/gpl-3.0.html} GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for format_treetopics
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_format_treetopics_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    // Add a new columns suggestednote and teachertip.
    if ($oldversion < 2020020502) {
        // Define field jsoncontent to be added to filter_wiris_formulas.
        $table = new xmldb_table('format_treetopics_contract');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        // Conditionally launch add field jsoncontent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

           upgrade_plugin_savepoint(true, 2020020502, 'format', 'treetopics');
    }

    return true;
}
