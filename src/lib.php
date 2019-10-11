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
require_once($CFG->dirroot. '/course/format/lib.php');

const TT_DISPLAY_NONE = 0;
const TT_DISPLAY_TABS = -1;
const TT_DISPLAY_IMAGES = -2;

const TT_DISPLAY_TABS_LEVEL_1 = 1;
const TT_DISPLAY_TABS_LEVEL_2 = 2;
const TT_DISPLAY_TABS_LEVEL_3 = 3;

function format_treetopics_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=[]) {
    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);
 
    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.
 
    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.
 
    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }
 
    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'format_treetopics', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }
 
    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering. 
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * Main class for the Topics course format
 *
 * @package    format_treetopics
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_treetopics extends format_base {

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #")
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                    array('context' => context_course::instance($this->courseid)));
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the topics course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of format_base::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_treetopics');
        } else {
            // Use format_base::get_default_section_name implementation which
            // will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        global $CFG;
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', array('id' => $course->id));

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            if ($sr !== null) {
                if ($sr) {
                    $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
                    $sectionno = $sr;
                } else {
                    $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
                }
            } else {
                //$usercoursedisplay = $course->coursedisplay;
                $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
            }
            if ($sectionno != 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $url->param('section', $sectionno);
            } else {
                if (empty($CFG->linkcoursesections) && !empty($options['navigation'])) {
                    return null;
                }
                $url->set_anchor('section-'.$sectionno);
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Loads all of the course sections into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // if section is specified in course/view.php, make sure it is expanded in navigation
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                    $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // check if there are callbacks to extend course navigation
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    function ajax_section_move() {
        global $PAGE;
        $titles = array();
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return array('sectiontitles' => $titles, 'action' => 'move');
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return array(
            BLOCK_POS_LEFT => array(),
            BLOCK_POS_RIGHT => array()
        );
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * Topics format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ),
                'ttsectiondisplay' => array(
                    'default' => TT_DISPLAY_TABS,
                    'type' => PARAM_INT,
                ),
                'tttabsmodel' => array(
                    'default' => 5,
                    'type' => PARAM_INT,
                ),
                'ttimagegridcolumns' => array(
                    'default' => 4,
                    'type' => PARAM_INT,
                ),
                'tthascontract' => array(
                    'default' => false,
                    'type' => PARAM_BOOL,
                ),
                'ttcontract' => array(
                    'default' => '',
                    'type' => PARAM_TEXT,
                )
            );
        }
        if ($foreditform && !isset($courseformatoptions['ttsectiondisplay']['label'])) {
            $courseformatoptionsedit = array(
                'hiddensections' => array(
                        'label' => new lang_string('hiddensections'),
                        'help' => 'hiddensections',
                        'help_component' => 'moodle',
                        'element_type' => 'select',
                        'element_attributes' => array(
                            array(
                                0 => new lang_string('hiddensectionscollapsed'),
                                1 => new lang_string('hiddensectionsinvisible')
                            )
                        )
                ),
                'ttsectiondisplay' => array(
                    'label' => new lang_string('sectionmaindisplay', 'format_treetopics'),
                    'help' => 'sectionmaindisplay',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            TT_DISPLAY_TABS => new lang_string('displaytabs', 'format_treetopics'),
                            TT_DISPLAY_IMAGES => new lang_string('displayimages', 'format_treetopics')
                        )
                    )
                ),
                'tttabsmodel' => array(
                    'label' => new lang_string('tabsmodel', 'format_treetopics'),
                    'help' => 'tabsmodel',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => 'Model 1',
                            5 => 'Model 5'
                        )
                    )
                ),
                'ttimagegridcolumns' => array(
                    'label' => new lang_string('imagegridcolumns', 'format_treetopics'),
                    'help' => 'imagegridcolumns',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => '1',
                            2 => '2',
                            3 => '3',
                            4 => '4',
                            5 => '5',
                            6 => '6'
                        )
                    )
                ),
                'tthascontract' => array(
                    'label' => new lang_string('hascontract', 'format_treetopics'),
                    'help' => 'hascontract',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'checkbox'
                ),
                'ttcontract' => array(
                    'label' => new lang_string('contract', 'format_treetopics'),
                    'help' => 'contract',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'textarea',
                    'element_attributes' => array('wrap="virtual" rows="20" cols="80"')
                )
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }
    
    /**
     * Definitions of the additional options that this course format uses for section
     *
     * See {@link format_base::course_format_options()} for return array definition.
     *
     * Additionally section format options may have property 'cache' set to true
     * if this option needs to be cached in {@link get_fast_modinfo()}. The 'cache' property
     * is recommended to be set only for fields used in {@link format_base::get_section_name()},
     * {@link format_base::extend_course_navigation()} and {@link format_base::get_view_url()}
     *
     * For better performance cached options are recommended to have 'cachedefault' property
     * Unlike 'default', 'cachedefault' should be static and not access get_config().
     *
     * Regardless of value of 'cache' all options are accessed in the code as
     * $sectioninfo->OPTIONNAME
     * where $sectioninfo is instance of section_info, returned by
     * get_fast_modinfo($course)->get_section_info($sectionnum)
     * or get_fast_modinfo($course)->get_section_info_all()
     *
     * All format options for particular section are returned by calling:
     * $this->get_format_options($section);
     *
     * @param bool $foreditform
     * @return array
     */
    public function section_format_options($foreditform = false) {
        /*static */$sectionformatoptions = false;
        if ($sectionformatoptions === false) {
            $sectionformatoptions = array(
                'ttsectiondisplay' => array(
                    'default' => TT_DISPLAY_TABS_LEVEL_1,
                    'type' => PARAM_INT
                ),
                'ttsectioncontentdisplay' => array(
                    'default' => TT_DISPLAY_TABS,
                    'type' => PARAM_INT
                ),
                'ttsectionshowactivities' => array(
                    'default' => true,
                    'type' => PARAM_BOOL
                ),
                'ttsectionfile' => array(
                    'default' => '',
                    'type' => PARAM_FILE
                ),
                'ttsectiontitle' => array(
                    'default' => true,
                    'type' => PARAM_BOOL
                ),
                'ttsectionimage-context' => array(
                    'default' => '',
                    'type' => PARAM_TEXT
                ),
                'ttsectionimage-component' => array(
                    'default' => '',
                    'type' => PARAM_TEXT
                ),
                'ttsectionimage-filearea' => array(
                    'default' => '',
                    'type' => PARAM_TEXT
                ),
                'ttsectionimage-itemid' => array(
                    'default' => '',
                    'type' => PARAM_TEXT
                ),
                'ttsectionimage-filepath' => array(
                    'default' => '',
                    'type' => PARAM_TEXT
                ),
                'ttsectionimage-filename' => array(
                    'default' => '',
                    'type' => PARAM_TEXT
                )
            );
        }
        if ($foreditform/* && !isset($sectionformatoptions['ttsectioncontentdisplay']['label'])*/) {
            $course = $this->get_course();
            $coursesections = array();
            $sectionformatoptionsedit = array(
                'ttsectiondisplay' => array(
                    'label' => new lang_string('sectiondisplay', 'format_treetopics'),
                    'help' => 'sectiondisplay',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            TT_DISPLAY_TABS_LEVEL_1 => new lang_string('displaytabslev1', 'format_treetopics'),
                            TT_DISPLAY_TABS_LEVEL_2 => new lang_string('displaytabslev2', 'format_treetopics'),
                            TT_DISPLAY_TABS_LEVEL_3 => new lang_string('displaytabslev3', 'format_treetopics'),
                            TT_DISPLAY_IMAGES => new lang_string('displayimages', 'format_treetopics')
                        )
                    )
                ),
                'ttsectioncontentdisplay' => array(
                    'label' => new lang_string('sectioncontentdisplay', 'format_treetopics'),
                    'help' => 'sectioncontentdisplay',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            TT_DISPLAY_IMAGES => new lang_string('displayimages', 'format_treetopics'),
                            TT_DISPLAY_TABS => new lang_string('displaytabs', 'format_treetopics')
                        )
                    )
                ),
                'ttsectionshowactivities' => array(
                    'label' => new lang_string('sectionshowactivities', 'format_treetopics'),
                    'help' => 'sectionshowactivities',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'checkbox'
                ),
                'ttsectionfile' => array(
                    'label' => new lang_string('sectionimage', 'format_treetopics'),
                    'help' => 'sectionimage',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'filemanager',
                    'element_attributes' => array(
                        'accepted_types' => array('image'),
                        'maxfiles' => 1,
                        'subdirs' => 0
                    )
                ),
                'ttsectiontitle' => array(
                    'label' => new lang_string('showsectiontitle', 'format_treetopics'),
                    'help' => 'showsectiontitle',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'checkbox'
                ),
                'ttsectionimage-context' => array(
                    'label' => new lang_string('sectionimagecontext', 'format_treetopics'),
                    'help' => 'sectionimagecontext',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'hidden'
                ),
                'ttsectionimage-component' => array(
                    'label' => new lang_string('sectionimagecomponent', 'format_treetopics'),
                    'help' => 'sectionimagecomponent',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'hidden'
                ),
                'ttsectionimage-filearea' => array(
                    'label' => new lang_string('sectionimagefilearea', 'format_treetopics'),
                    'help' => 'sectionimagefilearea',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'hidden'
                ),
                'ttsectionimage-itemid' => array(
                    'label' => new lang_string('sectionimageitemid', 'format_treetopics'),
                    'help' => 'sectionimageitemid',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'hidden'
                ),
                'ttsectionimage-filepath' => array(
                    'label' => new lang_string('sectionimagefilepath', 'format_treetopics'),
                    'help' => 'sectionimagefilepath',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'hidden'
                ),
                'ttsectionimage-filename' => array(
                    'label' => new lang_string('sectionimagefilename', 'format_treetopics'),
                    'help' => 'sectionimagefilename',
                    'help_component' => 'format_treetopics',
                    'element_type' => 'hidden'
                )
            );
            $sectionformatoptions = array_merge_recursive($sectionformatoptions, $sectionformatoptionsedit);
        }
        return $sectionformatoptions;
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" element to the create course form - it will force new course to be prepopulated
            // with empty sections.
            // The "Number of sections" option is no longer available when editing course, instead teachers should
            // delete and add sections when needed.
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        return $elements;
    }
    
    /**
     * Updates format options for a course
     *
     * In case if course format was changed to 'treetopics', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            //var_dump($data);
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    }
                }
            }
        }
        
        return $this->update_format_options($data);
    }
    
    public function update_section_format_options($data) {
        global $CFG;
        $data = (array)$data;
        $course = $this->get_course();
        $context = context_course::instance($course->id);
        $attachmentid = $data['ttsectionfile'];
        
        file_prepare_draft_area($attachmentid, $context->id, 'format_treetopics', 'attachment', $data['id'], array('subdirs' => 0, 'maxbytes' => 4096, 'maxfiles' => 64));
        file_save_draft_area_files($attachmentid, $context->id, 'format_treetopics', 'attachment', $data['id'], array('subdirs' => 0, 'maxbytes' => 4096, 'maxfiles' => 64));
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'format_treetopics', 'attachment', $data['id'], '', false);
        foreach ($files as $file) {
          if($file->get_itemid() == $data['id'])
          {
              /*switch($file->get_mimetype())
              {
                  case 'image/png':
                    $ext = '.png';
                    break;
                  case 'image/jpeg':
                    $ext = '.jpg';
                    break;
                  case 'image/gif':
                    $ext = '.gif';
                    break;
                  default:
                    $ext = '.bin';
                  continue;
              }*/
              //$local = '/course/format/treetopics/sectionimages/'.$course->id.'_'.$data['id'].$ext;
              //$file->copy_content_to($CFG->dirroot.$local);
              //$data['ttsectionimage'] = $CFG->wwwroot.$local;
              $data['ttsectionimage-context'] = $file->get_contextid();
              $data['ttsectionimage-component'] = $file->get_component();
              $data['ttsectionimage-filearea'] = $file->get_filearea();
              $data['ttsectionimage-itemid'] = $file->get_itemid();
              $data['ttsectionimage-filepath'] = $file->get_filepath();
              $data['ttsectionimage-filename'] = $file->get_filename();
              file_save_draft_area_files($attachmentid, $file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), array('subdirs' => 0));
              break;
          }
          
        }
        return $this->update_format_options($data, $data['id']);
    }
    
    /**
     * Updates format options for a course or section
     *
     * If $data does not contain property with the option name, the option will not be updated
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param null|int null if these are options for course or section id (course_sections.id)
     *     if these are options for section
     * @return bool whether there were any changes to the options values
     */
    protected function update_format_options($data, $sectionid = null) {
        global $DB;
        //$data = $this->validate_format_options((array)$data, $sectionid);
        if (!$sectionid) {
            $allformatoptions = $this->course_format_options();
            $sectionid = 0;
        } else {
            $allformatoptions = $this->section_format_options();
        }
        if (empty($allformatoptions)) {
            // nothing to update anyway
            return false;
        }
        $defaultoptions = array();
        $cached = array();
        foreach ($allformatoptions as $key => $option) {
            $defaultoptions[$key] = null;
            if (array_key_exists('default', $option)) {
                $defaultoptions[$key] = $option['default'];
            }
            $cached[$key] = ($sectionid === 0 || !empty($option['cache']));
        }
        $records = $DB->get_records('course_format_options',
                array('courseid' => $this->courseid,
                      'format' => $this->format,
                      'sectionid' => $sectionid
                    ), '', 'name,id,value');
        $changed = $needrebuild = false;
        foreach ($defaultoptions as $key => $value) {
            if (isset($records[$key])) {
                if (array_key_exists($key, $data) && $records[$key]->value !== $data[$key]) {
                    $DB->set_field('course_format_options', 'value',
                            $data[$key], array('id' => $records[$key]->id));
                    $changed = true;
                    $needrebuild = $needrebuild || $cached[$key];
                }
            } else {
                if (array_key_exists($key, $data) && $data[$key] !== $value) {
                    $newvalue = $data[$key];
                    $changed = true;
                    $needrebuild = $needrebuild || $cached[$key];
                } else {
                    $newvalue = $value;
                    // we still insert entry in DB but there are no changes from user point of
                    // view and no need to call rebuild_course_cache()
                }
                $DB->insert_record('course_format_options', array(
                    'courseid' => $this->courseid,
                    'format' => $this->format,
                    'sectionid' => $sectionid,
                    'name' => $key,
                    'value' => $newvalue
                ));
            }
        }
        if ($needrebuild) {
            rebuild_course_cache($this->courseid, true);
        }
        if ($changed) {
            // reset internal caches
            if (!$sectionid) {
                $this->course = false;
            }
            unset($this->formatoptions[$sectionid]);
        }
        return $changed;
    }

    /**
     * Whether this format allows to delete sections
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Prepares the templateable object to display section name
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return \core\output\inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
                                                         $editable = null, $edithint = null, $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_treetopics');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_treetopics', $title);
        }
        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    public function section_action($section, $action, $sr) {
        global $PAGE;

        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'treetopics' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_treetopics');
        $rv['section_availability'] = $renderer->section_availability($this->get_section($section));
        return $rv;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        return $this->get_format_options();
    }
    
    private function console_log( $data ){
        echo '<script>';
        echo 'console.log('. json_encode( $data ) .')';
        echo '</script>';
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_treetopics_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'treetopics'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}
