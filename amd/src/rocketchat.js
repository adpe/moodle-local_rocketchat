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
 * Javascript to initialise the Rocket.Chat courses- and role integration settings.
 *
 * @module      local_rocketchat/rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2021 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Ajax from 'core/ajax';

const setRocketChatCourseSync = (courseid, pendingsync) => {
    Ajax.call([
        {
            methodname: 'local_rocketchat_set_rocketchat_course_sync',
            args: {
                courseid: courseid,
                pendingsync: pendingsync
            }
        }
    ]);
};

const setRocketChatEventBasedSync = (courseid, eventbasedsync) => {
    Ajax.call([
        {
            methodname: 'local_rocketchat_set_rocketchat_event_based_sync',
            args: {
                courseid: courseid,
                eventbasedsync: eventbasedsync
            }
        }
    ]);
};

const setRocketChatManuallyTriggerSync = (courseid) => {
    Ajax.call([
        {
            methodname: 'local_rocketchat_manually_trigger_sync',
            args: {
                courseid: courseid
            }
        }
    ]);

    location.reload();
};

const setRocketChatRoleSync = (roleid, requiresync) => {
    Ajax.call([
        {
            methodname: 'local_rocketchat_set_rocketchat_role_sync',
            args: {
                roleid: roleid,
                requiresync: requiresync
            }
        }
    ]);
};

export const init = () => {
    const courses = $('#integrated-courses');
    const roles = $('#integrated-roles');

    courses.on('click', 'input[name="pendingsync"]', function() {
        setRocketChatCourseSync($(this).data('courseid'), $(this).is(":checked"));
    });

    courses.on('click', 'input[name="eventbasedsync"]', function() {
        setRocketChatEventBasedSync($(this).data('courseid'), $(this).is(":checked"));
    });

    courses.on('click', 'button', function(e) {
        e.preventDefault();

        $(this).text("Syncing ...");
        $(this).prop("disabled", "disabled");

        setRocketChatManuallyTriggerSync($(this).data('courseid'), this);
    });

    roles.on('click', 'input', function() {
        setRocketChatRoleSync($(this).data('roleid'), $(this).is(":checked"));
    });
};