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
 * Defines various library functions.
 *
 * @package     local_rocketchat
 * @copyright   2021 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_rocketchat\utilities;

/**
 * Navigation hook to add to preferences page.
 *
 * @param navigation_node $useraccount
 * @param stdClass $user
 * @param context_user $context
 * @param stdClass $course
 * @param context_course $coursecontext
 * @throws coding_exception
 * @throws dml_exception
 */
function local_rocketchat_extend_navigation_user_settings(
    navigation_node $useraccount,
    stdClass $user,
    context_user $context,
    stdClass $course,
    context_course $coursecontext
): void {
    global $USER;

    if (!utilities::is_external_connection_allowed()) {
        return;
    }

    if (has_capability('local/rocketchat:linkaccount', $context) && $user->id == $USER->id) {
        $parent = $useraccount->parent->find('useraccount', navigation_node::TYPE_CONTAINER);
        $parent->add(get_string('linkaccount', 'local_rocketchat'), new moodle_url('/local/rocketchat/linkaccount.php'));
    }
}
