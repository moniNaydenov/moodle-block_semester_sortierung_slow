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
 * Migration script from deprecated block
 *
 * @package   block_semsort
 * @author    Simeon Naydenov (moniNaydenov@gmail.com)
 * @author    Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../locallib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();

if (!is_siteadmin()) {
    redirect(new moodle_url('/my'), get_string('adminonly', 'badges'));
}

class migrateform extends moodleform {
    protected function definition() {
        $this->add_action_buttons(false, get_string('migrate', 'block_semsort'));
    }
}

$context = context_system::instance();
$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/blocks/semsort/db/migrate.php'));

$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('migrate_title', 'block_semsort'));
$PAGE->set_title(get_string('migrate_title', 'block_semsort'));

$migrateform = new migrateform();

$counts = false;
if ($migrateform->is_submitted()) {
    $counts = block_semsort_migrate_user_preferences();
    redirect($PAGE->url, get_string('migrateresults', 'block_semsort', $counts));
    die;
}

echo $OUTPUT->header();

echo $migrateform->render();
echo $OUTPUT->footer();