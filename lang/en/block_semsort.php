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
 * English language file
 *
 * @package       block_semsort
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Semester overview';
$string['sortcourse'] = 'Sort courses by semester';
$string['sortcoursedesc'] = 'Instance-wide on/off';
$string['wintermonths'] = 'Months of the winter semester';
$string['monthsdesc'] = 'Not marked months = Months of the summer semester. Months January - June still count for the winter semester from the previous year';
$string['summersem'] = 'Summer term';
$string['wintersem'] = 'Winter term';

$string['favorites'] = 'My favorites';
$string['addtofavorites'] = 'Add to favorites';
$string['removefromfavorites'] = 'Remove from favorites';
$string['enablefavorites'] = 'Show Favorites';
$string['enablefavoritesdesc'] = 'Show a separate section for courses chosen as favorites';
$string['enablepersonalsort'] = 'Enable personal sort';
$string['enablepersonalsortdesc'] = 'Enable the user to sort the courses according to his personal wish';
$string['semsort:addinstance'] = 'Add a new Semester overview block';
$string['semsort:myaddinstance'] = 'Add a new Semester overview block to My home';

$string['migrate_title'] = 'Migrate settings from deprecated semester_sortierung block';
$string['migrate'] = 'Migrate settings';
$string['migrate:results'] = '<strong>Migration complete:</strong><br />';
$string['migrate:defaultdashboard'] = 'Replace block_semestersortierung with block_semsort on default dashboardpage';
$string['migrate:alldashboards'] = 'Replace block_semestersortierung with block_semsort on all users dashboardpages';
$string['migrate:usersettings'] = 'Migrate usersettings (favorite, private order) from block_semestersortierung to block_semsort';
$string['migrate:adminsettings'] = 'Migrate adminsettings from block_semestersortierung to block_semsort';
$string['migrate:defaultdashboard:success'] = 'Default Dashboard updated';
$string['migrate:alldashboards:success'] = 'All users\' Dashboard pages updated';
$string['migrate:usersettings:success'] = 'User settings: <p><br />Updated records: {$a->updated}<br />Newly inserted records: {$a->inserted}<br />Unchanged records: {$a->unchanged}</p>';
$string['migrate:adminsettings:success'] = 'Admin settings migrated';
$string['migrateone'] = 'Migrate for current user only';
$string['migrateall'] = 'Migrate for all users';