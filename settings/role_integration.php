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
 * Site administration page for Role Integration in Rocket.Chat
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_rocketchat_role_integration');

$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_rocketchat/rocketchat', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('heading_role', 'local_rocketchat'));
echo html_writer::tag('p', get_string('role_desc', 'local_rocketchat'));

echo html_writer::start_tag('table', ['class' => 'admintable generaltable', 'id' => 'integrated-roles']);
echo html_writer::start_tag('thead');
echo html_writer::tag('th', get_string('roletable_column_1', 'local_rocketchat'));
echo html_writer::tag('th', get_string('roletable_column_2', 'local_rocketchat'));
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');

// Get all roles and list in table.
$rocketchatroles = \local_rocketchat\utilities::get_roles();
$roles = role_fix_names(get_all_roles(), context_system::instance(), ROLENAME_ORIGINAL);

foreach ($roles as $role) {
    foreach ($rocketchatroles as $rocketchatrole) {
        if ($role->id == $rocketchatrole->roleid) {
            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', $role->localname);

            echo html_writer::start_tag('td');
            echo html_writer::checkbox(
                'requiresync',
                null,
                $rocketchatrole->requiresync,
                '',
                ['data-roleid' => $role->id]
            );
            echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
        }
    }
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// Show some additional information and hints.
echo html_writer::start_tag('div', ["class" => 'alert alert-info']);
echo html_writer::start_tag('ul', ["style" => "margin-top: 1rem"]);
echo html_writer::tag('li', get_string('roleinfo_1', 'local_rocketchat'));
echo html_writer::end_tag('ul');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
