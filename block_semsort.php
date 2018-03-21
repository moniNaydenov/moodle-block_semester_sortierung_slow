<?php
// This file is part of block_semsort for Moodle - http://moodle.org/
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
 * Semester overview block.. partial copy from the course overview block. Displays a list of all the courses,
 * divided by semester. Uses ajax to update data.
 *
 * @package       block_semsort
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class block_semsort extends block_base {

    /**
     * block initializations
     */
    public function init() {
        // Set the title of the block.
        $this->title   = get_string('pluginname', 'block_semsort');

    }

    /**
     * prevent addition of more than one block instance
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * prevent user from hiding the block
     *
     * @return boolean
     */
    public function instance_can_be_hidden() {
        return false;
    }

    /**
     * block contents
     *
     * @return object
     */
    public function get_content() {
        require_once(__DIR__ . '/locallib.php');
        global $USER, $CFG, $PAGE;
        // If content already present, spare time.
        if ($this->content !== null) {
            return $this->content;
        }

        $config = get_config('block_semsort');

        $this->config = $config;

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $content = array();

        $renderer = $this->page->get_renderer('block_semsort');

        $content[] = $renderer->semsort($this);

        $this->content->text = implode($content); // Compile the text from the array.

        return $this->content;
    }

    public function export_for_template(renderer_base $output) {

        $context = new stdClass;

        // Get the information about the enrolled courses.
        $courses = enrol_get_my_courses('id, fullname, shortname, summary, summaryformat, enddate', 'visible DESC, fullname ASC');
        $cid = optional_param('block_semsort_favorites', null, PARAM_ALPHANUM);
        $status = optional_param('status', null, PARAM_ALPHANUM);
        if (!empty($cid)) {
            block_semsort_toggle_fav($cid, $status);
        }

        block_semsort_update_personal_sort($this->config);
        // Some moodle hacks..
        $site = get_site();
        $course = $site; // Just in case we need the old global $course hack.

        // Get remote courses.. not really needed but part of the Course overview block, so keep it.
        if (is_enabled_auth('mnet')) {
            $remotecourses = get_my_remotecourses();
        }
        if (empty($remotecourses)) {
            $remotecourses = array();
        }

        // Moodle hack - removes the site(main course with id usually 0 or 1) from the list of courses
        // There is no information in that "course".
        if (array_key_exists($site->id, $courses)) {
            unset($courses[$site->id]);
        }

        foreach ($courses as $c) {
            if (isset($USER->lastcourseaccess[$c->id])) {
                $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
            } else {
                $courses[$c->id]->lastaccess = 0;
            }
        }
        // Add the semester to each course.
        $courses = block_semsort_fill_course_semester($courses, $this->config);
        $courses = block_semsort_sort_user_personal_sort($courses, $this->config);

        $context->courses = $courses;

        if ($semestersexpanded = get_user_preferences('semsort_semesters', '')) {
            $context->semestersexpanded = array_flip(explode(',', $semestersexpanded));
        } else {
            $context->semestersexpanded = array();
        }

        if ($coursesexpanded = get_user_preferences('semsort_courses', '')) {
            $coursesexpanded = array_flip(explode(',', $coursesexpanded));
        } else {
            $coursesexpanded = array();
        }

        if ($favorites = get_user_preferences('semsort_favorites', '')) {
            $context->favorites = array_flip(explode(',', $favorites));
        } else {
            $context->favorites = array();
        }

        $context->coursesexpanded = array();

        $count = 0;
        $autoclose = isset($this->config->autoclose) ? intval($this->config->autoclose) : 0;
        // Create an array with expanded courses to be filled up with info.
        foreach ($context->courses as $semester => $semesterinfo) {
            if ($autoclose > 0 && $count >= $autoclose) {
                break; // Don't allow more than three semesters opened (performance reasons);
            }
            foreach ($semesterinfo['courses'] as $id => $courseinfo) {
                if (isset($coursesexpanded[strval($courseinfo->id)])) {
                    $context->coursesexpanded[$courseinfo->id] = $courseinfo;
                }
            }
            $count++;
        }

        $context->exportedevents = block_semsort_get_courses_events($context->coursesexpanded, $output);

        // Whether the courses should be sorted.
        $context->sorted = (isset($this->config->sortcourses) && $this->config->sortcourses == '1');

        $context->showfavorites = (isset($this->config->enablefavorites) && $this->config->enablefavorites == '1');
        $context->personalsort = (isset($this->config->enablepersonalsort) && $this->config->enablepersonalsort == '1')
                                 && $context->sorted;
        // Prevent personal sort when not enabled in the setting.
        $context->userediting = $context->personalsort && $this->page->user_is_editing();

        $context->remotecourses = $remotecourses;
        $context->config = $this->config;

        // Archive magic comes here...
        if ($this->config->archive != 0) {
            $currentsemester = block_semsort_get_semester($this->config, time());
            $currentsemester = $currentsemester->semester_short;
            $year = intval(substr($currentsemester, 0, 4)); // TODO: improve, this works only for Gregorian!
            $semester = substr($currentsemester, 4);
            $archive = intval($this->config->archive);

            $year -= floor($archive / 2);
            if ($archive % 2 != 0) { // Number is odd, so semester changes from winter to summer or vs..
                if ($semester == 'S') {
                    $year--;
                    $semester = 'W';
                } else {
                    $semester = 'S';
                }
            }
            $context->archive = strval($year) . $semester;
        } else {
            $context->archive = false;
        }
        return $context;
    }


    /**
     * enable the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true, 'my' => true);
    }

    /**
     * prevent user from editing
     *
     * @return boolean
     */
    public function user_can_edit() {
        // Return false; not needed in 2.5 since block protection is working.
        return true;
    }

    /**
     * Sets whether the block can be configured
     *
     * @return boolean false
     */
    public function instance_allow_config() {
        return false;
    }

    /**
     * Prints the block configuration. since the block is not configurable, nothing gets printed
     *
     * @return false
     */
    public function instance_config_print() {
        // Default behavior: print the config_instance.html file
        // You don't need to override this if you're satisfied with the above.
        if (!$this->instance_allow_multiple() && !$this->instance_allow_config()) {
            return false;
        }
        return true;
    }


    /**
     * loads the required javascript to run semsort
     *
     */
    public function get_required_javascript() {
        $arguments = array(
            'id'             => $this->instance->id
        );
        $this->page->requires->yui_module(array('core_dock', 'moodle-block_semsort-semsort'),
            'M.block_semsort.init_add_semsort', array($arguments));
    }


}
