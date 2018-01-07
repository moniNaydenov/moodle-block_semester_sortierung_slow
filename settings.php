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
 * Settings page
 *
 * @package       block_semsort
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $configs = array();

    $configs[] = new admin_setting_configcheckbox('sortcourses',
        get_string('sortcourse', 'block_semsort'),
        get_string('sortcoursedesc', 'block_semsort'), '1');

    // Setting for configuring which months belong to the winter semester..
    // 12 checkboxes for each month; jan, july-dec should be checked by default.
    $monthsarray = array();
    $selected = array();
    for ($i = 1; $i <= 12; $i++) {
        $monthsarray['mon' . ($i < 10 ? '0' : '') . strval($i)] = userdate(mktime(1, 0, 0, $i, 1, 2016),
                                                                          '%B');
        if ($i < 2 || $i > 6) {
            $selected['mon' . ($i < 10 ? '0' : '') .  strval($i)] = 1;
        }
    }

    $configs[] = new admin_setting_configmulticheckbox('wintermonths',
        get_string('wintermonths', 'block_semsort'),
        get_string('monthsdesc', 'block_semsort'),
        $selected,
        $monthsarray);

    $configs[] = new admin_setting_configcheckbox('enablefavorites',
        get_string('enablefavorites', 'block_semsort'),
        get_string('enablefavoritesdesc', 'block_semsort'),
        '1');

    $configs[] = new admin_setting_configcheckbox('enablepersonalsort',
        get_string('enablepersonalsort', 'block_semsort'),
        get_string('enablepersonalsortdesc', 'block_semsort'),
        '1');

    $values = array();
    for ($i = 0; $i < 16; $i++) {
        $values[$i] = strval($i) ;
    }
    $values[0] = get_string('no');
    $configs[] = new admin_setting_configselect('archive',
        get_string('setting:archive', 'block_semsort'),
        get_string('setting:archivedesc', 'block_semsort', '...'),
        0,
        $values
    );
    foreach ($configs as $config) {
        $config->plugin = 'block_semsort';
        $settings->add($config);
    }

}

if (file_exists($CFG->dirroot . '/blocks/semester_sortierung/version.php')) {
    $ADMIN->add('blocksettings', new admin_externalpage('blocksemsortmigrate', get_string('migrate_title', 'block_semsort'),
        $CFG->wwwroot.'/blocks/semsort/db/migrate.php'));
}
