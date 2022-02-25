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
 * Privacy Subsystem implementation for local_rocketchat.
 *
 * @package    local_rocketchat
 * @copyright  2021 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\user_preference_provider;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for the local_rocketchat implementation of the privacy API.
 *
 * @package    local_rocketchat
 * @copyright  2021 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, user_preference_provider {
    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The array of metadata.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference('local_rocketchat_external_user',
                'privacy:metadata:preference:local_rocketchat_external_user');
        $collection->add_user_preference('local_rocketchat_external_token',
                'privacy:metadata:preference:local_rocketchat_external_token');

        $collection->link_external_location('local_rocketchat', [
                'apiusername' => 'privacy:metadata:local_rocketchat_api:username',
                'apipassword' => 'privacy:metadata:local_rocketchat_api:password',
                'userusername' => 'privacy:metadata:local_rocketchat_user:username',
                'userpassword' => 'privacy:metadata:local_rocketchat_user:password',
        ], 'privacy:metadata:local_rocketchat');

        return $collection;
    }

    /**
     * Export all user preferences of the linked Rocket.Chat user account.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @throws \coding_exception
     */
    public static function export_user_preferences(int $userid) {
        $preference = get_user_preferences('local_rocketchat_external_user', null, $userid);
        if (isset($preference)) {
            writer::export_user_preference('local_rocketchat', 'local_rocketchat_external_user',
                    $preference,
                    get_string('privacy:metadata:preference:local_rocketchat_external_user', 'local_rocketchat')
            );
        }

        $preference = get_user_preferences('local_rocketchat_external_token', null, $userid);
        if (isset($preference)) {
            writer::export_user_preference('local_rocketchat', 'local_rocketchat_external_token',
                    $preference,
                    get_string('privacy:metadata:preference:local_rocketchat_external_token', 'local_rocketchat')
            );
        }
    }
}
