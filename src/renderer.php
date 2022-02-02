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

//js_reset_all_caches();
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
            $html .= $this->getHtmlLoading();
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
            if(($id == $sectionid) || ("#$id" == $sectionid)) { 
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
        if ($section->available){
            
                // Show summary if section is available or has
            $sectionsummary = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php', $context->id, 'course', 'section',
                    $section->id);

            $sectionsummary = format_text($sectionsummary,  $section->summaryformat, array('noclean' => true, 'overflowdiv' => true,
                    'filter' => true));
        }
        $html = "<div class='section main clearfix tt-section $sectionstyle' data-section='$sectionid' role='region' aria-label='$sectionname'>";

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
        return sprintf("#section-%d", $section->section);
    }

    /**
     * Function to get section name of TreeTopics.
     * @param object $section
     * @return string
     */
    public function get_section_name($section) {
        return (empty($section->name) ? get_string('section') . '' . $section->section : $section->name);
    }

    public function get_last_section(){
        return end($this->sectionslist);
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
        global $CFG, $COURSE, $OUTPUT;
        $this->renderer = $format_recit_renderer;

        $completioninfo = new completion_info($this->course);

        $selectedSection = (isset($_COOKIE["course-{$COURSE->id}-cursection"]) ? $_COOKIE["course-{$COURSE->id}-cursection"] : '#menu');
        $selectedSectionNumber = (int)filter_var($selectedSection, FILTER_SANITIZE_NUMBER_INT);
        if ($selectedSection == '#menu') $selectedSectionNumber = 'menu';


        $massaction = "<div class='bg-light p-3'>";

        $massaction .= '<p><b>Avec la sélection : </b></p>';
        $massaction .= "<div class='d-flex'>";

        $massaction .= "<div class='d-flex' style='align-items: center;'>";
        $massaction .= "<a href='#' class='recitformat_massdelete btn btn-danger' data-section='%s'><i class='fa fa-trash'></i> Supprimer</a>";
        $massaction .= "<a href='#' class='recitformat_massshow btn btn-primary ml-2' data-section='%s'><i class='fa fa-eye'></i> Afficher</a>";
        $massaction .= "<a href='#' class='recitformat_masshide btn btn-primary ml-2' data-section='%s'><i class='fa fa-eye-slash'></i> Cacher</a>";
        $massaction .= "</div>";

        $massaction .= "<div class='ml-5 mr-5' style='border-left: 1px solid #efefef;'></div>";

        $massaction .= "<div class=''>";
        $massaction .= "<select class='recitformat_massmove custom-select w-100' data-section='%s'>";
        
        $massaction .= "<option value='' disabled selected>".get_string('movecm', 'format_recit')."</option>";
        foreach ($this->sectionslist as $section) {
            $sectionId = $this->get_section_id($section);
            $sectionName = $this->get_section_name($section);
            $massaction .= "<option value='{$section->section}'>{$sectionName}</option>";
        }
        $massaction .= "</select> ";
        $massaction .= "</div>";

        $massaction .= "</div>";
        $massaction .= "</div>";

        $data = new stdClass();
        $data->sectionList = array();

        $data->menu = new stdClass();
        $data->menu->desc = "Configurer le menu et les activités";
        $data->menu->sectionId = "#menu";
        $data->menu->sectionIdAlt = "menu";
        $data->menu->sectionIdAlt2 = 'menu';
        $data->menu->active = ($selectedSection == "#menu" ? 'active' : '');
        $data->menu->addSectionUrl = "{$CFG->wwwroot}/course/changenumsections.php?courseid={$COURSE->id}&insertsection=0&sesskey=".sesskey()."&sectionreturn=0";
        $data->menu->content = $completioninfo->display_help_icon();        

        foreach ($this->sectionslist as $section) {
            $sectionId = $this->get_section_id($section);

            $data->menu->content .= $format_recit_renderer->section_header($section, $this->course, false, 0, false);
            $data->menu->content .= html_writer::start_tag('div', array('class' => 'collapse show', 'id' => 'collapse-section-'.$section->section));
            $data->menu->content .= sprintf("<div class='section_add_menus' id='add_menus-%s'></div>", $sectionId);
            $data->menu->content .= "<div data-course-section-cm-list='1'>". $this->get_course_section_cm_list_editing($this->course, $section)."</div>";
            $data->menu->content .= sprintf($massaction, $section->section, $section->section, $section->section, $section->section);
            $data->menu->content .= html_writer::end_tag('div');
            $data->menu->content .= $format_recit_renderer->section_footer();

            $item = new stdClass();
            $item->desc =  $this->get_section_name($section);
            $item->sectionId = $sectionId;
            $item->sectionIdAlt = "isection-{$section->section}";
            $item->sectionIdAlt2 = $section->section;
            $item->active = ($selectedSectionNumber === $section->section ? 'active' : '');
            $item->active .= ($section->sectionlevel == 2 ? ' ml-3' : '');
            $item->editingUrl = "{$CFG->wwwroot}/course/editsection.php?id= $section->id&sr";
            $item->content = $completioninfo->display_help_icon();
            $item->content .= $format_recit_renderer->section_header($section, $this->course, false, 0, true, false);
            $item->content .= $format_recit_renderer->get_course_section_cm_list($this->course, $section);
            $item->content .= $format_recit_renderer->get_course_section_add_cm_control($this->course, $section->section);
            $item->content .= $format_recit_renderer->section_footer();
            $data->sectionList[] = $item;
        }
        
        return $OUTPUT->render_from_template('format_recit/editing_mode', $data);
    }

    protected function get_course_section_cm_list_editing($course, $section, $sectionreturn = null, $displayoptions = []) {
        global $USER;

        $output = '';
        $format = course_get_format($course);
        $modinfo = get_fast_modinfo($course);
        if (is_object($section)) {
            $section = $modinfo->get_section_info($section->section);
        } else {
            $section = $modinfo->get_section_info($section);
        }
        
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];
                if ($modulehtml = $this->course_section_cm_editing($course, $completioninfo, $mod, $sectionreturn, $displayoptions, $section)) {
                    $modclasses = 'activity ' . $mod->modname . ' modtype_' . $mod->modname . ' ' . $mod->extraclasses;
                    $output .= html_writer::tag('li', $modulehtml, array('class' => $modclasses, 'id' => 'module-' . $mod->id, 'style' => 'list-style:none'));
                }
            }
        }
        return html_writer::tag('ul', $output, array('class' => 'section img-text'));
    }

    
    public function course_section_cm_editing($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array(), $section) {
        global $PAGE;
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->is_visible_on_course_page()) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        /*if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }*/

        $output .= html_writer::start_tag('div');

        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer w-100 d-inline'));

        if ($PAGE->user_is_editing()) {
            $output .= "<input type='checkbox' class='massactioncheckbox' data-section='$section->section' name='".$mod->id."'/>";
        }

        // This div is used to indent the content.
        //$output .= html_writer::div('', $indentclasses);

        // Start a wrapper for the actual content to keep the indentation consistent
        $output .= html_writer::start_tag('div', array('class' => 'd-inline'));

        // Display the link to the module (or do nothing if module has no url)
        $cmname = $mod->name;

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= html_writer::start_tag('div', array('class' => 'activityinstance ml-2'));
                
            $onclick = htmlspecialchars_decode($mod->onclick, ENT_QUOTES);

            // Display link itself.
            $activitylink = html_writer::empty_tag('img', array('src' => $mod->get_icon_url(),
                    'class' => 'iconlarge activityicon', 'alt' => '', 'role' => 'presentation', 'aria-hidden' => 'true')) .
                    html_writer::tag('span', $cmname, array('class' => 'instancename'));
            $output .= html_writer::link($mod->url, $activitylink, array('class' => 'aalink', 'onclick' => $onclick));


            // Module can put text after the link (e.g. forum unread)
            $output .= $mod->afterlink;
            // Show availability info (if module is not available).
            if (!$mod->visible) {
                $output .= html_writer::tag('span', get_string('hiddenfromstudents'), array('class' => 'badge badge-info'));
            }

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div'); // .activityinstance
        }

        $output .= html_writer::end_tag('div'); // $indentclasses

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Displays availability info for a course section or course module
     *
     * @param string $text
     * @param string $additionalclasses
     * @return string
     */
    public function availability_info($text, $additionalclasses = '') {

        $data = ['text' => $text, 'classes' => $additionalclasses];
        $additionalclasses = array_filter(explode(' ', $additionalclasses));

        if (in_array('ishidden', $additionalclasses)) {
            $data['ishidden'] = 1;

        } else if (in_array('isstealth', $additionalclasses)) {
            $data['isstealth'] = 1;

        } else if (in_array('isrestricted', $additionalclasses)) {
            $data['isrestricted'] = 1;

            if (in_array('isfullinfo', $additionalclasses)) {
                $data['isfullinfo'] = 1;
            }
        }

        return $this->render_from_template('core/availability_info', $data);
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
            $this->formatrecit->load($this, course_get_format($course)->get_course());
            echo $this->formatrecit->render_editing_mode($this);
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

        $o .= html_writer::start_tag('li', array('id' => 'section-'.$section->section, //This id cannot be changed or dragndrop will break
            'class' => "section main clearfix sectiondraggable yui3-dd-drop".$sectionstyle, 'role' => 'region',
            'aria-label' => get_section_name($course, $section), "data-section-level" => $section->sectionlevel,
            "data-section-id" => $section->id, 'style' => 'list-style: none;') );


        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $o .= html_writer::tag('span', get_section_name($course, $section), array('class' => "hidden sectionname $display"));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o .= html_writer::tag('div', $leftcontent, array('class' => "left side $display"));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        //$o .= html_writer::tag('div', $rightcontent, array('class' => "right side $display"));
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null.
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one.
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }
        $sectionname = html_writer::tag('span', $this->section_title($section, $course), array('class' => $display . $classes));
        $o .= $sectionname;

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
        global $CFG;
        $lastSection = $this->formatrecit->get_last_section();
        $editSectionUrl = "{$CFG->wwwroot}/course/editsection.php?id={$section->id}&sr";
        $hideSectionUrl = "{$CFG->wwwroot}/course/view.php?id={$course->id}&".($section->visible == 1 ? 'hide' : 'show')."={$section->section}&sesskey=".sesskey();
        $upSectionUrl = "{$CFG->wwwroot}/course/view.php?id={$course->id}&section={$section->section}&move=-1&sesskey=".sesskey();
        $downSectionUrl = "{$CFG->wwwroot}/course/view.php?id={$course->id}&section={$section->section}&move=1&sesskey=".sesskey();
        $sectionname = '';
        if ($section->section > 0){
            //$sectionname .= '<span class="section-handle moodle-core-dragdrop-draghandle" title="Déplacer '.$this->formatrecit->get_section_name($section).'" tabindex="0" data-draggroups="sectiondraggable" role="button">          <i class="icon fa fa-arrows fa-fw " aria-hidden="true" style="cursor: move;"></i>            </span>';
        }
        $sectionname .= "<a class='accordion-toggle h3' data-toggle=\"collapse\" data-target=\"#collapse-section-".$section->section."\" href='#section-".$section->section."'> ".$this->formatrecit->get_section_name($section)."</a>";
        $sectionname .= " <a class='ml-1 btn-sm' data-toggle='pill' title='Voir la section' role='tab' aria-controls='isection-".$section->section."' href='#isection-".$section->section."' onclick=\"M.recit.course.format.recit.EditingMode.instance.goToSection(event, true)\"><i class='fa fa-sign-in'></i></a>";
        $sectionname .= " <a href='$editSectionUrl' title='Modifier la section' class='ml-2'><i class='fa fa-pencil'></i></a>";
        $sectionname .= " <a href='$hideSectionUrl' title='Cacher/montrer la section' class='ml-2'><i class='fa ".($section->visible == 1 ? 'fa-eye' : 'fa-eye-slash')."'></i></a>";
        if ($section->section > 1){
            $sectionname .= " <a href='$upSectionUrl' title='Monter la section' class='ml-2'><i class='fa fa-arrow-up'></i></a>";
        }
        if ($section->section > 0 && $section->section != $lastSection->section){
            $sectionname .= " <a href='$downSectionUrl' title='Descendre la section' class='ml-2'><i class='fa fa-arrow-down'></i></a>";
        }
        $sectionname .= " <a href='#' title='Supprimer la section' class='ml-2' onclick=\"M.recit.course.format.recit.EditingMode.instance.deleteSection(".$section->section.")\"><i class='fa fa-trash'></i></a>";

        $level = "";

        $radiosectionlevel = '<label class="ml-2 mb-0" style="align-self: center"><input name="ttRadioSectionLevel%ld" data-component="ttRadioSectionLevel" type="radio" value="%s"  %s> %s</label>';

        $level = "";
        if ($section->section > 0) {
            $level = sprintf('<form class="d-flex ml-3">%s%s%s</form>',
            sprintf($radiosectionlevel, $section->section, "1",
                    ($section->sectionlevel == 1 ? "checked" : ""), get_string('displaytabslev1', 'format_recit')),
            sprintf($radiosectionlevel, $section->section, "2",
                    ($section->sectionlevel == 2 ? "checked" : ""), get_string('displaytabslev2', 'format_recit')),
            "");
        }

        $html = sprintf("<span style='display: flex; align-items: center; height: 30px;'>%s%s</span>", $sectionname, $level);

        return $html;
    }
}
