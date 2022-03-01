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

/**
 * Navigation hook to add to preferences page.
 *
 * @param navigation_node $useraccount
 * @param stdClass $user
 * @param context_user $context
 * @param stdClass $course
 * @param context_course $coursecontext
 */
function local_rocketchat_extend_navigation_user_settings(navigation_node $useraccount, stdClass $user, context_user $context,
        stdClass $course, context_course $coursecontext) {
    global $USER;

    $rocketchat = new \local_rocketchat\client();
    if ($rocketchat->authenticated && \local_rocketchat\utilities::is_external_connection_allowed()) {
        if (has_capability('local/rocketchat:linkaccount', $context) && $user->id == $USER->id) {
            $parent = $useraccount->parent->find('useraccount', navigation_node::TYPE_CONTAINER);
            $parent->add(get_string('linkaccount', 'local_rocketchat'), new moodle_url('/local/rocketchat/linkaccount.php'));
        }
    }
}
