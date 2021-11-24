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
 * Class format_recit_renderer extends of format_section_renderer_base.
 *
 * @package    format_recit
 * @copyright  RECITFAD
 * @author     RECITFAD
 * @license    {@link http://www.gnu.org/licenses/gpl-3.0.html} GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');

js_reset_all_caches();
/**
 * FormatRecit specifics functions.
 *
 * @author RECITFAD
 */
class FormatRecit
{
    /** @var string */
    const ID_APPEND = 'tt-';

    /** @var format_recit_renderer */
    protected $moodlerenderer = null;
    /** @var stdClass */
    protected $course = null;
    /** @var string */
    protected $output = null;
    /** @var stdClass */
    protected $modinfo = null;
    /** @var array */
    protected $sectionslist = array();

    /**
     * Construc for FormatRecit
     */
    public function __construct() {
        global $OUTPUT;
        //$context = context_course::instance($COURSE->id);
        $this->output = $OUTPUT;
    }

     /**
     * Function load of FormatRecit.
     *
     * @param format_recit_renderer $moodlerenderer
     * @param stdClass $course
     */
    public function load($moodlerenderer, $course) {
        $this->moodlerenderer = $moodlerenderer;
        $this->course = $course;

        $this->modinfo = get_fast_modinfo($course);
        $this->sectionslist = $this->modinfo->get_section_info_all();
    }

    /**
     * Function render of FormatRecit.
     *
     * @return string
     */
    public function render() {
        $this->signing_contract();

        if ($this->show_contract()) {
            $html = "<div id='sectioncontent_placeholder'>%s</div>";
            $html = sprintf($html, $this->render_contract());
        } else {
            $html = "<div id='sectioncontent_placeholder'></div>";
        }

        echo $html;
    }

    /**
     * Function to get section tab 
     *
     * @param string $section
     * @return string
     */
    public function render_section_content($sectionid) {
        $section = null;
        foreach ($this->sectionslist as $item) {
            if( !$item->visible ){ continue; }

            $id = $this->get_section_id($item);
            if ($id == $sectionid) { 
                $section = $item;
                break;
            }
        }

        if(empty($section)){
            return null;
        }

        //$sections = $modinfo->get_section_info_all();
     //   $thissection = $infosections[$this];
        $sectionid = $this->get_section_id($section);
        $sectionname = $this->get_section_name($section);
        $sectionavail = $this->moodlerenderer->section_availability($section);

        $sectionstyle = '';

        if (!$section->visible ) {
            $sectionstyle = ' hidden';
        }
        if (course_get_format($this->course)->is_section_current($section)) {
            $sectionstyle = ' current';
        }

        $content = "";
        if ($section->ttsectionshowactivities == 1) {
            $content = $this->moodlerenderer->get_course_section_cm_list($this->course, $section);
        }

        $context = context_course::instance($this->course->id);
        $sectionsummary ='';
        if (!$section->available)
        {}
         else{
            
                // Show summary if section is available or has
            $sectionsummary = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php', $context->id, 'course', 'section',
                    $section->id);

            $sectionsummary = format_text($sectionsummary,  $section->summaryformat, array('noclean' => true, 'overflowdiv' => true,
                    'filter' => true));
         }
        $html = "<div class='section main clearfix tt-section $sectionstyle' role='region' aria-label='$sectionname'";
        $html .= " data-section='$sectionid'>";

        if($section->ttsectiontitle == 1){
            $html .= "<h2>$sectionname</h2>";
        }

        $html .= "<div class='content'>";
        $html .= "$sectionavail";
        $html .= "<div class='summary'>$sectionsummary</div>";
        $html .= "$content";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Generate a section's id
     *
     * @param stdClass $section The course_section entry from DB
     * @return string the section's string id
     */
    protected function get_section_id($section) {
        return sprintf("section-%d", $section->section);
    }

    /**
     * Function to get section name of TreeTopics.
     * @param string $section
     * @return string
     */
    protected function get_section_name($section) {
        return (empty($section->name) ? get_string('section') . '' . $section->section : $section->name);
    }

    /**
     * Function show contract of FormatRecit.
     * @return boolean
     */
    protected function show_contract() {
        return (isset($this->course->tthascontract)) && ($this->course->tthascontract) && (!$this->contract_is_signed());
    }

    /**
     * Function signing contract of FormatRecit.
     */
    protected function signing_contract() {
        if ((isset($_GET["ttc"])) && ($_GET["ttc"] == '1')) {
            $this->contract_sign();
        }
        // Could have "else{$this->contract_unsign();}".
    }

    /**
     * Function for checking signed contract of FormatRecit.
     * @return boolean
     */
    protected function contract_is_signed() {
        global $DB, $USER;
        $result = $DB->record_exists('format_recit_contract', ['courseid' => $this->course->id, 'userid' => $USER->id]);
        return $result;
    }

    /**
     * Function to update signed contract of FormatRecit.
     */
    protected function contract_sign() {
        global $DB, $USER;

        if (!$this->contract_is_signed()) {
            $DB->insert_record('format_recit_contract',
                    array('courseid' => $this->course->id, 'userid' => $USER->id, 'timemodified' => time()));
        }
    }

    /**
     * Function render contract of FormatRecit.
     * @return string
     */
    protected function render_contract() {
        global $CFG;

        $signed = $this->contract_is_signed();

        $section = $this->sectionslist[0];

        $html = "<br/><br/>";
        $html .= html_writer::start_tag('div', array('class' => self::ID_APPEND . 'contract'));
        $html .= html_writer::tag('h2', "Contrat d'engagement", array('class' => self::ID_APPEND . 'contract-title'));
        $html .= html_writer::tag('div', $section->ttcontract_editor, array('class' => self::ID_APPEND . 'contract-content'));

        $html .= "<div>";
        $html .= sprintf("<label><input id='%s' type='checkbox'/>
                J'ai lu, je comprends et j'accepte les termes du contrat</label>", self::ID_APPEND.'contract-read');
        $html .= "</div>";

        $html .= html_writer::start_tag('div');
        $html .= html_writer::tag('button', 'Signer le contrat',
            array('id' => self::ID_APPEND . 'contract-sign', 'type' => 'submit', 'disabled' => 'disabled',
                'href' => $CFG->wwwroot.'/course/view.php?id='.$this->course->id.'&ttc=1#section-1',
                'class' => 'btn btn-primary'));
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('div');

        return $html;
    }

    public function render_editing_mode($format_recit_renderer){
        global $CFG, $COURSE;

        $selectedSection = (isset($_COOKIE['section']) ? $_COOKIE['section'] : 'menu');

        $result = '<div class="row">';
        $result .= '<div class="bg-light p-2 w-100">';
        $result .= '<button class="btn btn-outline-primary btn-sm m-1 mr-2" type="button" data-toggle="collapse" data-target="#navTabs" aria-expanded="false" aria-controls="navTabs"><i class="fa fa-bars"></i></button>';
        $result .= '<span class="h6 text-muted">Liste de sections</span>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '<div class="row">';        
        $result .= '<div class="col-xs col-sm-4 col-lg-2 collapse p-0" id="navTabs">';
        $result .= '<div class="nav nav-pills bg-light  flex-column" id="v-pills-tab" role="tablist" aria-orientation="vertical">';

        $sectionid = 'menu';
        $templateNavItem = "<a class='nav-item nav-link %s' id='v-pills-%s-tab' data-toggle='pill' href='#v-pills-%s' role='tab' 
                            onclick='M.recit.course.format.recit.EditingMode.instance.goToSection(event, \"%s\")'
                            aria-controls='v-pills-%s' aria-selected='true'>%s</a>";

        $result .= sprintf($templateNavItem, ($selectedSection === $sectionid ? 'active' : ''), $sectionid, $sectionid, $sectionid, $sectionid, "Menu");

        foreach ($this->sectionslist as $section) {
            $sectionid = $this->get_section_id($section);
            $result .= sprintf($templateNavItem, ($selectedSection === $sectionid ? 'active' : ''), $sectionid, $sectionid, $sectionid, $sectionid, $this->get_section_name($section));
        }
        
        $result .= '</div>';
        $result .= '</div>';
        $result .= '<div class="col-xl">';
        $result .= '<div class="tab-content" id="v-pills-tabContent">';

        $html = "<div class='btn-group  pull-right'>";
        $html .= sprintf("<a href='%s/course/changenumsections.php?courseid=%ld&insertsection=0&sesskey=%s&returnurl=%s&sectionreturn=menu' 
                        class='btn btn-outline-primary' title='%s'><i class='fa fa-plus'></i></a>", 
                        $CFG->wwwroot, $COURSE->id, sesskey(), course_get_url($COURSE), get_string('addsections', 'format_recit'));
        $html .= sprintf("<button class='btn btn-outline-primary' onclick='M.recit.course.format.recit.EditingMode.instance.onBtnShowHideCmList(event)' title='%s'><i class='fa fa-fw fa-eye-slash'></i></button>", 
                        get_string('sectionshowhideactivities', 'format_recit'));
        $html .= sprintf("<button class='btn btn-outline-primary' onclick='window.location.reload();' title='%s'><i class='fa fa-fw fa-refresh'></i></button>", 
                    get_string('saveandrefresh', 'format_recit'));
        $html .= "</div>";
        $html .= "<br/><br/><br/>";

        foreach ($this->sectionslist as $section) {
            $html .= $this->render_editing_mode_section_content($format_recit_renderer, $section, true);
        }       

        $html .= $this->getHtmlLoading();

        $sectionid = 'menu';
        $templateTabContent = '<div class="tab-pane fade show %s p-2" id="v-pills-%s" role="tabpanel" aria-labelledby="v-pills-%s-tab">%s</div>';
        $result .= sprintf($templateTabContent, ($selectedSection === $sectionid ? 'active editing-mode-menu' : 'editing-mode-menu'), $sectionid, $sectionid, $html);

        foreach ($this->sectionslist as $section) {
            $sectionid = $this->get_section_id($section);
            $result .= sprintf($templateTabContent, ($selectedSection === $sectionid ? 'active' : ''), $sectionid, $sectionid, 
                        $this->render_editing_mode_section_content($format_recit_renderer, $section));
        }
        
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        return $result;
    }

    protected function render_editing_mode_section_content($format_recit_renderer, $section, $showMenu = false){
        global $CFG;

        // Title with completion help icon.
        $completioninfo = new completion_info($this->course);
        $sectionid = $this->get_section_id($section);

        $result = "";
        $result .= $completioninfo->display_help_icon();
        
        if($showMenu){
            $result .= $format_recit_renderer->section_header($section, $this->course, false, 0, false);
            $result .= sprintf("<div class='section_add_menus' id='add_menus-%s'></div>", $sectionid);
            $result .= sprintf("<div data-course-section-cm-list='1' style='display:none;'>%s</div>", $format_recit_renderer->get_course_section_cm_list($this->course, $section));
            $result .= $format_recit_renderer->section_footer();
        }
        else{   
            $result .= sprintf("<h2>%s</h2>",  $this->get_section_name($section));
            $result .= "<div class='btn-group pull-right'>";
            $result .= sprintf("<a class='btn btn-outline-primary' href='%s/course/editsection.php?id=%ld&sr' title='%s'><i class='fa fa-fw fa-sliders'></i></a>", $CFG->wwwroot, $section->id, get_string('editsection', 'format_recit'));
            $result .= sprintf("<button class='btn btn-outline-primary' onclick='M.recit.course.format.recit.EditingMode.instance.onBtnShowHideHiddenActivities(event)' title='%s'><i class='fa fa-fw fa-eye'></i></button>", get_string('showhidehiddenactivities', 'format_recit'));
            $result .= html_writer::tag('button', "<i class='fa fa-plus'></i>", [
                'class' => 'section-modchooser-link btn btn-outline-primary',
                'data-action' => 'open-chooser',
                'data-sectionid' => $section->section,
                'data-sectionreturnid' => $section->section,
                'title' => get_string('addresourceoractivity'),
                ]
            );
            $result .= "</div>";
            $result .= "<br/><br/>";
            $result .= $format_recit_renderer->section_header($section, $this->course, false, 0, true, false);
            $result .= $format_recit_renderer->get_course_section_cm_list($this->course, $section);
            $result .= $format_recit_renderer->get_course_section_add_cm_control($this->course, $section->section);
            $result .= $format_recit_renderer->section_footer();
        }
        
        return $result;
    }

    protected function getHtmlLoading(){
        $html = "
        <div id='tt-loading' class='fa-5x' style='display: none; position: fixed; z-index: 9999; top: 50%; left: 50%; transform: translate(50%, 50%);'>
            <i class='fa fa-spinner fa-spin'></i>
        </div>";

        return $html;
    }
}

/**
 * Basic renderer for topics format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_recit_renderer extends format_section_renderer_base {

    /** @var FormatRecit */
    protected $formatrecit = null;

    /** 
     * @const int
     * Option 1 = All sections together (standard way)
     * Option 2 = One section at once
     */
    public const EDITING_MODE_OPTION = 2;

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Since format_recit_renderer::section_edit_controls() only displays the 'Set current section' control
        // when editing mode is on.
        // We need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other
        // managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');

        $this->formatrecit = new FormatRecit();
    }

    /**
     * Function render tree topics of format_recit_renderer class.
     * @param stdClass $course
     */
    public function render_format_recit($course) {

        if ($this->page->user_is_editing()) {
            switch (self::EDITING_MODE_OPTION) {
                case 1: 
                    $this->print_multiple_section_page($course, null, null, null, null);
                    break;
                case 2:
                    $this->formatrecit->load($this, course_get_format($course)->get_course());
                    echo $this->formatrecit->render_editing_mode($this);
                    break;
            }
        } else {
            echo $this->output->heading($this->page_title(), 2, 'accesshide');
            echo $this->course_activity_clipboard($course, 0);  // Copy activity clipboard..
            $this->formatrecit->load($this, course_get_format($course)->get_course());
            $this->formatrecit->render();
        }
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        // Could "return html_writer::start_tag('div', array('class' => 'recit'));".
        return "";
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        // Could "return html_writer::end_tag('div');".
        return "";
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Function get course section cm list of format_recit_renderer class.
     * @param stdClass $course
     * @param string $section
     */
    public function get_course_section_cm_list($course, $section) {
        return $this->courserenderer->course_section_cm_list($course, $section, 0);
    }
    
     /**
     * Output the html for a multiple section page
     * Renders HTML for the menus to add activities and resources to the current course
     *
     * @param stdClass $course The course entry from DB
     * @param string $sections
     * @param string $mods
     * @param string $modnames
     * @param string $modnamesused
     * @param stdClass $course
     * @return string
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);

        echo "<div class='editing-mode-menu'>";

        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        //if ($course->ttdisplayshortcuts) {
            $filtervalues = (isset($_COOKIE['ttModeEditionFilter'])
                    ? explode(",", $_COOKIE['ttModeEditionFilter']) : array("sum", "act"));
            $ttmodeeditorfilter = '
                <div class="btn-group btn-group-toggle" data-toggle="buttons" id="ttModeEditionFilter">
                    <label class="btn btn-outline-primary  %s">
                        <input type="checkbox" value="sum" autocomplete="off" %s> Affiche le sommaire de la section
                    </label>
                    <label class="btn btn-outline-primary  %s">
                        <input type="checkbox" value="act" autocomplete="off" %s> Afficher les activités
                    </label>
                </div><br/><br/>
            ';

            echo sprintf($ttmodeeditorfilter, (in_array("sum", $filtervalues) ? 'active' : ''),
                    (in_array("sum", $filtervalues) ? 'checked' : ''),
                    (in_array("act", $filtervalues) ? 'active' : ''),
                    (in_array("act", $filtervalues) ? 'checked' : ''));
       // }

        $numsections = course_get_format($course)->get_last_section_number();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                // Special case : 0-section is displayed a little different then the others.
                if ($thissection->summary or !empty($modinfo->sections[0]) or $this->page->user_is_editing()) {
                    echo $this->section_header($thissection, $course, false, 0);
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, 0, 0);
                    echo $this->section_footer();
                }
                continue;
            }
            if ($section > $numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
                    (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }

            if (!$this->page->user_is_editing()) {
                // Display section summary only.
                echo $this->section_summary($thissection, $course, null);
            } else {
                echo $this->section_header($thissection, $course, false, 0);
                if ($thissection->uservisible) {
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);
                }
                echo $this->section_footer();
            }
        }

        if ($this->page->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $numsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }

            echo "<br/>";
            echo $this->end_section_list();

            echo $this->change_number_sections($course, 0);
        } else {
            echo $this->end_section_list();
        }

        echo "</div>";
    }

    /**
     * Renders HTML for the menus to add activities and resources to the current course
     *
     * @param stdClass $course
     * @return string
     */
    public function get_course_section_add_cm_control($course, $isection) {
        return $this->courserenderer->course_section_add_cm_control($course, $isection, 0);
    }
    
    /**
     * OVERRIDE
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @param int $showsectionsummary 
     * @return string HTML to output.
     */
    public function section_header($section, $course, $onsectionpage, $sectionreturn=null, $showsectionsummary=true, $showsectiondetails = true) {

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible ||!$section->available) {
                $sectionstyle = ' hidden';
            }
            if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        $display = ($showsectiondetails ? '' : 'd-none');

        $o .= html_writer::start_tag('li', array('id' => 'section-'.$section->section,
            'class' => "section main clearfix".$sectionstyle, 'role' => 'region',
            'aria-label' => get_section_name($course, $section), "data-section-level" => $section->ttsectiondisplay,
            "data-section-id" => $section->id, 'style' => 'list-style: none;') );


        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $o .= html_writer::tag('span', get_section_name($course, $section), array('class' => "hidden sectionname $display"));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o .= html_writer::tag('div', $leftcontent, array('class' => "left side $display"));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o .= html_writer::tag('div', $rightcontent, array('class' => "right side $display"));
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null.
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one.
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }
        $sectionname = html_writer::tag('span', $this->section_title($section, $course), array('class' => "$display"));
        $o .= $this->output->heading($sectionname, 3, 'sectionname' . $classes);

        $o .= $this->section_availability($section);

        $o .= html_writer::start_tag('div', array('class' => 'summary'));
        if ($section->uservisible || $section->visible) {
            // Show summary if section is available or has availability restriction information.
            // Do not show summary if section is hidden but we still display it because of course setting
            // "Hidden sections are shown in collapsed form".
            if($showsectionsummary){
                $o .= $this->format_summary_text($section);
            }
        }
        $o .= html_writer::end_tag('div');

        return $o;
    }

    /**
     * Generate footer html of a stealth section
     *
     * @return string HTML to output.
     */
    public function section_footer(){
        return parent::section_footer();
    }
    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        $sectionname = $this->render(course_get_format($course)->inplace_editable_render_section_name($section));

        $level = "";

        $radiosectionlevel = '<label><input name="ttRadioSectionLevel%ld" data-component="ttRadioSectionLevel" type="radio" value="%s"  %s> %s</label>';

        $level = "";
        if ($section->section > 0) {
            $level = sprintf('<form class="inline-form-editing-mode">%s%s%s</form>',
            sprintf($radiosectionlevel, $section->section, "1",
                    ($section->ttsectiondisplay == 1 ? "checked" : ""), get_string('displaytabslev1', 'format_recit')),
            sprintf($radiosectionlevel, $section->section, "2",
                    ($section->ttsectiondisplay == 2 ? "checked" : ""), get_string('displaytabslev2', 'format_recit')),
            "");
        }

        $html = sprintf("<span style='display: flex; align-items: center; '>%s%s</span>", $sectionname, $level);

        return $html;
    }
}
