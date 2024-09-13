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
 * Channels functions for Rocket.Chat API calls.
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
require_once($CFG->dirroot . '/group/externallib.php');

/**
 * Class with channels helper methods.
 */
class channels {

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
     * Constructor.
     *
     * @param $client
     */
    public function __construct($client) {
        $this->client = $client;
    }

    /**
     * Create channels for a single course.
     *
     * @param $rocketchatcourse
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function create_channels_for_course($rocketchatcourse) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $rocketchatcourse->course]);
        $groups = $DB->get_records('groups', ['courseid' => $course->id]);

        foreach ($groups as $group) {
            if (!$this->group_requires_rocketchat_channel($group)) {
                continue;
            }

            $channelname = $this->get_formatted_channel_name($course->shortname, $group->name);

            $this->create($channelname);
        }
    }

    /**
     * Check if channel exists for a group.
     *
     * @param $group
     * @return bool
     * @throws \dml_exception
     */
    public function has_channel_for_group($group) {
        global $DB;

        $course = $DB->get_record('course', ['id' => $group->courseid]);
        $channelname = $this->get_formatted_channel_name($course->shortname, $group->name);

        return $this->has_private_group($channelname);
    }

    /**
     * Check if group has a private channel.
     *
     * @param $name
     * @return bool
     * @throws \dml_exception
     */
    public function has_private_group($name) {
        $api = '/api/v1/groups.info?roomName=' . $name;

        $header = $this->client->authentication_headers();

        $response = utilities::make_request($this->client->url, $api, 'get', null, $header);

        if ($response->success) {
            return $response->group->_id;
        }

        return false;
    }

    /**
     * Create a channel.
     *
     * @param $channel
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function create($channel) {
        if (!$this->channel_exists($channel)) {
            $this->create_channel($channel);
        }
    }

    /**
     * Check if channel exists.
     *
     * @param $channelname
     * @return bool
     * @throws \dml_exception
     */
    private function channel_exists($channelname) {
        foreach ($this->get_existing_channels() as $channel) {
            if ($channel->name == $channelname) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get existing channel.
     *
     * @return mixed
     * @throws \dml_exception
     */
    private function get_existing_channels() {
        $api = '/api/v1/rooms.get';

        $header = $this->client->authentication_headers();
        $header[] = $this->client->contenttype_headers();

        $response = utilities::make_request($this->client->url, $api, 'get', null, $header);

        return $response->update;
    }

    /**
     * Create channel.
     *
     * @param $channel
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function create_channel($channel) {
        $api = '/api/v1/channels.create';

        $data = [
                'name' => $channel,
        ];

        $header = $this->client->authentication_headers();
        $header[] = $this->client->contenttype_headers();

        $response = utilities::make_request($this->client->url, $api, 'post', $data, $header);

        if (!$response->success) {
            $object = new \stdClass();
            $object->code = get_string('channel_creation', 'local_rocketchat');
            $object->error = $response->error;

            $this->errors[] = $object;

            return;
        }

        $api = '/api/v1/channels.setType';

        $data = [
                'roomId' => $response->channel->_id,
                'type' => 'p',
        ];

        $response = utilities::make_request($this->client->url, $api, 'post', $data, $header);

        if (!$response->success) {
            $object = new \stdClass();
            $object->code = get_string('channel_creation', 'local_rocketchat');
            $object->error = $response->error;

            $this->errors[] = $object;
        }
    }

    /**
     * Check if group needs a channel.
     *
     * @param $group
     * @return bool
     * @throws \dml_exception
     */
    private function group_requires_rocketchat_channel($group) {
        $groupregextext = get_config('local_rocketchat', 'groupregex');
        $groupregexs = explode("\r\n", $groupregextext);

        foreach ($groupregexs as $regex) {
            if (preg_match($regex, $group->name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get formatted channel name by group name.
     *
     * @param $courseshortname
     * @param $groupname
     * @return string
     */
    private function get_formatted_channel_name($courseshortname, $groupname): string {
        return str_replace(' ', '_', $courseshortname . '-' . $groupname);
    }
}
