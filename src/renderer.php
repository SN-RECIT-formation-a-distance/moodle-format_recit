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

/**
 * Basic renderer for topics format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_treetopics_renderer extends format_section_renderer_base {

	const ID_APPEND = 'tt-';
    const ID_TAB_APPEND = '-tab';

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
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('div', array('class' => 'treetopics'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('div');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section));
    }

    /**
     * Generate the section title to be displayed on the section page, without a link
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
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

            echo $this->end_section_list();

            echo $this->change_number_sections($course, 0);
        } else {
            echo $this->end_section_list();
        }
    }
	
	/**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     */
    public function print_treetopics_section_page($course) {
        global $PAGE;
        
        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $context = context_course::instance($course->id);
        
        if($PAGE->user_is_editing())
        {
            $this->print_multiple_section_page($course, null, null, null, null);
            return;
        }
        
        // Title
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();
        $numsections = course_get_format($course)->get_last_section_number();
        
        if(!$course->tthascontract)
            $contractsigned = true;
        else
            $contractsigned = $this->contract_is_signed($course);
        
        $showHome = !isset($_GET["course"]) || (isset($_GET["course"]) && $_GET["course"] == '0');
        if($contractsigned && isset($_COOKIE['section1']))
            $showHome = false;
        
        $showContract = $course->tthascontract && isset($_GET["contract"]) && $_GET["contract"] == '1';
        
        if($course->tthascontract)
        {
            if(isset($_GET["ttc"]))
            {
                if($_GET["ttc"] == '1')
                {
                    $this->contract_sign($course);
                }
                else if($_GET["ttc"] == 'DEBUG_UNSIGN') //TODO comment for prod
                {
                    $this->contract_unsign($course);
                }
            }
        }
        
        if($showContract)
        {
            $this->print_contract($course);
            return;
        }
        
        if($showHome)
        {
            $this->print_home($course, $modinfo);
            return;
        }
        
        if($course->tthascontract)
        {
            if(!$this->contract_is_signed($course))
            {
                $this->print_contract($course);
                return;
            }
        }
        
        // Print all sections
        switch($course->ttsectiondisplay)
        {
            case TT_DISPLAY_TABS:
                $this->print_tabs_block($course, $modinfo, $numsections);
                $this->print_tabs_sections($course, $modinfo, $numsections, $sectioninfo);
                break;
            case TT_DISPLAY_IMAGES:
                echo $this->print_images_block(null, $course, $modinfo, range(1, $numsections + 1));
                $this->print_tabs_sections($course, $modinfo, $numsections, true);
                break;
            default:
                break;
        }
    }
    
    protected function contract_is_signed($course)
    {
        global $DB;
        global $USER;
        return $DB->record_exists('format_treetopics_contract', ['courseid' => $course->id, 'userid' => $USER->id]);
    }
    
    protected function contract_sign($course)
    {
        global $DB;
        global $USER;
        
        if(!$this->contract_is_signed($course))
        {
            $DB->insert_record('format_treetopics_contract', array(
                'courseid' => $course->id,
                'userid' => $USER->id
            ));
        }
    }
    
    protected function contract_unsign($course)
    {
        global $DB;
        global $USER;
        
        if($this->contract_is_signed($course))
        {
            $DB->delete_records('format_treetopics_contract', ['courseid' => $course->id, 'userid' => $USER->id]);
        }
    }
    
    protected function print_contract($course){
        global $CFG;
        
        $signed = $this->contract_is_signed($course);
        
        $o = '';
        $o .= html_writer::start_tag('div', array('class' => self::ID_APPEND . 'contract'));
        $o .= html_writer::tag('h2', "Contrat d'engagement", array('class' => self::ID_APPEND . 'contract-title'));
        $o .= html_writer::tag('pre', $course->ttcontract, array('class' => self::ID_APPEND . 'contract-content'));

        $o .= html_writer::start_tag('div');
        if($signed)
            $o .= html_writer::tag('label', "Contrat Signé", array('id' => self::ID_APPEND . 'contract-read', 'type' => 'checkbox', 'disabled' => 'disabled'));
        else
            $o .= html_writer::tag('input', "J'ai lu, je comprends et j'accepte les termes du contrat", array('id' => self::ID_APPEND . 'contract-read', 'type' => 'checkbox'));
        $o .= html_writer::end_tag('div');
        
        $o .= html_writer::start_tag('div');
        if($signed)
            $o .= html_writer::tag('button', 'Retour', array('id' => self::ID_APPEND . 'contract-sign', 'type' => 'submit', 'href' => $CFG->wwwroot.'/course/view.php?id='.$course->id));
        else
            $o .= html_writer::tag('button', 'Signer le contrat', array('id' => self::ID_APPEND . 'contract-sign', 'type' => 'submit', 'disabled' => 'disabled', 'href' => $CFG->wwwroot.'/course/view.php?id='.$course->id.'&ttc=1'));
        $o .= html_writer::end_tag('div');
        
        $o .= html_writer::end_tag('div');
        
        echo $o;
    }
    
       protected function print_home($course, $modinfo){
        global $CFG;
       /* $o = '';
        $o .= html_writer::start_tag('div', array('class' => self::ID_APPEND . 'home'));
        $o .= html_writer::start_tag('div', array('class' => self::ID_APPEND . 'home-header'));
        $o .= '<iframe class="tt-home-video" width="560" height="315" src="https://www.youtube.com/embed/IilAcCLJaMo" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        //$o .= html_writer::tag('video', html_writer::start_tag('source', array('src' => 'src', 'type' => 'video/mp4')), array('class' => self::ID_APPEND . 'home-video', 'width' => 320, 'height' => 240, 'controls'));
        $o .= html_writer::tag('img', '', array('class' => self::ID_APPEND . 'home-image', 'src' => $CFG->wwwroot.'/course/format/treetopics/sectionimages/histoire.png'));
        $o .= html_writer::end_tag('div');
        $o .= html_writer::start_tag('div', array('class' => self::ID_APPEND . 'home-menu'));
        $o .= html_writer::tag('button', html_writer::tag('a', 'Échéancier', array('href' => '')), array('class' => self::ID_APPEND . 'home-menu-button'));
        $o .= html_writer::tag('button', html_writer::tag('a', 'Exigences et travaux', array('href' => '')), array('class' => self::ID_APPEND . 'home-menu-button'));
        $o .= html_writer::tag('button', html_writer::tag('a', 'Contacte ton enseignant', array('href' => '')), array('class' => self::ID_APPEND . 'home-menu-button'));
        if($course->tthascontract)
            $o .= html_writer::tag('button', html_writer::tag('a', "Contrat d'engagement", array('href' => $CFG->wwwroot.'/course/view.php?id='.$course->id.'&contract=1')), array('class' => self::ID_APPEND . 'home-menu-button'));
        $o .= html_writer::end_tag('div');
        $o .= html_writer::start_tag('div', array('class' => self::ID_APPEND . 'home-menu2'));
        $o .= html_writer::tag('button', html_writer::tag('a', 'Rencontre tes guides', array('href' => '')), array('class' => self::ID_APPEND . 'home-menu-button'));
        $o .= html_writer::tag('button', html_writer::tag('a', 'Débuter le cours', array('href' => $CFG->wwwroot.'/course/view.php?id='.$course->id.'&course=1')), array('class' => self::ID_APPEND . 'home-menu-button'));
        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('div');*/
        
        $o = $this->section_tabs_header($modinfo->get_section_info_all()[0], $course, false, false, false);
                
		$o .= '<section class="C1004" data-version="0.1.1" data-recit-block="1" style="">    <div class="C1004a parallax-recit c_espacement-nul bg_secondary" style="background-image: url()">        <div class="container">            <div class="C1004a row ">                <div class="C1004b inserer col-md-3 ">				<div class=" basic-pale-row" style="" data-version="0.1.1" data-recit-block="2"></div>                 </div>				 <div class="C1004c inserer col-md-3 ">				<div class=" basic-pale-row " style="" data-version="0.1.1" data-recit-block="2"></div>                 </div>				 <div class="C1004d inserer col-md-3 ">				<div class=" basic-pale-row " style="" data-version="0.1.1" data-recit-block="2"></div>                 </div>				 <div class="C1004e inserer col-md-3 ">				<div class=" basic-pale-row text_box1" style="" data-version="0.1.1" data-recit-block="2"><div class="I803  " data-version="0.1.1" data-recit-block="2" style="">				    <p>					';
		$o .= html_writer::tag('button', html_writer::tag('a', 'Commencer le cours', array('href' => $CFG->wwwroot.'/course/view.php?id='.$course->id.'&course=1')), array('class' =>  'recit-btn-style_btn_1'));
		$o .= '					</p>					</div>                </div>                                  </div>                </div>        </div>    </div></section>' ;
		echo $o;
		   
    }
    
    protected function print_tabs_item($course, $sectioninfoall, $numsections, $previous, &$o, &$i)
    {
        if($i > $numsections)
            return 0;
        
        $thissection = $sectioninfoall[$i];
        $showsection = $thissection->uservisible || ($thissection->visible && !$thissection->available) || (!$thissection->visible && !$course->hiddensections);
            
        if (!$showsection)
            return 0;
        
        $thistype = $thissection->ttsectiondisplay;
        
        if($previous > $thistype)
            return 0;
        
        $childoutput = '';
        if($thissection->ttsectioncontentdisplay == TT_DISPLAY_IMAGES)
        {
            while(++$i <= $numsections && $sectioninfoall[$i]->ttsectiondisplay > $thistype);
        }
        else
            $i++;
        
        $childtype = $this->print_tabs_item($course, $sectioninfoall, $numsections, $thistype, $childoutput, $i);
        
        $sectionname = get_section_name($course, $thissection);
        $sectionid = $this->get_section_id($course, $thissection);
            
        if($childtype > $thistype)
        {
            if($thistype == 1)
            {
                $dropdownid = $sectionid.'DropdownMenuLink';
                $o .= html_writer::start_tag('li', array('class' => 'nav-item dropdown'));
                $o .= html_writer::tag('a', $sectionname, array('class' => 'nav-link dropdown-toggle', 'href' => '#', 'id' => $dropdownid, 'data-toggle' => 'dropdown', 'aria-haspopup' => 'true', 'aria-expanded' => 'false'));
                $o .= html_writer::start_tag('ul', array('class' => 'dropdown-menu', 'aria-labelledby' => $dropdownid));
                $o .= $childoutput;
                $o .= html_writer::end_tag('ul');
                $o .= html_writer::end_tag('li');
            }
            else
            {
                $o .= html_writer::start_tag('li', array('class' => 'dropdown-submenu'));
                $o .= html_writer::tag('a', $sectionname, array('class' => 'dropdown-item dropdown-toggle', 'href' => '#'));
                $o .= html_writer::start_tag('ul', array('class' => 'dropdown-menu'));
                $o .= $childoutput;
                $o .= html_writer::end_tag('ul');
                $o .= html_writer::end_tag('li');
            }
            
            $childoutput = '';
            $childtype = $this->print_tabs_item($course, $sectioninfoall, $numsections, $thistype, $childoutput, $i);
            $o .= $childoutput;
        }
        else
        {
            if($thistype == 1)
            {
                $o .= html_writer::start_tag('li', array('class' => 'nav-item'));
                $o .= html_writer::tag('a', $sectionname, array('class' => 'nav-link', 'href' => '#', 'data-section' => $sectionid));
                $o .= html_writer::end_tag('li');
            }
            else
            {
                $o .= html_writer::tag('li', html_writer::tag('a', $sectionname, array('class' => 'dropdown-item', 'href' => '#', 'data-section' => $sectionid)));
            }
            
            $o .= $childoutput;
        }
        
        return $thistype;
    }
    
    /**
     * Prints the tabs block
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $modinfo the course info
     * @param int $numsections The number of sections
     */
    protected function print_tabs_block($course, $modinfo, $numsections){
        
        $o = html_writer::start_tag('nav', array('class' => 'navbar navbar-expand-lg navbar-light bg-light', 'id' => 'tt-recit-nav'));
        $o .= html_writer::start_tag('button', array('class' => 'navbar-toggler', 'type' => 'button', 'data-toggle' => 'collapse', 'data-target' => '#navbarTogglerCourse', 'aria-controls' => 'navbarTogglerCourse', 'aria-expanded' => 'false', 'aria-label' => 'Toggle navigation'));
        $o .= html_writer::tag('span', '', array('class' => 'navbar-toggler-icon'));
        $o .= html_writer::end_tag('button');
        $o .= html_writer::start_tag('div', array('class' => 'collapse navbar-collapse', 'id' => 'navbarTogglerCourse'));
        $o .= html_writer::start_tag('ul', array('class' => 'navbar-nav mr-auto mt-2 mt-lg-0'));
        
        $sectioninfoall = $modinfo->get_section_info_all();
        $tabsoutput = '';
        $i = 1;
        $this->print_tabs_item($course, $sectioninfoall, $numsections, 0, $tabsoutput, $i);
        $o .= $tabsoutput;
        
        $o .= html_writer::end_tag('ul');
        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('nav');
        echo $o;
    }
    
    /**
     * Prints the images block
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $modinfo the course info
     * @param int $numsections The number of sections to print
     * @param int $start The first section to print
     */
    protected function print_images_block($parentsection, $course, $modinfo, $sectionarray, $startvisible = true){
        $o = '';

        $nbcolumns = $course->ttimagegridcolumns;
        if($nbcolumns < 1)
            $nbcolumns = 1;
        
        $hauto = '';
        for($i = 0; $i < $nbcolumns; $i++)
            $hauto .= 'auto ';
        
        $o .= html_writer::tag('button', get_string('backtotableofcontent', 'format_treetopics'), array('class' => self::ID_APPEND . 'section-image-link', 'id' => self::ID_APPEND . 'section-image-back', 'style' => 'display: none;'));
        
        $firstdivoptions = array('id' => self::ID_APPEND . "imagebuttons", 'class' => self::ID_APPEND . "imagebuttons auto$nbcolumns", 'style' => 'display: ' . ($startvisible ? 'grid' : 'none') . '; grid-template-columnsx: ' . $hauto . ';');
        
        if($parentsection != null)
        {
            $firstdivoptions['id'] = $this->get_section_id($course, $parentsection).'-imagebuttons';
            $firstdivoptions['data'] = $this->get_section_id($course, $parentsection);
        }
        
        $o .= html_writer::start_tag('div', $firstdivoptions);
        
		foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            
			// Hide general section and orphaned activities
            if (!in_array($section, $sectionarray))
            {
				continue;
			}
			// Show the section if the user is permitted to access it
			$showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available) ||
                    (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }
            
            $format_options = format_base::instance($course)->get_format_options($thissection);
            //var_dump($format_options);
            
            $sectionname = get_section_name($course, $thissection);
            $sectionheader = $this->format_summary_text($thissection);//$this->section_tabs_header($thissection, $course, false, false, false);
            $summaryStart = strpos($sectionheader, '&lt;summary&gt;');
            if($summaryStart !== false)
            {
                $summaryStart += 15;
                $summaryEnd = strpos($sectionheader, '&lt;/summary&gt;', $summaryStart);
                if($summaryEnd !== false)
                {
                    $summaryLength = $summaryEnd - $summaryStart;
                    $sectionheader = substr($sectionheader, $summaryStart, $summaryLength);
                    
                    $startPara = strpos($sectionheader, '<p>');
                    if($startPara !== false)
                    {
                        $endPara = strpos($sectionheader, '</p>');
                        if($endPara < $startPara)
                        {
                            $sectionheader = substr($sectionheader, $endPara + 4);
                        }
                    }
                    
                    $startPara = strrpos($sectionheader, '<p>');
                    if($startPara !== false)
                    {
                        $endPara = strrpos($sectionheader, '</p>', $startPara);
                        if($endPara === false)
                        {
                            $sectionheader .= '</p>';
                        }
                    }
                }
            }
            
			$sectionclass = self::ID_APPEND . 'section-image-link '. self::ID_APPEND . 'grid-element' . ($section == 1 ? ' ' . self::ID_APPEND . 'section-image-link-selected' : '');
            
            $url = moodle_url::make_pluginfile_url($format_options['ttsectionimage-context'], $format_options['ttsectionimage-component'], $format_options['ttsectionimage-filearea'], $format_options['ttsectionimage-itemid'], $format_options['ttsectionimage-filepath'], $format_options['ttsectionimage-filename']);
            if($url == '')
                $url = 'http://res.freestockphotos.biz/pictures/15/15789-illustration-of-a-blank-glossy-rectangular-button-pv.png';
            
            $o .= html_writer::start_tag('div', array('class' => $sectionclass, 'data' => $this->get_section_id($course, $thissection),'style' => 'position:relative;'));
            $o .= html_writer::tag('img', '', array('class' => self::ID_APPEND . 'section-image', 'src' => $url->out(false), 'alt' => $sectionname));
            if($format_options['ttsectiontitle'])
                $o .= html_writer::tag('div', $sectionname, array('class' => self::ID_APPEND . 'section-title'));
            $o .= $sectionheader;
            $o .= html_writer::end_tag('div');
		}
		$o .= html_writer::end_tag('div');
        
		return $o;
    }
    
    /**
     * Prints the tabs mode sections
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $modinfo the course info
     * @param int $numsections The number of sections
     */
    protected function print_tabs_sections($course, $modinfo, $numsections, $hideall=false)
    {
        $sectioninfoall = $modinfo->get_section_info_all();
        for($i = 1; $i <= $numsections; $i++)
        {
            $thissection = $sectioninfoall[$i];
            $showsection = $thissection->uservisible || ($thissection->visible && !$thissection->available) || (!$thissection->visible && !$course->hiddensections);
            
            if ($i > $numsections || !$showsection)
                continue;
            
            $showtitle = true;//$thissection->ttsectiondisplay == TT_DISPLAY_IMAGES || $course->ttsectiondisplay == TT_DISPLAY_IMAGES;
            echo $this->section_tabs_header($thissection, $course, false, $hideall, $showtitle);
            
            if($thissection->ttsectioncontentdisplay == TT_DISPLAY_IMAGES)
            {
                $start = $i + 1;
                for($j = $start; $j <= $numsections; $j++)
                {
                    $childsection = $sectioninfoall[$j];
                    if($childsection->ttsectiondisplay <= $thissection->ttsectiondisplay)
                    {
                        break;
                    }
                }
                if($j < $numsections)
                    $j--;
                
                echo $this->print_images_block($thissection, $course, $modinfo, range($start, $j), false);
            }
            
            if($i > $numsections)
                break;

            // Display course activities
            if ($thissection->uservisible && $thissection->ttsectionshowactivities) 
            {
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->courserenderer->course_section_add_cm_control($course, $i, 0);
            }
            echo $this->section_tabs_footer();
        }
    }
    
    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @return string HTML to output.
     */
    protected function section_tabs_header($section, $course, $onsectionpage, $hideall, $showtitle) {
        global $PAGE;
        
        // Don't display 0 section
        if($section->section == 0)
        {
            $hideall = false;
            $showtitle = false;
        }

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        if (!$section->visible) {
            $sectionstyle = ' hidden';
        }
        if (course_get_format($course)->is_section_current($section)) {
            $sectionstyle = ' current';
        }
        $o.= html_writer::start_tag('div', array('id' => $this->get_section_id($course, $section),
            'class' => 'section main clearfix tt-section'.$sectionstyle, 'role'=>'region',
            'aria-label'=> get_section_name($course, $section),
            'style'=> (($section->section == 1 || $section->section == 0) && !$hideall) ? 'display:block' : 'display:none'));
            
        // Create a span that contains the section title to be used to create the keyboard section move menu.
        if($showtitle)
            $o .= html_writer::tag('h2', get_section_name($course, $section), array('class' => 'sectionname'));
        
        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));

        $o .= $this->section_availability($section);

        $o .= html_writer::start_tag('div', array('class' => 'summary'));
        if ($section->uservisible || $section->visible) {
            //Show summary if section is available or has availability restriction information.
            //Do not show summary if section is hidden but we still display it because of course setting
            //"Hidden sections are shown in collapsed form".
            $o .= str_replace('<p></p>', '', str_replace('&lt;summary&gt;', '', str_replace('&lt;/summary&gt;', '', $this->format_summary_text($section))));
        }
        $o .= html_writer::end_tag('div');

        return $o;
    }
    
    /**
     * Generate the display of the footer part of a section
     *
     * @return string HTML to output.
     */
    protected function section_tabs_footer() {
        $o = html_writer::end_tag('div');
        $o.= html_writer::end_tag('div');

        return $o;
    }
	
	/**
     * Generate a section's id
     *
	 * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @return the section's string id
     */
	protected function get_section_id($course, $section){
        $specialchars = array("'", '"', '\\', '/', '@', '$', '%', '!', '#', '?', '&', '*', '(', ')', '+', ' ', '-', '=', ';', ':', '^', '`', '<', '>', '«', '»');
		return self::ID_APPEND . str_replace($specialchars, '', get_section_name($course, $section));
	}

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();
        if ($section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $markedthistopic = get_string('markedthistopic');
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                                               'name' => $highlightoff,
                                               'pixattr' => array('class' => '', 'alt' => $markedthistopic),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markedthistopic,
                                                   'data-action' => 'removemarker'));
            } else {
                $url->param('marker', $section->section);
                $markthistopic = get_string('markthistopic');
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                                               'name' => $highlight,
                                               'pixattr' => array('class' => '', 'alt' => $markthistopic),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markthistopic,
                                                   'data-action' => 'setmarker'));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }
}
