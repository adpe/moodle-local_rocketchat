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

use coding_exception;
use dml_exception;
use local_rocketchat\client;
use local_rocketchat\utilities;
use stdClass;

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
    private client $client;

    /**
     * Holds the errors.
     *
     * @var array
     */
    public array $errors = [];

    /**
     * Constructor.
     *
     * @param client $client
     */
    public function __construct(client $client) {
        $this->client = $client;
    }

    /**
     * Create channels for a single course.
     *
     * @param mixed $rocketchatcourse
     * @throws dml_exception
     * @throws coding_exception
     */
    public function create_channels_for_course(mixed $rocketchatcourse): void {
        global $DB;

        $course = $DB->get_record('course', ['id' => $rocketchatcourse->course]);
        $groups = $DB->get_records('groups', ['courseid' => $course->id]);

        foreach ($groups as $group) {
            if (!$this->group_requires_rocketchat_channel($group->name)) {
                continue;
            }

            $channelname = $this->get_formatted_channel_name($course->shortname, $group->name);

            $this->create($channelname);
        }
    }

    /**
     * Check if channel exists for a group.
     *
     * @param mixed $group
     * @return bool
     * @throws dml_exception
     */
    public function has_channel_for_group(mixed $group): mixed {
        global $DB;

        $course = $DB->get_record('course', ['id' => $group->courseid]);
        $channelname = $this->get_formatted_channel_name($course->shortname, $group->name);

        return $this->has_private_group($channelname);
    }

    /**
     * Check if group has a private channel.
     *
     * @param string $name
     * @return mixed
     */
    public function has_private_group(string $name): mixed {
        $api = '/api/v1/groups.info?roomName=' . $name;

        $header = $this->client->authentication_headers();

        $response = utilities::make_request($this->client->url, $api, 'get', [], $header);

        if ($response->success) {
            return $response->group->_id;
        }

        return false;
    }

    /**
     * Create a channel.
     *
     * @param string $channel
     * @throws coding_exception
     * @throws dml_exception
     */
    private function create(string $channel): void {
        if (!$this->channel_exists($channel)) {
            $this->create_channel($channel);
        }
    }

    /**
     * Check if channel exists.
     *
     * @param string $channelname
     * @return bool
     * @throws dml_exception
     */
    private function channel_exists(string $channelname): bool {
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
     */
    private function get_existing_channels(): mixed {
        $api = '/api/v1/rooms.get';

        $header = $this->client->authentication_headers();
        $header[] = $this->client->contenttype_headers();

        $response = utilities::make_request($this->client->url, $api, 'get', [], $header);

        return $response->update;
    }

    /**
     * Create channel.
     *
     * @param string $channel
     * @throws coding_exception
     */
    private function create_channel(string $channel): void {
        $api = '/api/v1/channels.create';

        $data = [
                'name' => $channel,
        ];

        $header = $this->client->authentication_headers();
        $header[] = $this->client->contenttype_headers();

        $response = utilities::make_request($this->client->url, $api, 'post', $data, $header);

        if (!$response->success) {
            $object = new stdClass();
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
            $object = new stdClass();
            $object->code = get_string('channel_creation', 'local_rocketchat');
            $object->error = $response->error;

            $this->errors[] = $object;
        }
    }

    /**
     * Check if group needs a channel.
     *
     * @param string $groupname
     * @return bool
     * @throws dml_exception
     */
    private function group_requires_rocketchat_channel(string $groupname): bool {
        $groupregextext = get_config('local_rocketchat', 'groupregex');
        $groupregexs = explode("\r\n", $groupregextext);

        foreach ($groupregexs as $regex) {
            if (preg_match($regex, $groupname)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get formatted channel name by group name.
     *
     * @param string $courseshortname
     * @param string $groupname
     * @return string
     */
    private function get_formatted_channel_name(string $courseshortname, string $groupname): string {
        return str_replace(' ', '_', $courseshortname . '-' . $groupname);
    }
}
