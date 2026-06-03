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
 * Privacy provider for format_recit.
 *
 * This plugin stores only course-level configuration data (section layout
 * options) in the format_recit_options table. No personally identifiable
 * information (PII) is collected, processed, or stored. This satisfies the
 * requirements of Law 25 (Québec Act respecting the protection of personal
 * information in the private sector) and Moodle's privacy subsystem.
 *
 * The format_recit_options table contains:
 *   - courseid  (int)  : links to the course, not to any user
 *   - sectionid (int)  : links to a course section, not to any user
 *   - name      (char) : option key (e.g. 'sectionlevel')
 *   - value     (text) : option value (e.g. '1')
 *
 * No userid, username, email, or other personal identifiers are recorded.
 *
 * @package     format_recit
 * @copyright   RECITFAD
 * @author      RECITFAD
 * @license     {@link http://www.gnu.org/licenses/gpl-3.0.html} GNU GPL v3 or later
 */

namespace format_recit\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy subsystem for format_recit.
 *
 * Implements null_provider because the format_recit_options table stores
 * course configuration only — no personal data is collected, and there is
 * nothing to export or delete on a per-user basis (Law 25 / GDPR compliant).
 *
 * @copyright  RECITFAD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Returns the lang string key explaining that no personal data is stored.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
