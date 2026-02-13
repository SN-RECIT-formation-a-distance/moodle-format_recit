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

namespace format_recit\output;

use core_courseformat\output\section_renderer;
use moodle_page;
use core\context\course as context_course;
use renderable;
use stdClass;

/**
 * Basic renderer for topics format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends section_renderer {      
    protected $editingMode = null;
    protected $nonEditingMode = null;
    public static $instance = null;

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        renderer::$instance = $this;

        // Since format_recit_renderer::section_edit_controls() only displays the 'Set current section' control
        // when editing mode is on.
        // We need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other
        // managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');

       $this->editingMode = new EditingMode($page);
       $this->nonEditingMode  = new NonEditingMode($page);
    }

    /**
     * Renders the provided widget and returns the HTML to display it.
     *
     * Course format templates uses a similar subfolder structure to the renderable classes.
     * This method find out the specific template for a course widget. That's the reason why
     * this render method is different from the normal plugin renderer one.
     *
     * course format templatables can be rendered using the core_course/local/* templates.
     * Format plugins are free to override the default template location using render_xxx methods as usual.
     *
     * @param renderable $widget instance with renderable interface
     * @return string the widget HTML
     */
    public function render(renderable $widget = null) {
        if($widget != null){
            return parent::render($widget);
        }

        $result = "";
        if ($this->page->user_is_editing()) {           
            $result .= $this->editingMode->render();
            echo $result;
            return "";
        } else {
            $result .= $this->nonEditingMode->render($this->page_title());
        }

        return $result;
    }

    /**
     * Function get course section cm list of format_recit_renderer class.
     * @param stdClass $course
     * @param string $section
     */
    public function get_course_section_cm_list($format, $section) {
        $cmlistclass = $format->get_output_classname('content\\section\\cmlist');
        return $this->render(new $cmlistclass($format, $section));
    }
}

class LocalRenderer{
    /** @var string */
    const ID_APPEND = 'tt-';

    protected $page = null;
    /** @var stdClass */
    protected $course = null;
    /** @var string */
    protected $output = null;
    /** @var stdClass */
    protected $modinfo = null;
    /** @var format_recit */
    protected $format = null;
    /** @var array */
    public $sectionslist = array();

    public function __construct($page) {
        global $COURSE, $OUTPUT;

        $this->page = $page;
        $this->course = $COURSE;
        $this->output = $OUTPUT;
        $this->format = course_get_format($this->course);
        $this->modinfo = get_fast_modinfo($this->course);
        $this->sectionslist = $this->modinfo->get_section_info_all();
    }

     
    /**
     * Generate a section's id
     *
     * @param stdClass $section The course_section entry from DB
     * @return string the section's string id
     */
    protected function get_section_id($section) {
        return sprintf("#section-%d", $section->section);
    }

    public function get_section_name($section) {
        $sectioninfo = $this->format->get_section($section);
        
        return $this->format->get_section_name($sectioninfo);
    }
}

class NonEditingMode extends LocalRenderer{
    public function render($pageTitle) {
        $html = '';
        $html .= $this->output->heading($pageTitle, 2, 'accesshide');

        $issinglesection = $this->page->url->compare(new \moodle_url('/course/section.php'), URL_MATCH_BASE);
        // course/section.php
        if ($issinglesection) {
            $cursection = optional_param('id', 0, PARAM_INT);

            foreach ($this->sectionslist as $item) {
                if( !$item->visible ){ continue; }

                if ($item->id == $cursection){
                    $html = $this->render_section_content($item);
                    break;
                }
            }

            return $html;
        } 
        // course/view.php
        else {
            // first section
            $item = !empty($this->sectionslist) ? reset($this->sectionslist) : null;            
            $html = $this->render_section_content($item);
            // the html must be printed out and not returned and on section.php
            echo $html;
            return "";
        }
    }

    /**
     * Function to get section tab 
     *
     * @param string $section
     * @return string
     */
    public function render_section_content($section) {
        global $USER, $CFG;

        if(empty($section)){
            return "";
        }

        $sectionid = $this->get_section_id($section);
        $sectionname = $this->get_section_name($section);
        $hideVisible = isset($this->course->hiddensections) ? $this->course->hiddensections : 1; //0 = show for teachers, 1 = hidden for everyone
        $sectionstyle = '';               

        //Disabled for format option hiddensections
        /*if (!$section->visible ) {
            $sectionstyle = ' hidden';
        }*/
        if ($this->format->is_section_current($section)) {
            $sectionstyle = ' current';
        }
                    
        $content = "";
        
        if ($section->ttsectionshowactivities == 1) {
            $content = renderer::$instance->get_course_section_cm_list($this->format, $section);
        }              

        $context = context_course::instance($this->course->id);
        $seehidden = has_capability('theme/recit2:accesshiddensections', $context, $USER->id, false);
        $sectionsummary = '';

        if ($section->available || ($hideVisible == 0 && $seehidden)){
            // Show summary if section is available or has
            $sectionsummary = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php', $context->id, 'course', 'section', $section->id);
                    
            $bkpForceclean = $CFG->forceclean;
            $CFG->forceclean = false;
            $sectionsummary = format_text($sectionsummary,  $section->summaryformat, array('noclean' => true, 'overflowdiv' => true, 'filter' => true));
            $CFG->forceclean = $bkpForceclean;
        }
        $html = "<div class='section main clearfix tt-section $sectionstyle' data-section='$sectionid' role='region' aria-label='$sectionname'>";

        $html .= "<div class='content'>";
        $html .= $section->availableinfo;
        $html .= "<div class='summary'>$sectionsummary</div>";
        $html .= "$content";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }
}

class EditingMode extends LocalRenderer{
    public function render(){
        $renderer = $this->page->get_renderer('format_topics');
        $outputclass = $this->format->get_output_classname('content');
        $widget = new $outputclass($this->format);
        return $renderer->render($widget);
    }
}