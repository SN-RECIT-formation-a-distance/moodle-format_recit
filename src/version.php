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
 * Treetopics version control.
 *
 * @package    format_treetopics
 * @copyright  RECITFAD
 * @author     RECITFAD
 * @license    {@link http://www.gnu.org/licenses/gpl-3.0.html} GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2020062900;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2018051700;        // Requires this Moodle version.
$plugin->component = 'format_treetopics';    // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_BETA;
$plugin->release = 'R9-V1.9';
$plugin->dependencies = [];