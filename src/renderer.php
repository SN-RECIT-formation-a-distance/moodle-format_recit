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
    protected $lazyLoading = false;
    /** @var array */
    public $sectionslist = array();

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
        $this->lazyLoading = ($course->ttloadingtype == 1);
    }

    /**
     * Function render of FormatRecit.
     *
     * @return string
     */
    public function render() {
        $html = "<div id='sectioncontent_placeholder' data-lazyloading='".($this->lazyLoading ? 1 : 0)."'></div>";
        if ($this->lazyLoading){
            $html .= $this->getHtmlLoading();
        }else{
            foreach ($this->sectionslist as $item) {
                if( !$item->visible ){ continue; }
    
                $id = $this->get_section_id($item);
                $html .= $this->render_section_content($id);
            }
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
        global $USER, $CFG;
        $section = null;
        foreach ($this->sectionslist as $item) {

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
        $course = course_get_format($this->course)->get_course();
        $hideVisible = isset($course->hiddensections) ? $course->hiddensections : 1; //0 = show for teachers, 1 = hidden for everyone

        $sectionstyle = '';

        if (!$section->visible ) {
            //$sectionstyle = ' hidden';//Disabled for format option hiddensections
        }
        if (course_get_format($this->course)->is_section_current($section)) {
            $sectionstyle = ' current';
        }

        $content = "";
        if ($section->ttsectionshowactivities == 1) {
            $content = $this->moodlerenderer->get_course_section_cm_list($this->course, $section);
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
        $html = "<div class='section main clearfix tt-section $sectionstyle' ".($this->lazyLoading ? '' : 'style="display:none"')." data-section='$sectionid' role='region' aria-label='$sectionname'>";
 
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


    public function render_editing_mode($format_recit_renderer){
        global $CFG, $COURSE, $OUTPUT;
        $this->renderer = $format_recit_renderer;

        $completioninfo = new completion_info($this->course);

        $selectedSection = (isset($_COOKIE["course-{$COURSE->id}-cursection"]) ? $_COOKIE["course-{$COURSE->id}-cursection"] : '#menu');
        $selectedSectionNumber = (int)filter_var($selectedSection, FILTER_SANITIZE_NUMBER_INT);
        if ($selectedSection == '#menu') $selectedSectionNumber = 'menu';


        $massaction = "<div class='p-3'>";

        $massaction .= '<p><b>Avec la sélection : </b></p>';
        $massaction .= "<div class='d-flex'>";

        $massaction .= "<div class='d-flex' style='align-items: center;'>";
        $massaction .= "<a href='#' class='recitformat_massdelete btn btn-danger btn-sm ' data-section='%s'><i class='fa fa-trash'></i> Supprimer</a>";
        $massaction .= "<a href='#' class='recitformat_massshow btn btn-primary btn-sm ml-2' data-section='%s'><i class='fa fa-eye'></i> Afficher</a>";
        $massaction .= "<a href='#' class='recitformat_masshide btn btn-primary btn-sm ml-2' data-section='%s'><i class='fa fa-eye-slash'></i> Cacher</a>";
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

        $sesskey = sesskey();

        $data->menu = new stdClass();
        $data->menu->desc = "Configurer le menu et les activités";
        $data->menu->sectionId = "#menu";
        $data->menu->sectionIdAlt = "menu";
        $data->menu->sectionIdAlt2 = 'menu';
        $data->menu->active = ($selectedSection == "#menu" ? 'active' : '');
        $data->menu->addSectionUrl = "{$CFG->wwwroot}/course/changenumsections.php?courseid={$COURSE->id}&insertsection=0&sesskey=$sesskey&sectionreturn=0";
        $data->menu->content = '';        

        foreach ($this->sectionslist as $section) {
            $sectionId = $this->get_section_id($section);

            $data->menu->content .= $format_recit_renderer->section_header($section, $this->course, false, 0, false, true, true);
            $data->menu->content .= html_writer::start_tag('div', array('class' => 'collapse show bg-light pt-2', 'id' => 'collapse-section-'.$section->section));
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
            $item->sectionIdAlt3 = $section->id;
            $item->active = ($selectedSectionNumber === $section->section ? 'active' : '');
            $item->active .= ($section->sectionlevel == 2 ? ' ml-3' : '');
            $item->editingUrl = "{$CFG->wwwroot}/course/editsection.php?id=$section->id&sr";
            if ($COURSE->marker == $section->section){
                $item->markUrl = "{$CFG->wwwroot}/course/view.php?id=$COURSE->id&sesskey=$sesskey&marker=0&sr";
                $item->markLabel = get_string('highlightoff');
            }else{
                $item->markLabel = get_string('highlight');
                $item->markUrl = "{$CFG->wwwroot}/course/view.php?id=$COURSE->id&sesskey=$sesskey&marker={$section->section}&sr";
            }
            if ($section->visible){
                $item->hideUrl = "{$CFG->wwwroot}/course/view.php?id=$COURSE->id&sesskey=$sesskey&hide={$section->section}&sr";
                $item->hideLabel = get_string('hide');
            }else{
                $item->hideUrl = "{$CFG->wwwroot}/course/view.php?id=$COURSE->id&sesskey=$sesskey&show={$section->section}&sr";
                $item->hideLabel = get_string('show');
            }
            $item->deleteUrl = "{$CFG->wwwroot}/course/editsection.php?id=$section->id&sr&delete=1";
            $item->content = $format_recit_renderer->section_header($section, $this->course, false, 0, true, false);
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

    
    public function course_section_cm_editing($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions, $section) {
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
            $output .= "<input type='checkbox' class='massactioncheckbox mr-2' data-section='$section->section' name='".$mod->id."'/>";
        }

        // This div is used to indent the content.
        //$output .= html_writer::div('', $indentclasses);

        // Start a wrapper for the actual content to keep the indentation consistent
        $output .= html_writer::start_tag('div', array('class' => 'd-inline'));

        // Display the link to the module (or do nothing if module has no url)
        $cmname = $mod->name;

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= html_writer::start_tag('div', array('class' => 'd-inline-flex align-items-center'));
                
            $onclick = htmlspecialchars_decode($mod->onclick, ENT_QUOTES);

            // Display link itself.
            $imagedata = $this->moodlerenderer->pix_icon('monologo', '', $mod->modname, ['class' => 'activityicon']);
            $purposeclass = plugin_supports('mod', $mod->modname, FEATURE_MOD_PURPOSE);
            $purposeclass .= ' activityiconcontainer small2';
            $purposeclass .= ' modicon_' . $mod->modname;
            $imagedata = html_writer::tag('div', $imagedata, ['class' => $purposeclass]);
            $activitylink = $imagedata .
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
class format_recit_renderer extends core_courseformat\output\section_renderer {

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
        $format = course_get_format($course);
        $cmlistclass = $format->get_output_classname('content\\section\\cmlist');

        return $this->render(new $cmlistclass($format, $section));
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
    public function section_header($section, $course, $onsectionpage, $sectionreturn=null, $showsectionsummary=true, $showsectiondetails = true, $onmenupage = false) {

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

        //$rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        //$o .= html_writer::tag('div', $rightcontent, array('class' => "right side $display"));
        $contentclass = "";
        if ($onmenupage) $contentclass = "border p-2";
        $o .= html_writer::start_tag('div', array('class' => 'content '.$contentclass));

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
     * Generate the content to displayed on the left part of a section
     * before course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    protected function section_left_content($section, $course, $onsectionpage) {

        $o = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (course_get_format($course)->is_section_current($section)) {
                $o = get_accesshide(get_string('currentsection', 'format_' . $course->format));
            }
        }

        return $o;
    }

    /**
     * Generate html for a section summary text
     *
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
    protected function format_summary_text($section) {

        $format = course_get_format($section->course);
        if (!($section instanceof section_info)) {
            $modinfo = $format->get_modinfo();
            $section = $modinfo->get_section_info($section->section);
        }
        $summaryclass = $format->get_output_classname('content\\section\\summary');
        $summary = new $summaryclass($format, $section);
        return $summary->format_summary_text();
    }

    public function section_availability($section){
        
        $context = context_course::instance($section->course);
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);
        $course = $section->course;
        $format = course_get_format($course);
        $widgetclass = $format->get_output_classname('content\\section\\availability');
        $widget = new $widgetclass($format, $section);
        
        return html_writer::div($this->render($widget), 'section_availability');
    }

    /**
     * Generate footer html of a stealth section
     *
     * @return string HTML to output.
     */
    public function section_footer(){
        $o = html_writer::end_tag('div');
        $o .= html_writer::end_tag('li');
        return $o;
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
        $moveSectionUrl = "{$CFG->wwwroot}/course/view.php?id={$course->id}&section={$section->section}&move=%s&sesskey=".sesskey();
        $upSectionUrl = sprintf($moveSectionUrl, '-1');
        $downSectionUrl = sprintf($moveSectionUrl, '1');
        $sectionname = '';
        $sectionactions = '';
        if ($section->section > 0){
            //$sectionname .= '<span class="section-handle moodle-core-dragdrop-draghandle" title="Déplacer '.$this->formatrecit->get_section_name($section).'" tabindex="0" data-draggroups="sectiondraggable" role="button">          <i class="icon fa fa-arrows fa-fw " aria-hidden="true" style="cursor: move;"></i>            </span>';
        }
        $sectionname .= "<a class='accordion-toggle h3' data-toggle=\"collapse\" data-target=\"#collapse-section-".$section->section."\" href='#section-".$section->section."'> ".$this->formatrecit->get_section_name($section)."</a>";
        if ($section->section > 0){
            $sectionactions .= $this->get_move_section_select($section, $moveSectionUrl);
        }

        $sectionactions .= " <a href='$hideSectionUrl' title='Cacher/montrer la section' class='ml-2'><i class='fa ".($section->visible == 1 ? 'fa-eye' : 'fa-eye-slash')."'></i></a>";
        if ($section->section > 1){
            $sectionactions .= " <a href='$upSectionUrl' title='Monter la section' class='ml-2'><i class='fa fa-arrow-up'></i></a>";
        }
        if ($section->section > 0 && $section->section != $lastSection->section){
            $sectionactions .= " <a href='$downSectionUrl' title='Descendre la section' class='ml-2'><i class='fa fa-arrow-down'></i></a>";
        }
        $sectionactions .= " <a href='#' title='Supprimer la section' class='ml-2' onclick=\"M.recit.course.format.recit.EditingMode.instance.deleteSection(".$section->section.")\"><i class='fa fa-trash'></i></a>";

        $level = "";

        $radiosectionlevel = '<label class="ml-2 mb-0" style="align-self: center"><input name="ttRadioSectionLevel%ld" data-component="ttRadioSectionLevel" type="radio" value="%s"  %s> %s</label>';

        $level = "";
        if ($section->section > 0) {
            $level = sprintf('<form class="d-flex m-3">%s%s%s</form>',
            sprintf($radiosectionlevel, $section->section, "1",
                    ($section->sectionlevel == 1 ? "checked" : ""), get_string('displaytabslev1', 'format_recit')),
            sprintf($radiosectionlevel, $section->section, "2",
                    ($section->sectionlevel == 2 ? "checked" : ""), get_string('displaytabslev2', 'format_recit')),
            "");
        }

        $goToSection = " <a class='btn btn-primary' data-toggle='pill' title='Accéder à la section' role='tab' aria-controls='isection-".$section->section."' href='#isection-".$section->section."' onclick=\"M.recit.course.format.recit.EditingMode.instance.goToSection(event, true)\"><i class='fa fa-sign-in'></i> Accéder à la section</a>";

        $html = sprintf("%s<div class='float-sm-right m-2'>%s</div><div class='m-2 d-flex align-items-center flex-wrap'>%s%s</div>", $sectionname, $goToSection, $sectionactions, $level);

        return $html;
    }

    public function get_move_section_select($section, $moveSectionUrl){
        $sectionname = '';
        
        $sectionname .= "<select class='recitformat_massmovesect custom-select ml-2' data-section='%s'>";
            
        $sectionname .= "<option value='' disabled selected>".get_string('movecm', 'format_recit')."</option>";
        $index = 0;
        $index2 = 0;
        foreach ($this->formatrecit->sectionslist as $sectiono) {
            // ignore section 0
            if($sectiono->section == 0){
                continue;
            }

            if ($section->section == $sectiono->section){
                break;
            }
            $index++;
        }

        foreach ($this->formatrecit->sectionslist as $sectiono) {
            // ignore section 0
            if($sectiono->section == 0){
                continue;
            }

            $sectionName = $this->formatrecit->get_section_name($sectiono);
            $url = sprintf($moveSectionUrl, 0-($index-$index2));
            $sectionname .= "<option value='{$url}'>{$sectionName}</option>";
            $index2++;
        }
        $sectionname .= "</select> ";
        return $sectionname;
    }
}
