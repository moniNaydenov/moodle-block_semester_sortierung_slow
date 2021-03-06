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

$ADMIN->add('blocksettings', new admin_category('blocksettingsemsortfolder', new lang_string('pluginname', 'block_semsort')));

if ($ADMIN->fulltree) {

    $configs = array();

    $configs[] = new admin_setting_configcheckbox('block_semsort/sortcourses',
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

    $configs[] = new admin_setting_configmulticheckbox('block_semsort/wintermonths',
        get_string('wintermonths', 'block_semsort'),
        get_string('monthsdesc', 'block_semsort'),
        $selected,
        $monthsarray);

    $configs[] = new admin_setting_configcheckbox('block_semsort/enablefavorites',
        get_string('enablefavorites', 'block_semsort'),
        get_string('enablefavoritesdesc', 'block_semsort'),
        '1');

    $configs[] = new admin_setting_configcheckbox('block_semsort/enablepersonalsort',
        get_string('enablepersonalsort', 'block_semsort'),
        get_string('enablepersonalsortdesc', 'block_semsort'),
        '1');

    $values = array();
    for ($i = 0; $i < 16; $i++) {
        $values[$i] = strval($i);
    }
    $values[0] = get_string('no');
    $configs[] = new admin_setting_configselect('block_semsort/archive',
        get_string('setting:archive', 'block_semsort'),
        get_string('setting:archivedesc', 'block_semsort', '...'),
        0,
        $values
    );
    $values[0] = get_string('showall', 'moodle', '');
    $configs[] = new admin_setting_configselect('block_semsort/autoclose',
        get_string('setting:autoclose', 'block_semsort'),
        get_string('setting:autoclosedesc', 'block_semsort'),
        0,
        $values
    );
    $values = array();
    for ($i = 0; $i <= 48; $i++) {
        $values[$i] = strval($i);
    }
    $values[0] = get_string('showall', 'moodle', '');

    $configs[] = new admin_setting_configselect('block_semsort/skipevents',
        get_string('setting:skipevents', 'block_semsort'),
        get_string('setting:skipeventsdesc', 'block_semsort'),
        0,
        $values
    );
    foreach ($configs as $config) {
        $config->plugin = 'block_semsort';
        $settings->add($config);
    }

}

if (file_exists($CFG->dirroot . '/blocks/semester_sortierung/version.php')) {
    $ADMIN->add('blocksettingsemsortfolder', $settings);
    $ADMIN->add('blocksettingsemsortfolder',
        new admin_externalpage(
            'blocksemsortmigrate',
            get_string('migrate_title', 'block_semsort'),
            $CFG->wwwroot.'/blocks/semsort/db/migrate.php'
        )
    );
    $ADMIN->add('blocksettingsemsortfolder',
        new admin_externalpage(
            'blocksemsortmigrate2',
            get_string('migrate2_title', 'block_semsort'),
            $CFG->wwwroot.'/blocks/semsort/db/migrate2.php'
        )
    );
    $settings = null; // Prevent duplicate settings page!
}