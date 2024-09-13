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
 * Observer to add subscription for Rocket.Chat integration.
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat\events\observers;

use coding_exception;
use dml_exception;
use local_rocketchat\client;
use local_rocketchat\integration\subscriptions;
use local_rocketchat\integration\sync;
use local_rocketchat\utilities;
use ReflectionException;

/**
 * Handles when group member is added.
 */
class group_member_added {
    /**
     * Main method call.
     *
     * @param \core\event\group_member_added $event
     * @throws ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function call(\core\event\group_member_added $event): void {
        $data = utilities::access_protected($event, 'data');

        if (sync::is_event_based_sync_on_course($data['courseid'])) {
            self::add_subscription($data);
        }
    }

    /**
     * Add user to channel.
     *
     * @param array $data
     * @throws coding_exception
     * @throws dml_exception
     */
    private static function add_subscription(array $data): void {
        $client = new client();

        if (!$client->authenticated) {
            return;
        }

         [$user, $group] = utilities::get_user_and_group_by_event_data($data);

        $subscriptionapi = new subscriptions($client);
        $subscriptionapi->add_subscription_for_user($user, $group);
    }
}
