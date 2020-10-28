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
 * Class format_treetopics_renderer extends of format_section_renderer_base.
 *
 * @package    format_treetopics
 * @copyright  RECITFAD
 * @author     RECITFAD
 * @license    {@link http://www.gnu.org/licenses/gpl-3.0.html} GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');

//js_reset_all_caches();
/**
 * TreeTopics specifics functions.
 *
 * @author RECITFAD
 */
class TreeTopics
{
    /** @var string */
    const ID_APPEND = 'tt-';

    /** @var format_treetopics_renderer */
    protected $moodlerenderer = null;
    /** @var stdClass */
    protected $course = null;
    /** @var string */
    protected $output = null;
    /** @var stdClass */
    protected $modinfo = null;
    /** @var stdClass */
    protected $courseformat = null;
    /** @var array */
    protected $sectionslist = array();
    /** @var array */
    protected $sectionstree = array();
    /** @var boolean */
    protected $sectionsingleloading = true;
    /**
     * Construc for TreeTopics
     */
    public function __construct() {
        global $COURSE, $OUTPUT;
        //$context = context_course::instance($COURSE->id);
        $this->output = $OUTPUT;
    }

     /**
     * Function load of TreeTopics.
     *
     * @param format_treetopics_renderer $moodlerenderer
     * @param stdClass $course
     */
    public function load($moodlerenderer, $course) {
        $this->moodlerenderer = $moodlerenderer;
        $this->course = $course;

        $this->modinfo = get_fast_modinfo($course);
        $this->courseformat = course_get_format($course);
        $this->sectionslist = $this->modinfo->get_section_info_all();

        $this->create_section_tree();
    }

    /**
     * Function render of TreeTopics.
     *
     * @return string
     */
    public function render() {
        $this->signing_contract();

        if ($this->show_contract()) {
            $html = "<div class='treetopics'>%s</div>";
            $html = sprintf($html, $this->render_contract());
        } else {
            $orientation = "";
            
            switch($this->course->tttabsmodel){
                case 5:
                    $orientation = "horizontal"; break;
                case 2:
                    $orientation = "vertical-right"; break;
                case 3:
                    $orientation = "vertical-left"; break;
            }

            $html = "<div class='treetopics $orientation'>%s</div>";
            
            $html = sprintf($html, $this->render_sections());
            $html .= $this->render_pagination();
        }

        echo $html;
    }

    /**
     * Function create section tree of TreeTopics.
     */
    protected function create_section_tree() {
        $this->sectionstree = array();

        foreach ($this->sectionslist as $section) {
            switch ($section->ttsectiondisplay) {
                case 1:
                    $item1 = new stdClass();
                    $item1->section = $section;
                    $item1->child = array();
                    $this->sectionstree[] = $item1;
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

    /**
     * Function render sections of TreeTopics.
     * @return string
     */
    protected function render_sections() {
        // La section 0 controle le mode d'affichage.
        $mode = $this->sectionslist[0]->ttsectioncontentdisplay;
        $model = $this->course->tttabsmodel;
        
        $menu = "";
        if ($mode == TT_DISPLAY_TABS) {
            switch ($model) {
                case 1: 
                    $menu = $this->render_sections_menu_m1();
                    break;
                case 2: 
                case 3: 
                case 5:
                    $menu = $this->render_sections_menu_m5();
                    break;
                default: 
                    $menu = $this->render_sections_menu_m5();
            }
        }
        else{
            return "La section 0 doit être \"Affichage sous forme d'onglets\".";
        }
        
        $content = "<div id='sectioncontent_placeholder'></div>";

        $options = array(1, 3, 5);
        if(in_array($this->course->tttabsmodel, $options)){
            return $menu.$content;
        }
        else{
            return $content.$menu;
        }
    }

    /**
     * Function render sections menu m1 of TreeTopics.
     * @return string
     */
    protected function render_sections_menu_m1() {  
        $maxNbChars = 25;

        $menuitemtemplate =
                "<li class='menu-item' data-submenu='%s'>
                    <div class='menu-item-title'>
                        <div class='arrow'></div>
                        <a class='menu-item-desc' href='#' data-section='%s' onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)' title='%s'>%s</a>
                        <button class='btn btn-sm btn-outline-light btn-expand-sub-menu' onclick='M.recit.course.format.TreeTopics.instance.ctrlOpeningSubMenuResponsive(event, \"%s\")'><i class='fa fa-plus'></i></button>
                    </div>
                    %s
                </li>";

        $menuseparator = "<li></li>";

        $html = "
                <nav class='menuM1' id='tt-recit-nav' data-status='closed'>                    
                    <div class='background-menu-mobile'></div>
                    <ul class='menu-level1 tt-menu-color1'id='level1'>
                        <li class='btn-menu-responsive'>
                            <button class='btn btn-outline-light btn-sm' data-btn='open'
                                onclick='M.recit.course.format.TreeTopics.instance.ctrlOpeningMenuResponsive(\"open\")'><i class='fa fa-bars'></i>
                            </button>
                            <div class='section-title'></div>
                            <div class='sous-section-title'></div>
                            <button class='btn btn-outline-light btn-sm' data-btn='close'
                                onclick='M.recit.course.format.TreeTopics.instance.ctrlOpeningMenuResponsive(\"closed\")'><i class='fa fa-times'></i>
                            </button>
                        </li>
                        %s
                    </ul>
                    %s
                </nav>";

        $tmp1 = "";
        $tmp2 = "";

        $tmp1 .= sprintf($menuitemtemplate, "0", "map", "Menu", "<i class='fa fa-map'></i>", "", "");
        $tmp1 .= $menuseparator;
        foreach ($this->sectionstree as $item1) {
            $tmp2 = "";
            if ($item1->section->ttsectioncontentdisplay == TT_DISPLAY_IMAGES) {
                continue;
            }
            
            if( !$item1->section->visible ){
                continue; 
            }

            $tmp3 = "";
            foreach ($item1->child as $item2) {
                if ($item2->section->ttsectioncontentdisplay == TT_DISPLAY_IMAGES) {
                    continue;
                }

                if( !$item2->section->visible ){
                    continue; 
                }

                $sectionname = $this->get_section_name($item2->section);
                $tmp3 .= sprintf($menuitemtemplate, "0", $this->get_section_id($item2->section), $sectionname, mb_strimwidth($sectionname, 0, $maxNbChars, "..."), "", "");
                $tmp3 .= $menuseparator;
            }
            if (strlen($tmp3) > 0) {
                $sectionid = $this->get_section_id($item1->section);
                $tmp2 .= sprintf("<ul class='menu-level2 tt-menu-color2' id='level2' data-parent-section='%s' data-status='closed'>%s</ul>",
                        $this->get_section_id($item1->section), $tmp3);

                $sectionname = $this->get_section_name($item1->section);
                $tmp1 .= sprintf($menuitemtemplate, "1", $sectionid, $sectionname, mb_strimwidth($sectionname, 0, $maxNbChars, "..."),  $sectionid, $tmp2);
                $tmp1 .= $menuseparator;
                /*var_dump($tmp1);
                exit();*/
            }else{
                $sectionname = $this->get_section_name($item1->section);

                $tmp1 .= sprintf($menuitemtemplate, "0", $this->get_section_id($item1->section),  $sectionname, mb_strimwidth($sectionname, 0, $maxNbChars, "..."), "", "");
                $tmp1 .= $menuseparator;
            }

            
        }
        return sprintf($html, $tmp1, "");
    }

    /**
     * Function render sections menu m2 of TreeTopics. Mega menu !!!
     * @return string
     */
    /*protected function render_sections_menu_m3() { 
        //Template for the responsive menu icon
        $menuicontemplate =
                "<li class='menu-item'>
                    <a class='menu-item-desc' href='#' data-section='%s'
                        onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>%s</a>
                        <h5 id='section-title'></h5>
                        <div id='sous-title'><h5 id='sousSection-title'></h5></div>
                </li>";  
        $menuitemleveltemplate =
                "<li class='menu-item'>
                    <div class='arrow'></div>
                    <a class='menu-item-desc' href='#' data-section='%s'
                        onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>%s<i class='fas fa-plus' id='sectionIcon'></i></a>
                        %s
                </li>";
        $menuactivitiestemplate =
                "<li class='menu-item'>
                    <a class='menu-item-desc' href='#' data-section='%s'
                        onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>%s</a>
                        <h5 id='section-title'></h5>
                        <div id='sous-title'><h5 id='sousSection-title'></h5></div>
                        <ul class='menu-level3' id='level3'>%s</ul>
                </li>";  
        $menuitemtemplate =
                "<li class='menu-item'>
                    <div class='arrow'></div>
                    <a class='menu-item-desc' href='#' data-section='%s'
                        onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>%s<i class='fas fa-plus' id='sectionIcon'></i></a>
                </li>";

        $menuseparator = "<li></li>";

        $html = "
                <div id='dark-background-menu'></div>
                <nav class='menuM3' id='tt-recit-nav'>
                    <ul class='menu-level1 tt-menu-color1'id='level1'>%s</ul>
                    %s
                </nav>";

        $tmp1 = "";
        $tmp2 = "";
        //Ajout des l'icons du menu responsive
        $tmp1 .= sprintf($menuicontemplate, "icon", "<i class='fa fa-bars' id='faIcon'></i>");

        $tmp1 .= sprintf($menuitemtemplate, "map", "<i class='fa fa-map'></i> Mon cours");
        $tmp1 .= $menuseparator;

        $context = context_course::instance($this->course->id);

        foreach ($this->sectionstree as $item1) {
            $tmp2 = "";
            if ($item1->section->ttsectioncontentdisplay == TT_DISPLAY_IMAGES) {
                continue;
            }
            
            if( !$item1->section->visible ){
                continue; 
            }

            $tmp3 = "";
            foreach ($item1->child as $item2) {
                
                if ($item2->section->ttsectioncontentdisplay == TT_DISPLAY_IMAGES) {
                    continue;
                }

                if( !$item2->section->visible ){
                    continue; 
                }

                $tmp4 = "";
                $modinfo = get_fast_modinfo($this->course->id);
                foreach ($modinfo->cms as $cm) {
                    // Use normal access control and visibility, but exclude labels and hidden activities.
                    if (!$cm->has_view()) {
                        continue;
                    }

                    if($cm->section == $item2->section->id){
                        $tmp4 .= sprintf($this->get_activity_name($cm));
                        $tmp4 = format_text($tmp4, array('filter' => true));
                    }
                }
                if (strlen($tmp4) > 0) {
                    $tmp3 .= sprintf($menuactivitiestemplate, $this->get_section_id($item2->section),
                    $this->get_section_name($item2->section), $tmp4);
                }else{
                    $tmp3 .= sprintf($menuitemtemplate, $this->get_section_id($item2->section),
                            $this->get_section_name($item2->section));
                    $tmp3 .= $menuseparator;
                }
            }
            
            if (strlen($tmp3) > 0) {
                $tmp2 .= sprintf("<ul class='menu-level2 tt-menu-color2' id='level2' data-parent-section='%s'>%s</ul>",
                        $this->get_section_id($item1->section), $tmp3);
                $tmp1 .= sprintf($menuitemleveltemplate, $this->get_section_id($item1->section), $this->get_section_name($item1->section), $tmp2);
                $tmp1 .= $menuseparator;
            }else{
                $tmp1 .= sprintf($menuitemtemplate, $this->get_section_id($item1->section), $this->get_section_name($item1->section));
                $tmp1 .= $menuseparator;
            }
        }
        
        return sprintf($html, $tmp1, "");
    }*/

    /**
     * Function render sections menu m5 of TreeTopics.
     * @return string
     */
    protected function render_sections_menu_m5() {
        $navbar = "<nav class='navbar navbar-dark %s theme-bg-color menuM5' id='tt-recit-nav'>%s</nav>";

        $collapse = "<button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#menuM5-collapse'
                            aria-controls='menuM5-collapse' aria-expanded='false' aria-label='Toggle navigation'>
                        <span class='navbar-toggler-icon'></span>
                    </button>
                    <div class='collapse navbar-collapse' id='menuM5-collapse'>
                        %s
                    </div>";

        $menuitems = "<ul class='navbar-nav mr-auto mt-2 mt-lg-0'>
                        <li class='nav-item menu-item'>
                            <a class='nav-link' href='#' data-section='map'
                                onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>
                                <i class='fa fa-map'></i></a>
                        </li>
                        %s
                    </ul>";

        $hmenu = sprintf($navbar, "navbar-expand-lg",  sprintf($collapse, $menuitems));
        $vmenu = sprintf($navbar, "navbar-expand-lg", sprintf($collapse, $menuitems));
        
        $options = array(1, 5);
        if(in_array($this->course->tttabsmodel, $options)){
            $html = $hmenu;
        }
        else{
            $html = $vmenu;
        }

        $tmp1 = "";
        $tmp2 = "";
        $tmp3 = "";
        foreach ($this->sectionstree as $item1) {
            
            if( !$item1->section->visible ){
                continue; 
            }

            foreach ($item1->child as $item2) {
                if( !$item2->section->visible ){
                    continue; 
                }

                foreach ($item2->child as $item3) {
                    if( !$item3->section->visible ){
                        continue; 
                    }

                    $tmp3 .= $this->render_sections_menu_m5_items($item3->section);
                }
                $tmp2 .= $this->render_sections_menu_m5_items($item2->section, $tmp3);
                $tmp3 = "";
            }
            $tmp1 .= $this->render_sections_menu_m5_items($item1->section, $tmp2);
            $tmp2 = "";
        }

        return sprintf($html, $tmp1);
    }

    /**
     * Function render sections menu m5 items of TreeTopics.
     *
     * @param string $section
     * @param string $subsection
     * @return string
     */
    protected function render_sections_menu_m5_items($section, $subsection = "") {
        $html = "";

        $sectionid = $this->get_section_id($section);
        $sectionname = $this->get_section_name($section);

        // collapse action is taken on JS by goToSection function
        $templateMenuItem = "
            <li class='%s'>
                <a class='%s' href='#' data-section='$sectionid' data-toggle='%s' data-target='%s' %s 
                    onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>
                    $sectionname
                </a>
                %s
            </li>";

        if ($section->ttsectioncontentdisplay == TT_DISPLAY_TABS) {
            if ($section->ttsectiondisplay == 1) {
                if (empty($subsection)) {
                    $html = sprintf($templateMenuItem, "nav-item menu-item", "nav-link", "", "#menuM5-collapse", "", "");
                } else {
                    $dropdownid = $sectionid.'DropdownMenuLink';
                    $html = sprintf($templateMenuItem, "nav-item dropdown menu-item", "nav-link dropdown-toggle", "dropdown", "$sectionid.dropdownmenu", "aria-haspopup='true' aria-expanded='false' id='$dropdownid'",
                                "<ul class='dropdown-menu theme-bg-color' aria-labelledby='$dropdownid' id='$sectionid.dropdownmenu'>$subsection</ul>");
                }
            } else if ($section->ttsectiondisplay == 2) {
                if (empty($subsection)) {
                    $html = sprintf($templateMenuItem, "", "dropdown-item", "", "#menuM5-collapse", "", "");
                } else {
                    $dropdownid = $sectionid.'DropdownMenuLink';
                    $html = sprintf($templateMenuItem, "dropdown-submenu", "dropdown-item dropdown-toggle", "", "#menuM5-collapse", "id='$dropdownid'", "<ul class='dropdown-menu theme-bg-color'>$subsection</ul>");
                }
            } else if ($section->ttsectiondisplay == 3) {
                $html = sprintf($templateMenuItem, "", "dropdown-item", "", "#menuM5-collapse", "", "");
            }
        }

        return $html;
    }

    /**
     * Function render sections content of TreeTopics.
     *
     * @return string
     */
    public function render_section_content($sectionid) {
        
        if($sectionid === 'map'){
            return $this->get_map_sections();
        }

        $found = null; 
        $result = "";
        foreach ($this->sectionstree as $item1) {
            if( !$item1->section->visible ){
                continue; 
            }

            $id = $this->get_section_id($item1->section);
            if ($id == $sectionid) { 
                $found = $item1;
                break;
            }

            foreach ($item1->child as $item2) {
                if( !$item2->section->visible ){
                    continue; 
                }

                $id = $this->get_section_id($item2->section);
                if ($id == $sectionid) { 
                    $found = $item2;
                    break;
                }
            }
        }

        if(empty($found)){
            return null;
        }

        $tmp = "";
        foreach ($found->child as $item) { 
            if( !$item->section->visible ){
                continue; 
            }

            if ($item->section->ttsectioncontentdisplay == TT_DISPLAY_IMAGES) {
                $tmp .= $this->render_section_content_item($item->section);
            }
        }

        $result = $this->render_section_content_item($found->section, $tmp);

        return $result;
    }

    /**
     * Function render section content item of TreeTopics.
     *
     * @param string $section
     * @param string $subcontent
     * @return string
     */
    protected function render_section_content_item($section, $subcontent = "") {
        // The section 0 is required to display as tab in order to display all the children.
        if (($section->ttsectioncontentdisplay == TT_DISPLAY_IMAGES) && ($section->section > 0)) {
            $html = $this->get_section_display_image($section, $subcontent);
        } else {
            $html = $this->get_section_display_tab($section, $subcontent);
        }

        return $html;
    }

    /**
     * Function to get section image of TreeTopics.
     *
     * @param string $section
     * @param string $subcontent
     * @return string
     */
    protected function get_section_display_image($section, $subcontent) {
        $sectionid = $this->get_section_id($section);
        $sectionname = $this->get_section_name($section);

        $formatoptions = format_base::instance($this->course)->get_format_options($section);
        $imgsource = (isset($formatoptions['ttsectionimageurl'])
                ? format_treetopics::rewrite_file_url($formatoptions['ttsectionimageurl']) : "");

        $sectiontitle = ($formatoptions['ttsectiontitle'] == 1 ? "<div class='tt-section-title'>$sectionname</div>" : "");
        $sectionsummary = format_text($section->ttsectionimagesummary_editor, FORMAT_MOODLE,
                array('noclean' => true, 'filter' => true));
        $content = "";
        if ($section->ttsectionshowactivities == 1) {
            $content = $this->moodlerenderer->get_course_section_cm_list($this->course, $section);
        }
        $html = "<div class='tt-imagebuttons auto2' data-section='$sectionid'>";
        $html .= "<div class='tt-section-image-link tt-grid-element tt-section-image-link-selected' style='position:relative;'>";
        $html .= "<img class='tt-section-image' src='$imgsource' alt='$sectionname'>";
        $html .= "$sectiontitle";
        $html .= "$sectionsummary";
        $html .= "$content";
        $html .= "<div style='display: flex;'>$subcontent</div>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Function to get section tab of TreeTopics.
     *
     * @param string $section
     * @param string $subcontent
     * @return string
     */
    protected function get_section_display_tab($section, $subcontent) {
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

        // Prepare the container to receive the section display image.
        $colsize = 100 / max($this->course->ttimagegridcolumns, 1);
        $gritemplatecols = "";
        for ($i = 0; $i < $this->course->ttimagegridcolumns; $i++) {
            $gritemplatecols .= "$colsize% ";
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
        $html .= "<!-- sections display image type container -->";
        $html .= "<div class='grid{$this->course->ttimagegridcolumns}' style=''>$subcontent</div>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Function to get map of sections of TreeTopics.
     *
     * @return string
     */
    protected function get_map_sections() {
        $tmp1 = "";
        foreach ($this->sectionstree as $item1) {
            $tmp2 = "";
            foreach ($item1->child as $item2) {
                $tmp3 = "";
                foreach ($item2->child as $item3) {
                    $tmp3 .= $this->get_map_link($item3->section);
                }

                $tmp2 .= $this->get_map_link($item2->section);

                if (strlen($tmp3) > 0) {
                    $tmp2 .= "<ul>$tmp3</ul>";
                }
            }

            $tmp1 .= $this->get_map_link($item1->section);

            if (strlen($tmp2) > 0) {
                $tmp1 .= "<ul>$tmp2</ul>";
            }
        }

        $content = "<ul class='root'>$tmp1</ul>";

        $html = "<div class='section main clearfix tt-section menu-map' role='region' aria-label='carte'";
        $html .= " data-section='map'>";
        $html .= "<h2>Carte</h2>";
        $html .= "<div class='content'>";
        $html .= "$content";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Function to get map link of TreeTopics.
     * @param string $section
     * @return string
     */
    protected function get_map_link($section) {
        if ($section->ttsectioncontentdisplay == TT_DISPLAY_IMAGES) {
            return sprintf("<li>%s<div class='activity-list'>%s</div></li>",
                    $this->get_section_name($section), $this->moodlerenderer->get_course_section_cm_list($this->course, $section));
        } else {
            return sprintf("<li><a href='#' data-section='%s'
                onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>%s<a/>
                <div class='activity-list'>%s</div></li>",
                $this->get_section_id($section), $this->get_section_name($section),
                    $this->moodlerenderer->get_course_section_cm_list($this->course, $section));
        }
    }

    /**
     * Function render pagination of TreeTopics.
     *
     * @return string
     */
    protected function render_pagination() {
        if (!$this->course->ttshownavsection) {
            return "";
        }
        $result = "<nav id='sectionPagination' aria-label='Pagination de sections'
            style='margin-top: 3rem; border-top: 1px solid #efefef; padding-top: 1rem;'>";
        $result .= '<ul class="pagination justify-content-center ">';
        $result .= '<li class="page-item">';
        $result .= sprintf("<a class='page-link' href='#' tabindex='-1'
            onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'><i class='fa fa-arrow-left'></i> %s</a>",
                get_string('prev_section', 'format_treetopics', 'fr_ca'));
        $result .= '</li>';
        $result .= '<li class="page-item">';
        $result .= sprintf("<a class='page-link' href='#' tabindex='-1'
            onclick='M.recit.course.format.TreeTopics.instance.goToSection(event)'>%s <i class='fa fa-arrow-right'></i></a>",
                get_string('next_section', 'format_treetopics', 'fr_ca'));
        $result .= '</li>';
        $result .= '</ul>';
        $result .= '</nav>';

        return $result;

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
     * Function to get section name of TreeTopics.
     * @param string $section
     * @return string
     */
    protected function get_activity_name($activity) {
        return sprintf('[[' . $activity->name . ']]');
    }

    /**
     * Function show contract of TreeTopics.
     * @return boolean
     */
    protected function show_contract() {
        return ($this->course->tthascontract) && (!$this->contract_is_signed());
    }

    /**
     * Function signing contract of TreeTopics.
     */
    protected function signing_contract() {
        if ((isset($_GET["ttc"])) && ($_GET["ttc"] == '1')) {
            $this->contract_sign();
        }
        // Could have "else{$this->contract_unsign();}".
    }

    /**
     * Function for checking signed contract of TreeTopics.
     * @return boolean
     */
    protected function contract_is_signed() {
        global $DB, $USER;
        $result = $DB->record_exists('format_treetopics_contract', ['courseid' => $this->course->id, 'userid' => $USER->id]);
        return $result;
    }

    /**
     * Function to update signed contract of TreeTopics.
     */
    protected function contract_sign() {
        global $DB, $USER;

        if (!$this->contract_is_signed()) {
            $DB->insert_record('format_treetopics_contract',
                    array('courseid' => $this->course->id, 'userid' => $USER->id, 'timemodified' => time()));
        }
    }

    /**
     * Function render contract of TreeTopics.
     * @return string
     */
    protected function render_contract() {
        global $CFG;

        $signed = $this->contract_is_signed();

        $section = $this->sectionslist[0];
        $html = $this->render_section_content_item($section);

        $html .= "<br/><br/>";
        $html .= html_writer::start_tag('div', array('class' => self::ID_APPEND . 'contract'));
        $html .= html_writer::tag('h2', "Contrat d'engagement", array('class' => self::ID_APPEND . 'contract-title'));
        $html .= html_writer::tag('pre', $section->ttcontract_editor, array('class' => self::ID_APPEND . 'contract-content'));

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

    public function render_editing_mode($format_treetopics_renderer){
        global $CFG, $COURSE;

        $selectedSection = (isset($_COOKIE['section']) ? $_COOKIE['section'] : 'menu');

        $result = '<div class="row">';
        $result .= '<div class="bg-light p-2 w-100">';
        $result .= '<button class="btn btn-outline-primary btn-sm m-1 mr-2" type="button" data-toggle="collapse" data-target="#navTabs" aria-expanded="false" aria-controls="navTabs"><i class="fa fa-bars"></i></button>';
        $result .= '<span class="h6 text-muted">Liste de sections</span>';
        $result .= '</div>';
        $result .= '</div>';
        $result .= '<div class="row">';        
        $result .= '<div class="col-xs col-sm-4 col-lg-2 collapse show p-0" id="navTabs">';
        $result .= '<div class="nav nav-pills bg-light  flex-column" id="v-pills-tab" role="tablist" aria-orientation="vertical">';

        $sectionid = 'menu';
        $templateNavItem = "<a class='nav-item nav-link %s' id='v-pills-%s-tab' data-toggle='pill' href='#v-pills-%s' role='tab' 
                            onclick='M.recit.course.format.TreeTopicsEditingMode.instance.goToSection(event, \"%s\")'
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

        $html = "";
        $html .= sprintf("<a href='%s/course/changenumsections.php?courseid=%ld&insertsection=0&sesskey=%s&sectionreturn=0' 
                        class='btn btn-outline-primary pull-right'><i class='fa fa-plus'></i> %s</a>", 
                        $CFG->wwwroot, $COURSE->id, sesskey(), get_string('addsections', 'format_treetopics'));
        $html .= "<br/><br/><br/>";

        foreach ($this->sectionslist as $section) {
            $html .= $this->render_editing_mode_section_content($format_treetopics_renderer, $section, true);
        }       

        $sectionid = 'menu';
        $templateTabContent = '<div class="tab-pane fade show %s p-2" id="v-pills-%s" role="tabpanel" aria-labelledby="v-pills-%s-tab">%s</div>';
        $result .= sprintf($templateTabContent, ($selectedSection === $sectionid ? 'active editing-mode-menu' : 'editing-mode-menu'), $sectionid, $sectionid, $html);

        foreach ($this->sectionslist as $section) {
            $sectionid = $this->get_section_id($section);
            $result .= sprintf($templateTabContent, ($selectedSection === $sectionid ? 'active' : ''), $sectionid, $sectionid, 
                        $this->render_editing_mode_section_content($format_treetopics_renderer, $section));
        }
        
        $result .= '</div>';
        $result .= '</div>';
        $result .= '</div>';
        return $result;
    }

    protected function render_editing_mode_section_content($format_treetopics_renderer, $section, $showMenu = false){
        global $CFG;

        // Title with completion help icon.
        $completioninfo = new completion_info($this->course);
        $sectionid = $this->get_section_id($section);

        $result = "";
        $result .= $completioninfo->display_help_icon();
        
        if($showMenu){
            $result .= $format_treetopics_renderer->section_header($section, $this->course, false, 0, false);
            $result .= sprintf("<div class='section_add_menus' id='add_menus-%s'></div>", $sectionid);
            $result .= $format_treetopics_renderer->section_footer();
        }
        else{   
            $result .= sprintf("<h2>%s</h2>",  $this->get_section_name($section));
            $result .= sprintf("<a class='btn btn-outline-primary pull-right' href='%s/course/editsection.php?id=%ld&sr'><i class='fa fa-fw fa-pencil-alt'></i> %s</a>", $CFG->wwwroot, 
                            $section->id, get_string('editsection', 'format_treetopics'));
            $result .= "<br/><br/>";
            $result .= $format_treetopics_renderer->section_header($section, $this->course, false, 0, true, false);
            $result .= $format_treetopics_renderer->get_course_section_cm_list($this->course, $section);
            $result .= $format_treetopics_renderer->get_course_section_add_cm_control($this->course, $section->section);
            $result .= $format_treetopics_renderer->section_footer();
        }
        
        return $result;
    }
}

/**
 * Basic renderer for topics format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_treetopics_renderer extends format_section_renderer_base {

    /** @var TreeTopics */
    protected $treetopics = null;

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

        // Since format_treetopics_renderer::section_edit_controls() only displays the 'Set current section' control
        // when editing mode is on.
        // We need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other
        // managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');

        $this->treetopics = new TreeTopics();
    }

    /**
     * Function render tree topics of format_treetopics_renderer class.
     * @param stdClass $course
     */
    public function render_tree_topics($course) {

        if ($this->page->user_is_editing()) {
            switch (self::EDITING_MODE_OPTION) {
                case 1: 
                    $this->print_multiple_section_page($course, null, null, null, null);
                    break;
                case 2:
                    $this->treetopics->load($this, course_get_format($course)->get_course());
                    echo $this->treetopics->render_editing_mode($this);
                    break;
            }
        } else {
            echo $this->output->heading($this->page_title(), 2, 'accesshide');
            echo $this->course_activity_clipboard($course, 0);  // Copy activity clipboard..
            $this->treetopics->load($this, course_get_format($course)->get_course());
            $this->treetopics->render();
        }
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        // Could "return html_writer::start_tag('div', array('class' => 'treetopics'));".
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
     * Function get course section cm list of format_treetopics_renderer class.
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
        $contentdisplay = "";

        //if ($course->ttdisplayshortcuts) {
            $radiosectionlevel = '<label><input name="ttRadioSectionLevel%ld" data-component="ttRadioSectionLevel" type="radio" value="%s"  %s> %s</label>';

            $level = "";
            if ($section->section > 0) {
                $level = sprintf('<form class="inline-form-editing-mode">%s%s%s</form>',
                sprintf($radiosectionlevel, $section->section, "1",
                        ($section->ttsectiondisplay == 1 ? "checked" : ""), get_string('displaytabslev1', 'format_treetopics')),
                sprintf($radiosectionlevel, $section->section, "2",
                        ($section->ttsectiondisplay == 2 ? "checked" : ""), get_string('displaytabslev2', 'format_treetopics')),
                "");
                // Code 'sprintf($radiosectionlevel, ($section->ttsectiondisplay == 3 ? "active" : ""), "3",
                // ($section->ttsectiondisplay == 3 ? "checked" : ""), get_string('displaytabslev3', 'format_treetopics')));'.
            }

            $radiosectioncontentdisplay ='<label  ><input name="ttRadioSectionContentDisplay%ld" data-component="ttRadioSectionContentDisplay" type="radio" value="%s"  %s> %s</label>';

            $contentdisplay = sprintf('<form class="inline-form-editing-mode">%s%s</form>',
                sprintf($radiosectioncontentdisplay, $section->section, "-1",
                        ($section->ttsectioncontentdisplay == -1 ? "checked" : ""),
                        get_string('displaytabs', 'format_treetopics')),
                sprintf($radiosectioncontentdisplay, $section->section, "-2",
                        ($section->ttsectioncontentdisplay == -2 ? "checked" : ""),
                        get_string('displayimages', 'format_treetopics')));
       // }

        $html = sprintf("<span style='display: flex; align-items: end; line-height: 40px;'>%s%s%s</span>", $sectionname, $level, $contentdisplay);

        return $html;
    }
}
