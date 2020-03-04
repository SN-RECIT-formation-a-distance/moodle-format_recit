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
require_once($CFG->dirroot.'/course/format/renderer.php');
//require_once($CFG->dirroot.'/filter/recitactivity/filter.php');

//js_reset_all_caches();

class TreeTopics 
{
    const ID_APPEND = 'tt-';

    protected $moodleRenderer = null;
    protected $course = null;
    protected $output = null;
    protected $modinfo = null;
    protected $courseFormat = null;
    protected $sectionList = array();
    protected $sectionTree = array();
   // protected $autoLinkFilter = null;

    public function __construct(){
        global $COURSE;
        $context = context_course::instance($COURSE->id);
    //    $this->autoLinkFilter = new filter_recitactivity($context, array());
    }

    public function render($moodleRenderer, $course){
        global $PAGE;
        
        $this->moodleRenderer = $moodleRenderer;
        $this->course = $course;

        $this->signingContract();

        $this->modinfo = get_fast_modinfo($course);
        $this->courseFormat = course_get_format($course);
        $this->sectionList = $this->modinfo->get_section_info_all();

        $orientation = ($this->isMenuHorizontal() ? "horizontal" : "vertical");
        $html = "<div class='treetopics $orientation'>%s</div>";

        if($this->showContract()){
            $html = sprintf($html, $this->renderContract());    
        }
        else{
            $this->createSectionTree();
            $html = sprintf($html, $this->renderSections());
            $html .= $this->renderPagination();
        }

        echo $html;
    }

    protected function createSectionTree(){
        $this->sectionTree = array();

        foreach($this->sectionList as $section){
            switch($section->ttsectiondisplay){
                case 1:
                    $item1 = new stdClass();
                    $item1->section = $section;
                    $item1->child = array();
                    $this->sectionTree[] = $item1;
                    break;
                case 2:
                    $item2 = new stdClass();
                    $item2->section = $section;
                    $item2->child = array();
                    $item1->child[] = $item2;
                    break;
                case 3:
                    $item3 = new stdClass();
                    $item3->section = $section;
                    $item3->child = array();
                    $item2->child[] = $item3;
                    break;
            }
        }
    }

    protected function renderSections(){
        // la section 0 controle le mode d'affichage
        $mode = $this->sectionList[0]->ttsectioncontentdisplay;

        $menu = "";
        if($mode == TT_DISPLAY_TABS){
            $menu = $this->renderSectionMenu();    
        }
        
        $content = sprintf("<div>%s</div>", $this->renderSectionContent());

        return ($this->isMenuHorizontal() == 1 ? $menu.$content : $content.$menu);
    }

    protected function renderSectionMenu(){
        $navbar = "<nav class='navbar navbar-dark %s theme-bg-color' id='tt-recit-nav'>%s</nav>";

        $collapse = "<button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#navbarTogglerCourse' aria-controls='navbarTogglerCourse' aria-expanded='false' aria-label='Toggle navigation'>
                        <span class='navbar-toggler-icon'></span>
                    </button>
                    <div class='collapse navbar-collapse' id='navbarTogglerCourse'>
                        %s
                    </div>";

        $menuItems = "<ul class='navbar-nav mr-auto mt-2 mt-lg-0'>%s</ul>";

        $hMenu = sprintf($navbar, "navbar-expand-lg",  sprintf($collapse, $menuItems));
        $vMenu = sprintf($navbar, "flex-column", $menuItems);
        $html = ($this->isMenuHorizontal() ? $hMenu : $vMenu);

        $tmp1 = "";
        $tmp2 = "";
        $tmp3 = "";
        foreach($this->sectionTree as $item1){
            foreach($item1->child as $item2){
                foreach($item2->child as $item3){
                    $tmp3 .= $this->renderSectionMenuItem($item3->section);
                }
                $tmp2 .= $this->renderSectionMenuItem($item2->section, $tmp3);
                $tmp3 = "";
            }
            $tmp1 .= $this->renderSectionMenuItem($item1->section, $tmp2);
            $tmp2 = "";
        }

        return sprintf($html, $tmp1);
    }

    protected function renderSectionMenuItem($section, $subSection = ""){
        $html = "";

        $sectionid = $this->get_section_id($section);

        if ($section->ttsectioncontentdisplay == TT_DISPLAY_TABS){
            if($section->ttsectiondisplay == 1){
                if(empty($subSection)){
                    $html = "<li class='nav-item'>
                        <a class='nav-link' href='#' data-section='$sectionid' onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>". $this->getSectionName($section) ."</a>
                    </li>";
                }
                else{
                    $dropdownid = $sectionid.'DropdownMenuLink';
                    $html = "<li class='nav-item dropdown'>
                                <a class='nav-link dropdown-toggle' data-section='$sectionid' href='#' id='$dropdownid' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>". $this->getSectionName($section) ."</a>
                                <ul class='dropdown-menu theme-bg-color' aria-labelledby='$dropdownid'>$subSection</ul>
                            </li>";                
                }
            }
            else if($section->ttsectiondisplay == 2){
                if(empty($subSection)){
                    $html = "<li>
                        <a class='dropdown-item' href='#' data-section='$sectionid' onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>". $this->getSectionName($section) ."</a>
                    </li>";
                }
                else{
                    $dropdownid = $sectionid.'DropdownMenuLink';
                    $html = "<li class='dropdown-submenu'>
                                <a class='dropdown-item dropdown-toggle' data-section='$sectionid' href='#' id='$dropdownid' onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>". $this->getSectionName($section) ."</a>
                                <ul class='dropdown-menu theme-bg-color'>$subSection</ul>
                            </li>";                
                }
            }
            else if($section->ttsectiondisplay == 3){
                $html = "<li class=''>
                        <a class='dropdown-item' href='#' data-section='$sectionid' onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>". $this->getSectionName($section) ."</a>
                    </li>";
            }
        }

        return $html;
    }

    protected function renderSectionContent(){
        $tmp1 = "";
        $tmp2 = "";
        $tmp3 = "";
        foreach($this->sectionTree as $item1){
            foreach($item1->child as $item2){
                foreach($item2->child as $item3){
                    $tmp3 .= $this->renderSectionContentItem($item3->section);
                }

                if((isset($item2->child[0])) && ($item2->child[0]->section->ttsectioncontentdisplay == TT_DISPLAY_IMAGES)){
                    $tmp2 .= $this->renderSectionContentItem($item2->section, $tmp3);
                }
                else{
                    $tmp2 .= $tmp3;
                    $tmp2 .= $this->renderSectionContentItem($item2->section);
                }
                
                $tmp3 = "";
            }

            if((isset($item1->child[0])) && ($item1->child[0]->section->ttsectioncontentdisplay == TT_DISPLAY_IMAGES)){
                $tmp1 .= $this->renderSectionContentItem($item1->section, $tmp2);
            }
            else{
                $tmp1 .= $tmp2;
                $tmp1 .= $this->renderSectionContentItem($item1->section);
            }
            $tmp2 = "";
        }

        return $tmp1;
    }
    
    protected function renderSectionContentItem($section, $subContent = ""){
        // the section 0 is required to display as tab in order to display all the children
        if(($section->ttsectioncontentdisplay == TT_DISPLAY_IMAGES) && ($section->section > 0)){
           $html = $this->getSectionDisplayImage($section, $subContent);
        }
        else{
            $html = $this->getSectionDisplayTab($section, $subContent);
        }

        return $html;
    }

    protected function getSectionDisplayImage($section, $subContent){
        $sectionId = $this->get_section_id($section);
        $sectionName =  $this->getSectionName($section);

        $format_options = format_base::instance($this->course)->get_format_options($section);
        //$url = moodle_url::make_pluginfile_url($format_options['ttsectionimage-context'], $format_options['ttsectionimage-component'], $format_options['ttsectionimage-filearea'], $format_options['ttsectionimage-itemid'], $format_options['ttsectionimage-filepath'], $format_options['ttsectionimage-filename']);        
        //$imgSource = $url->out(false);
        $imgSource = (isset($format_options['ttsectionimageurl']) ? $format_options['ttsectionimageurl'] : "");

        $sectionTitle = ($format_options['ttsectiontitle'] ? "<div class='tt-section-title'>$sectionName</div>" : "");
        //$sectionSummary = $this->autoLinkFilter->filter($section->ttsectionimagesummary_editor);
		$sectionSummary = format_text($section->summary, FORMAT_MOODLE, array('noclean' => true,'filter' => true));
        $content = "";
        if($section->ttsectionshowactivities == 1){
            $content = $this->moodleRenderer->getCourseSectionCmList($this->course, $section);
        }
        $html = 
        "<div id='$sectionId' class='tt-imagebuttons auto2' data-section='$sectionId'>
            <div class='tt-section-image-link tt-grid-element tt-section-image-link-selected' data='$sectionId' style='position:relative;'>
                <img class='tt-section-image' src='$imgSource' alt='$sectionName'>
                $sectionTitle
                $sectionSummary
                $content
                <div style='display: flex;'>$subContent</div>
            </div>
        </div>";

        return $html;
    }    
/*protected function format_summary_text($section) {
        $context = context_course::instance($section->course);
        $summarytext = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php',
            $context->id, 'course', 'section', $section->id);

        $options = new stdClass();
        $options->noclean = true;
        $options->overflowdiv = true;
		$options->filter = true;
        return format_text($summarytext, $section->summaryformat, $options);
    }*/
	
    protected function getSectionDisplayTab($section, $subContent){
        $sectionId = $this->get_section_id($section);
        $sectionName =  $this->getSectionName($section);
        $sectionAvail = $this->moodleRenderer->section_availability($section);

        $sectionStyle = '';

        if (!$section->visible){
            $sectionStyle = ' hidden';
        }
        if (course_get_format($this->course)->is_section_current($section)) {
            $sectionStyle = ' current';
        }

        $content = "";
        if($section->ttsectionshowactivities == 1){
            $content = $this->moodleRenderer->getCourseSectionCmList($this->course, $section);
        }

        /*if($section->section == 0){
            $nextSection = $this->sectionList[1];
            $nextId = $this->get_section_id($nextSection);
            $content .= "<a href='#' data-section='$sectionId' class='btn btn-primary' id='tt-btn-start-course'  onclick='M.recit.course.format.TreeTopics.instance.goToSection(null,\"$nextId\")'>Commencer le cours</a>";
        }*/

        // prepare the container to receive the section display image
        $colSize = 100 / max($this->course->ttimagegridcolumns, 1);
        $griTemplateCols = "";
        for($i = 0; $i < $this->course->ttimagegridcolumns; $i++){
            $griTemplateCols .= "$colSize% ";
        }
$sectionSummary = format_text($section->summary, FORMAT_MOODLE, array('noclean' => true,'filter' => true));
       // $sectionSummary = $this->autoLinkFilter->filter($section->summary);
        $html = "<div id='$sectionId' class='section main clearfix tt-section $sectionStyle' role='region' aria-label='$sectionName' style='display: none;'>
                    <h2>$sectionName</h2>
                    <div class='content'>
                        $sectionAvail
                        <div class='summary'>$sectionSummary</div>
                        $content
                        <!-- sections display image type container -->
                        <div class='grid{$this->course->ttimagegridcolumns}' style='display: grid;  grid-template-columns: $griTemplateCols'>$subContent</div>
                    </div>
                </div>";

        return $html;
    }

    protected function isMenuHorizontal(){
        return ($this->course->ttmenudisplay == 0);
    }

    protected function renderPagination(){
        $result = "<nav id='sectionPagination' aria-label='Pagination de sections' style='margin-top: 3rem; border-top: 1px solid #efefef; padding-top: 1rem;'>";
        $result .= '<ul class="pagination justify-content-center ">';
        $result .= '<li class="page-item">';
        $result .= "<a class='page-link' href='#' tabindex='-1' onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'><i class='fa fa-arrow-left'></i> Précédente</a>";
        $result .= '</li>';
        $result .= '<li class="page-item">';
        $result .= "<a class='page-link' href='#' tabindex='-1'  onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>Suivante <i class='fa fa-arrow-right'></i></a>";
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</nav>';
 
        return $result;
    }

	/**
     * Generate a section's id
     *
     * @param stdClass $section The course_section entry from DB
     * @return the section's string id
     */
	protected function get_section_id($section){
        //$specialchars = array("'", '"', '\\', '/', '@', '$', '%', '!', '#', '?', '&', '*', '(', ')', '+', ' ', '-', '=', ';', ':', '^', '`', '<', '>', '«', '»', '.');
        //return self::ID_APPEND . str_replace($specialchars, '', get_section_name($this->course, $section));
        return sprintf("section-%d", $section->section);
    }
    
    protected function getSectionName($section) {
        return (empty($section->name) ? get_string('section') . '' . $section->section : $section->name);
    }

    protected function showContract(){
        return ($this->course->tthascontract) && (!$this->contract_is_signed());
    }

    protected function signingContract(){
        if((isset($_GET["ttc"])) && ($_GET["ttc"] == '1')){
            $this->contract_sign();
        }
        /*else{
            $this->contract_unsign();
        }*/
    }

    protected function contract_is_signed(){
        global $DB, $USER;
        $result = $DB->record_exists('format_treetopics_contract', ['courseid' => $this->course->id, 'userid' => $USER->id]);
        return $result;
    }
    
    protected function contract_sign(){
        global $DB, $USER;
        
        if(!$this->contract_is_signed()){
            $DB->insert_record('format_treetopics_contract', array('courseid' => $this->course->id, 'userid' => $USER->id, 'timemodified' => time()));
        }
    }
    
    /*protected function contract_unsign(){
        global $DB, $USER;
        
        if($this->contract_is_signed()){
            $DB->delete_records('format_treetopics_contract', ['courseid' => $this->course->id, 'userid' => $USER->id]);
        }
    }*/
    
    protected function renderContract(){
        global $CFG;
        
        $signed = $this->contract_is_signed();
        
        $section = $this->sectionList[0];
        $html = $this->renderSectionContentItem($section);

        $html .= "<br/><br/>";
        $html .= html_writer::start_tag('div', array('class' => self::ID_APPEND . 'contract'));
            $html .= html_writer::tag('h2', "Contrat d'engagement", array('class' => self::ID_APPEND . 'contract-title'));
            $html .= html_writer::tag('pre', $section->ttcontract_editor, array('class' => self::ID_APPEND . 'contract-content'));

            $html .= "<div>";
            $html .= sprintf("<label><input id='%s' type='checkbox'/> J'ai lu, je comprends et j'accepte les termes du contrat</label>", self::ID_APPEND.'contract-read');
            $html .= "</div>";
        
        $html .= html_writer::start_tag('div');
            $html .= html_writer::tag('button', 'Signer le contrat', array('id' => self::ID_APPEND . 'contract-sign', 'type' => 'submit', 'disabled' => 'disabled', 
                    'href' => $CFG->wwwroot.'/course/view.php?id='.$this->course->id.'&ttc=1#section-1', 'class' => 'btn btn-primary'));
            $html .= html_writer::end_tag('div');        
        $html .= html_writer::end_tag('div');
        
        return $html;
    }
}

/**
 * Basic renderer for topics format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_treetopics_renderer extends format_section_renderer_base {
    protected $treeTopics = null;

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Since format_treetopics_renderer::section_edit_controls() only displays the 'Set current section' control when editing mode is on
        // we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');

        $this->treeTopics = new TreeTopics();
    }

    public function renderTreeTopics($course){
        global $PAGE;
        
        if($PAGE->user_is_editing()){
            $this->print_multiple_section_page($course, null, null, null, null);
        }
        else{
            echo $this->output->heading($this->page_title(), 2, 'accesshide');
            echo $this->course_activity_clipboard($course, 0);  // Copy activity clipboard..
            $this->treeTopics->render($this, $course);
        }
    }

     /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        //return html_writer::start_tag('div', array('class' => 'treetopics'));
        return "";
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        //return html_writer::end_tag('div');
        return "";
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    public function getCourseSectionCmList($course, $section){
        return $this->courserenderer->course_section_cm_list($course, $section, 0);
    }
  
     /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();       

        $filterValues = (isset($_COOKIE['ttModeEditionFilter']) ? explode(",", $_COOKIE['ttModeEditionFilter']) : array());
        $ttModeEditionFilter ='
            <div class="btn-group btn-group-toggle" data-toggle="buttons" id="ttModeEditionFilter">
                <label class="btn btn-outline-primary  %s">
                    <input type="checkbox" value="act" autocomplete="off" %s> Afficher les activités
                </label>
                <label class="btn btn-outline-primary  %s">
                    <input type="checkbox" value="sum" autocomplete="off" %s> Affiche le sommaire de la section
                </label>
            </div><br/><br/>
        ';
        
        echo sprintf($ttModeEditionFilter, (in_array("act",$filterValues) ? 'active' : ''), (in_array("act",$filterValues) ? 'checked' : ''),
                                    (in_array("sum",$filterValues) ? 'active' : ''), (in_array("sum",$filterValues) ? 'checked' : ''));

        $numsections = course_get_format($course)->get_last_section_number();
        
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                // 0-section is displayed a little different then the others
                if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                    echo $this->section_header($thissection, $course, false, 0);
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, 0, 0);
                    echo $this->section_footer();
                }
                continue;
            }
            if ($section > $numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
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

            if (!$PAGE->user_is_editing()) {
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

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
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
    }

       /**
     *  OVERRIDE
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $PAGE;       

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            }
            if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        $o.= html_writer::start_tag('li', array('id' => 'section-'.$section->section,
            'class' => 'section main clearfix'.$sectionstyle, 'role'=>'region',
            'aria-label'=> get_section_name($course, $section), "data-section-level" => $section->ttsectiondisplay,
            "data-section-id" => $section->id));

        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $o .= html_writer::tag('span', get_section_name($course, $section), array('class' => 'hidden sectionname'));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }
        $sectionname = html_writer::tag('span', $this->section_title($section, $course));
        $o.= $this->output->heading($sectionname, 3, 'sectionname' . $classes);

        $o .= $this->section_availability($section);

        $o .= html_writer::start_tag('div', array('class' => 'summary'));
        if ($section->uservisible || $section->visible) {
            // Show summary if section is available or has availability restriction information.
            // Do not show summary if section is hidden but we still display it because of course setting
            // "Hidden sections are shown in collapsed form".
            $o .= $this->format_summary_text($section);
        }
        $o .= html_writer::end_tag('div');

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
        $sectionName = $this->render(course_get_format($course)->inplace_editable_render_section_name($section));

        $radioSectionLevel = 
        '<label class="btn btn-outline-primary %s" >
            <input data-component="ttRadioSectionLevel" type="radio" value="%s" autocomplete="off" %s> %s
        </label>';

        $level = "";
        if($section->section > 0){
            $level = sprintf('<div class="btn-group btn-group-toggle btn-group-sm" style="margin-right:1rem;" data-toggle="buttons">%s%s%s</div>',
            sprintf($radioSectionLevel, ($section->ttsectiondisplay == 1 ? "active" : ""), "1", ($section->ttsectiondisplay == 1 ? "checked" : ""), get_string('displaytabslev1', 'format_treetopics')),
            sprintf($radioSectionLevel, ($section->ttsectiondisplay == 2 ? "active" : ""), "2", ($section->ttsectiondisplay == 2 ? "checked" : ""), get_string('displaytabslev2', 'format_treetopics')),
            sprintf($radioSectionLevel, ($section->ttsectiondisplay == 3 ? "active" : ""), "3", ($section->ttsectiondisplay == 3 ? "checked" : ""), get_string('displaytabslev3', 'format_treetopics')));    
        }
        
        $radioSectionContentDisplay = 
            '<label class="btn btn-outline-primary %s" >
                <input data-component="ttRadioSectionContentDisplay" type="radio" value="%s" autocomplete="off" %s> %s
            </label>';

        $contentDisplay = sprintf('<div class="btn-group btn-group-toggle btn-group-sm" data-toggle="buttons">%s%s</div>',
            sprintf($radioSectionContentDisplay, ($section->ttsectioncontentdisplay == -1 ? "active" : ""), "-1", ($section->ttsectioncontentdisplay == -1 ? "checked" : ""), get_string('displaytabs', 'format_treetopics')),
            sprintf($radioSectionContentDisplay, ($section->ttsectioncontentdisplay == -2 ? "active" : ""), "-2", ($section->ttsectioncontentdisplay == -2 ? "checked" : ""), get_string('displayimages', 'format_treetopics')));

        $html = sprintf("<span>%s%s%s</span>", $sectionName, $level, $contentDisplay);

        return $html;
    }
}
