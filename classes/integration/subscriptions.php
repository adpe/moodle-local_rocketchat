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
 * Subscriptions/Groups functions for Rocket.Chat API calls.
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat\integration;

use local_rocketchat\client;
use local_rocketchat\utilities;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/grouplib.php');

/**
 * Class with subscriptions helper methods.
 */
class subscriptions {

    /**
     * The API client.
     *
     * @var client
     */
    private $client;

    /**
     * Holds the errors.
     *
     * @var array
     */
    public $errors = [];

    /**
     * The channels API client.
     *
     * @var channels
     */
    private $channelapi;

    /**
     * The users API clinet.
     *
     * @var users
     */
    private $userapi;

    /**
     * Constructor.
     *
     * @param $client
     */
    public function __construct($client) {
        $this->client = $client;
        $this->channelapi = new channels($this->client);
        $this->userapi = new users($this->client);
    }

    /**
     * Add subscription for a single course.
     *
     * @param $course
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function add_subscriptions_for_course($course) {
        global $DB;

        $groups = $DB->get_records('groups', ["courseid" => $course->id]);

        foreach ($groups as $group) {
            $this->add_subscriptions_for_group($group);
        }
    }

    /**
     * Add subscription for a single group.
     *
     * @param $group
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function add_subscriptions_for_group($group) {
        $users = groups_get_members($group->id);
        $users = json_decode(json_encode($users), false);

        foreach ($users as $user) {
            $this->add_subscription_for_user($user, $group);
        }
    }

    /**
     * Add subscription for a single user
     *
     * @param $user
     * @param $group
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function add_subscription_for_user($user, $group) {
        $rocketchatchannel = $this->channelapi->has_channel_for_group($group);
        $rocketchatuser = $this->userapi->get_user($user);

        $subscription = $this->has_subscription($rocketchatchannel, $rocketchatuser);

        if ($rocketchatuser && !$subscription && $rocketchatchannel) {
            $api = '/api/v1/groups.invite';

            $data = [
                    'roomId' => $rocketchatchannel,
                    'userId' => $rocketchatuser,
            ];

            $header = $this->client->authentication_headers();
            $header[] = $this->client->contenttype_headers();

            $response = utilities::make_request($this->client->url, $api, 'post', $data, $header);

            if (!$response->success) {
                $object = new \stdClass();
                $object->code = get_string('subscription_creation', 'local_rocketchat');
                $object->error = $response->error;

                $this->errors[] = $object;
            }
        }
    }

    /**
     * Remove subscription for a single user.
     *
     * @param $user
     * @param $group
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function remove_subscription_for_user($user, $group) {
        $rocketchatchannel = $this->channelapi->has_channel_for_group($group);
        $rocketchatuser = $this->userapi->get_user($user);

        $subscription = $this->has_subscription($rocketchatchannel, $rocketchatuser);

        if ($rocketchatuser && $subscription && $rocketchatchannel) {
            $api = '/api/v1/groups.kick';

            $data = [
                    "roomId" => $rocketchatchannel,
                    "userId" => $rocketchatuser,
            ];

            $header = $this->client->authentication_headers();
            $header[] = $this->client->contenttype_headers();

            $response = utilities::make_request($this->client->url, $api, 'post', $data, $header);

            if (!$response->success) {
                $object = new \stdClass();
                $object->code = get_string('subscription_creation', 'local_rocketchat');
                $object->error = $response->error;

                $this->errors[] = $object;
            }
        }
    }

    /**
     * Check if user has a subscription in a channel.
     *
     * @param $rocketchatchannel
     * @param $rocketchatuser
     * @return bool
     * @throws \dml_exception
     */
    public function has_subscription($rocketchatchannel, $rocketchatuser) {

        if ($rocketchatchannel && $rocketchatuser) {
            $api = '/api/v1/groups.counters?roomId=' . $rocketchatchannel . '&userId=' . $rocketchatuser;

            $header = $this->client->authentication_headers();

            $response = utilities::make_request($this->client->url, $api, 'get', null, $header);

            if ($response->success) {
                return $response->joined;
            }
        }

        return false;
    }
}
