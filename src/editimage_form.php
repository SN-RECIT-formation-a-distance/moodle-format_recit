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
global $CFG;
require_once("{$CFG->libdir}/formslib.php");

class grid_image_form extends moodleform {

    public function definition() {
        $mform = $this->_form;
        $instance = $this->_customdata;

        // Visible elements.
        $mform->addElement('filepicker', 'imagefile', get_string('imagefile', 'format_treetopics'), null, $instance['options']);
        $mform->addHelpButton('imagefile', 'imagefile', 'format_treetopics');
        $mform->addElement('selectyesno', 'deleteimage', get_string('deleteimage', 'format_treetopics'));
        $mform->addHelpButton('deleteimage', 'deleteimage', 'format_treetopics');

        // Hidden params.
        $mform->addElement('hidden', 'contextid', $instance['contextid']);
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('hidden', 'userid', $instance['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'sectionid', $instance['sectionid']);
        $mform->setType('sectionid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'uploadfile');
        $mform->setType('action', PARAM_ALPHA);

        // Buttons:...
        $this->add_action_buttons(true, get_string('savechanges', 'admin'));
    }

}
