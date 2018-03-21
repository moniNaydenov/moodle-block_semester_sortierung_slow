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
 * Local functions
 *
 * @package       block_semsort
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function block_semsort_usort($a, $b) {
    return strcasecmp(trim($a->fullname), trim($b->fullname));
}

function block_semsort_toggle_fav($courseid, $status) {

    if ($favorites = get_user_preferences('semsort_favorites', '')) {
        $favorites = explode(',', $favorites);
        $favorites = array_flip($favorites);
    } else {
        $favorites = array();
    }

    if ($status) {
        $favorites[$courseid] = 1;
    } else {
        if (isset($favorites[$courseid])) {
            unset($favorites[$courseid]);
        }
    }
    $favorites = implode(',', array_keys($favorites));

    set_user_preference('semsort_favorites', $favorites);
}

function block_semsort_update_personal_sort($config = null) {
    global $USER, $DB;

    // Get all possible parameters.
    $movecourse = optional_param('block_semsort_move_course', 0, PARAM_INT);
    $movecoursesemester = optional_param('block_semsort_move_semester', '', PARAM_ALPHANUM);
    $movecoursetarget = optional_param('block_semsort_move_target', -1, PARAM_INT);

    if ($movecourse <= 0 || $movecoursesemester == '' || $movecoursetarget < 0) {
        return; // Not needed.
    }

    if (is_null($config)) {
        $config = get_config('block_semsort');
    }

    if ((isset($config->enablepersonalsort) && $config->enablepersonalsort == '1') == false) {
        return; // Personal sorting disabled.
    }

    $courses = enrol_get_my_courses('id, fullname, shortname', 'visible DESC, fullname ASC');

    if (!isset($courses[$movecourse])) {
        return; // Invalid course id.
    }
    $context = context_course::instance($courses[$movecourse]->id);

    if (is_enrolled($context) != true) {
        return; // Invalid course id.
    }

    $courses = block_semsort_fill_course_semester($courses, $config, $movecoursesemester);
    $courses = block_semsort_sort_user_personal_sort($courses, $config, true);

    $usersort = block_semsort_get_usersort($USER->id);

    if (!isset($usersort[$movecoursesemester])) {
        return; // Invalid semester.
    }
    $courseorder = explode(',', $usersort[$movecoursesemester]->courseorder);

    if (!in_array($movecourse, $courseorder)) {
        return; // Invalid course/semester combination.
    }

    if (($key = array_search($movecourse, $courseorder)) !== false) {
        unset($courseorder[$key]); // Remove the course from the list.
    }
    $courseorder = array_values($courseorder);

    if ($movecoursetarget > count($courseorder)) {
        $movecoursetarget = count($courseorder); // Prevent invalid input.
    } else if ($movecoursetarget < 0) {
        $movecoursetarget = 0; // Prevent invalid input.
    }

    array_splice($courseorder, $movecoursetarget, 0, $movecourse); // Add the course at the new index.

    $usersort[$movecoursesemester]->courseorder = implode(',', array_values($courseorder)); // Clear out indexes.
    $DB->update_record('block_semsort_usersort', $usersort[$movecoursesemester]); // Save setting!

}

/**
 * add the "semester" variable to each semester. The semester is calculated according to the settings and the course
 * start date. Thus, each course MUST have a course date
 *
 */
function block_semsort_fill_course_semester($courses, $config, $chosensemester = null) {
    $sortedcourses = array();

    $showfavorites = isset($config->enablefavorites) && $config->enablefavorites == '1';
    $favorites = array();

    if ($showfavorites) {
        // Favorites are not enabled, nothing to do here.
        if ($favorites = get_user_preferences('semsort_favorites', '')) {
            $favorites = array_flip(explode(',', $favorites));
        }
        $sortedcourses['fav'] = array(
            'visible' => array(),
            'hidden' => array(),
            'title' => get_string('favorites', 'block_semsort')
        );
    }

    foreach ($courses as $course) {
        $semester = block_semsort_get_semester($config, $course->startdate);
        $course->semester_short = $semester->semester_short;
        if (empty($sortedcourses[$course->semester_short])) {
            $sortedcourses[$course->semester_short] = array(
                'visible' => array(),
                'hidden' => array(),
                'title' => $semester->semester
            );
        }
        $visible = $course->visible ? 'visible' : 'hidden';

        $sortedcourses[$course->semester_short][$visible][$course->id] = $course;
        if (isset($favorites[$course->id])) {
            $course = clone $course;
            $course->semester_short = 'fav';
            $sortedcourses['fav'][$visible][$course->id] = $course;
        }
    }
    krsort($sortedcourses);
    foreach ($sortedcourses as $semester => $groups) {
        // Esed to spare some some work after that when only changing personal sorting.
        if (!is_null($chosensemester) && $semester != $chosensemester) {
            unset($sortedcourses[$semester]);
            continue;
        }
        uasort($groups['visible'], 'block_semsort_usort');
        uasort($groups['hidden'], 'block_semsort_usort');
        $sortedcourses[$semester]['courses'] = $groups['visible'] + $groups['hidden'];
        unset($sortedcourses[$semester]['visible']);
        unset($sortedcourses[$semester]['hidden']);
    }
    return $sortedcourses;
}

function block_semsort_sort_user_personal_sort($sortedcourses, $config, $forcecreate = false) {

    global $DB, $USER;

    if ((isset($config->enablepersonalsort) && $config->enablepersonalsort == '1') == false) {
        // Nothing to do.
        return $sortedcourses;
    }

    $usersort = block_semsort_get_usersort($USER->id);

    // Go through all semesters.
    foreach ($sortedcourses as $semester => $semesterinfo) {
        if ($forcecreate && !isset($usersort[$semester])) {
            $us = new stdClass;
            $us->semester = $semester;
            $us->courseorder = '';
            $us->lastmodified = time();
            $us->userid = $USER->id;
            $us->id = $DB->insert_record('block_semsort_usersort', $us);
            $us->courseorder = array();
            $usersort[$semester] = $us;
        } else if (isset($usersort[$semester])) {
            $usersort[$semester]->courseorder = explode(',', $usersort[$semester]->courseorder);
        }

        // Check if existing in the user preference; if not, don't do anything and keep the current sort.
        if (isset($usersort[$semester])) {
            $usersorted = array();
            $sempref = $usersort[$semester]->courseorder; // Short for $usersorted[$semester]. This is not commented code!
            $courses = $semesterinfo['courses']; // Short for $semesterinfo['courses']. This is not commented code (codecheker)!

            $userprefchanged = false;
            // Go through all courses in the preference and add them to the new sorted list in the desired order.
            foreach ($sempref as $i => $sortedid) {
                // Check whether course exists in user courses.
                if (isset($courses[$sortedid])) {
                    $usersorted[$sortedid] = $courses[$sortedid];
                    unset($courses[$sortedid]);
                } else {
                    // Remove non-existent courses.
                    $userprefchanged = true;
                    unset($sempref[$i]);
                }
            }

            // Checks if any unsorted courses are left; if that is the case, add them to the sorted list in the
            // Default order.
            foreach ($courses as $id => $course) {
                $userprefchanged = true;
                $usersorted[$id] = $course;
                $sempref[] = $id;
            }

            $usersort[$semester]->courseorder = implode(',', array_values($sempref)); // Extract the values only
            $sortedcourses[$semester]['courses'] = $usersorted; // Replaces old sorted courses with user sorted courses.
            // In case there was a change, update the db.
            if ($userprefchanged) {
                $DB->update_record('block_semsort_usersort', $usersort[$semester]);
            }
        }
    }

    return $sortedcourses;

}

/**
 * convert a date to a valid semester
 *
 * @return string
 */
function block_semsort_get_semester($config, $timestamp) {
    global $CFG;
    $month = userdate($timestamp, '%m');
    $year = userdate($timestamp, '%Y');
    $prevyear = strval((intval($year) - 1));
    $nextyear = strval((intval($year) + 1));
    $semester = '';
    $short = '';

    if (isset($config->wintermonths) && strpos($config->wintermonths, 'mon'.$month) !== false) {
        if (intval($month) <= 6) {
            $short = $prevyear . 'W';
            $semester = $prevyear .'/' . $year;
        } else {
            $short = $year . 'W';
            $semester = $year .'/' . $nextyear;
        }
        $semester = get_string('wintersem', 'block_semsort') . '  ' . $semester;
    } else {
        $short = $year . 'S';
        $semester = get_string('summersem', 'block_semsort') . '  ' . $year;
    }

    $ret = new stdClass;
    $ret->semester = $semester;
    $ret->semester_short = $short;
    return $ret;

}


function block_semsort_get_events($vault, $courseids, $timesortfrom, $timesortto) {
    global $USER;
/*
    $categoryids = array_map(function($category) {
        return $category->id;
    }, \coursecat::get_all());

*/

    $categoryids = null;
    $groupids = array_reduce($courseids, function($carry, $courseid) use ($USER) {
        $groupings = groups_get_user_groups($courseid, $USER->id);
        // Grouping 0 is all groups.
        return array_merge($carry, $groupings[0]);
    }, []);

    return $vault->get_events(
        null,
        null,
        $timesortfrom,
        $timesortto,
        null,
        null,
        200,
        CALENDAR_EVENT_TYPE_ACTION,
        [$USER->id],
        $groupids ? $groupids : null,
        $courseids ? $courseids : null,
        $categoryids ? $categoryids : null, 
        true,
        true,
        function ($event) {
            return $event instanceof \core_calendar\local\event\entities\action_event_interface;
        }
    );

}



function block_semsort_get_courses_events($courses, $output) {
    global $CFG, $USER;

    require_once($CFG->dirroot . '/calendar/lib.php');
    require_once($CFG->dirroot . '/calendar/externallib.php');




    /*$allevents1 = \core_calendar\local\api::get_action_events_by_courses(
        $courses
    );*/

    $skipevents = intval(get_config('block_semsort', 'skipevents'));

    $timefrom = 0;
    if ($skipevents > 0) {
        $timefrom = time() - $skipevents * 31 * 24 * 60 * 60;
    }

    $vault = \core_calendar\local\event\container::get_event_vault();


    $courseids = array_keys($courses);
    $alleventsall = array(
        block_semsort_get_events($vault, $courseids, $timefrom, null),
        block_semsort_get_events($vault, $courseids, -1, 1),
    );

    $allevents = array();
    $foundevents = array();


    foreach ($alleventsall as $allevents2) {
        foreach ($allevents2 as $event) {
            $courseid = $event->get_course()->get('id');

            if (!isset($allevents[$courseid])) {
                $allevents[$courseid] = array();
            }
            $allevents[$courseid][] = $event;
            $foundevents[$event->get_id()] = 1;
        }
    }
/*
echo '<pre>';

    ksort($allevents);

    foreach ($allevents as $courseid => $events) {
        //echo 'COURSE: ' . $courseid . PHP_EOL;

        echo 'COURSE: ' . $courseid .  '   -   ' . count($events) . PHP_EOL;
        foreach ($events as $event) {
            echo "\t\t" . $event->get_id() . '  -  '. $event->get_times()->get_sort_time()->getTimestamp() . PHP_EOL;
        }
    }

    die;*/
    $exportercache = new \core_calendar\external\events_related_objects_cache($allevents, $courses);
    $exporter = new \core_calendar\external\events_grouped_by_course_exporter($allevents, ['cache' => $exportercache]);

    $exportedeventsraw = $exporter->export($output);
    $exportedevents = array();

    if (isset($exportedeventsraw->groupedbycourse)) {
        foreach ($exportedeventsraw->groupedbycourse as $courseevents) {
            $exportedevents[$courseevents->courseid] = $courseevents;
        }
    }
    return $exportedevents;
}

function block_semsort_migrate_admin_settings() {
    global $DB;
    $oldsettings = $DB->get_records('config_plugins', array('plugin' => 'block_semester_sortierung'));
    $settings = $DB->get_records('config_plugins', array('plugin' => 'block_semsort'), '', 'name, id, plugin, value');

    foreach ($oldsettings as $oldsetting) {
        if ($oldsetting->name == 'version') {
            continue;
        }

        if (isset($settings[$oldsetting->name])) {
            $settings[$oldsetting->name]->value = $oldsetting->value;
            $DB->update_record('config_plugins', $settings[$oldsetting->name]);
        } else {
            $oldsetting->plugin = 'block_semsort';
            unset($oldsetting->id);
            $DB->insert_record('config_plugins', $oldsetting);
        }
    }

    cache_helper::invalidate_by_definition('core', 'config', array(), 'block_semsort');
}

function block_semsort_migrate_default_dashboard() {
    global $DB;
    $instance = $DB->get_record('block_instances', array('blockname' => 'semester_sortierung', 'parentcontextid' => 1));
    if ($instance) {
        $instance->blockname = 'semsort';
        $DB->update_record('block_instances', $instance);
    }
}

function block_semsort_migrate_currentuser_dashboard() {
    global $DB, $USER;
    $context = \context_user::instance($USER->id);
    $instance = $DB->get_record('block_instances', array('blockname' => 'semester_sortierung', 'parentcontextid' => $context->id));
    if ($instance) {
        $instance->blockname = 'semsort';
        $DB->update_record('block_instances', $instance);
    }
}

function block_semsort_migrate_all_dashboards() {
    global $DB;

    $instances = $DB->get_records('block_instances', array('blockname' => 'semester_sortierung'));
    $count = 0;
    foreach ($instances as $instance) {
        if ($instance->parentcontextid == 1) {
            continue;
        }
        $instance->blockname = 'semsort';
        $DB->update_record('block_instances', $instance);
        $count++;
    }
    return $count;
}

function block_semsort_migrate_user_preferences($migrateall = false) {
    global $DB, $USER;
    // Move sorting preferences from user preferences to new table!
    $userfilter = array();
    if (!$migrateall) {
        $userfilter = array('userid' => $USER->id);
    }
    $userprefs = $DB->get_records('user_preferences', array('name' => 'semester_sortierung_sorting') + $userfilter);
    $counters = new stdClass;
    $counters->updated = 0;
    $counters->inserted = 0;
    $counters->unchanged = 0;
    $counters->userprefs = 0;
    foreach ($userprefs as $userpref) {
        $userid = $userpref->userid;
        $value = json_decode($userpref->value, true);
        foreach ($value as $semester => $courses) {
            $courses = implode(',', $courses);
            $currentprefs = block_semsort_get_usersort($userid);
            if (isset($currentprefs[$semester])) {
                if ($courses != $currentprefs[$semester]->courseorder) {
                    $currentprefs[$semester]->courseorder = $courses;
                    $currentprefs[$semester]->lastmodified = time();
                    $DB->update_record('block_semsort_usersort', $currentprefs[$semester]);
                    $counters->updated++;
                } else {
                    $counters->unchanged++;
                }
            } else {
                $newpref = new stdClass;
                $newpref->semester = $semester;
                $newpref->courseorder = $courses;
                $newpref->lastmodified = time();
                $newpref->userid = $userid;
                $DB->insert_record('block_semsort_usersort', $newpref);
                $counters->inserted++;
            }
        }
    }

    // Migrate the rest of the user preferences!
    $prefs = array(
        'semester_sortierung_courses' => 'semsort_courses',
        'semester_sortierung_semesters' => 'semsort_semesters',
        'semester_sortierung_favorites' => 'semsort_favorites'
    );
    foreach ($prefs as $oldname => $newname) {
        $olduserprefs = $DB->get_records('user_preferences',
            array('name' => $oldname) + $userfilter,
            '',
            'userid, name, value, id'
        );
        $newuserprefs = $DB->get_records('user_preferences',
            array('name' => $newname) + $userfilter,
            '',
            'userid, name, value, id'
        );
        foreach ($olduserprefs as $oldpref) {
            $counters->userprefs++;
            if (isset($newuserprefs[$oldpref->userid])) {
                $newuserprefs[$oldpref->userid]->value = $oldpref->value;
                $DB->update_record('user_preferences', $newuserprefs[$oldpref->userid]);
            } else {
                $userpref = new stdClass;
                $userpref->name = $newname;
                $userpref->value = $oldpref->value;
                $userpref->userid = $oldpref->userid;
                $DB->insert_record('user_preferences', $userpref);
            }
        }

    }

    return $counters;
}

function block_semsort_get_usersort($userid) {
    global $DB;
    return $DB->get_records('block_semsort_usersort', array('userid' => $userid),
                            '',
                            'semester, userid, id, courseorder, lastmodified');
}
