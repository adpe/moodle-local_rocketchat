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
 * User functions for Rocket.Chat API calls.
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat\integration;

use core_enrol_external;
use local_rocketchat\utilities;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');

class users {

    private $client;
    public $errors = [];

    public function __construct($client) {
        $this->client = $client;
    }

    /**
     * @param $rocketchatcourse
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public function create_users_for_course($rocketchatcourse) {
        $users = core_enrol_external::get_enrolled_users($rocketchatcourse->course);
        $users = json_decode(json_encode($users), false);

        foreach ($users as $user) {
            if ($this->user_exists($user)) {
                continue;
            }

            $this->create_user($user);
        }
    }

    /**
     * @param $user
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function create_user($user) {
        $api = '/api/v1/users.create';

        $data = [
                'name' => $user->firstname . ' ' . $user->lastname,
                'username' => explode('@', $user->email)[0],
                'email' => $user->email,
                'verified' => true,
                'password' => substr(str_shuffle(md5(microtime())), 0, 6),
                'joinDefaultChannels' => false
        ];

        $header = $this->client->authentication_headers();
        $header[] = $this->client->contenttype_headers();

        $response = utilities::make_request($this->client->url, $api, 'post', $data, $header);

        if (!$response->success) {
            $object = new \stdClass();
            $object->code = get_string('user_creation', 'local_rocketchat');
            $object->error = '[ user_id - ' . $user->id . ' | email - ' . $user->email . ']' . $response->error;

            $this->errors[] = $object;
        }
    }

    /**
     * @param $user
     * @return bool
     * @throws \dml_exception
     */
    public function user_exists($user) {
        foreach ($this->get_existing_users() as $existinguser) {
            $username = $user->username;

            if (count(explode('@', $user->email)) > 1) {
                $username = explode('@', $user->email)[0];
            }

            if ($username == $existinguser->username) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed
     * @throws \dml_exception
     */
    private function get_existing_users() {
        $api = '/api/v1/users.list';

        $header = $this->client->authentication_headers();

        $response = utilities::make_request($this->client->url, $api, 'get', null, $header);

        return $response->users;
    }

    /**
     * @param $user
     * @return bool
     * @throws \dml_exception
     */
    public function get_user($user) {
        $username = $user->username;

        if (count(explode('@', $user->email)) > 1) {
            $username = explode('@', $user->email)[0];
        }

        $api = '/api/v1/users.info?username=' . $username;

        $header = $this->client->authentication_headers();

        $response = utilities::make_request($this->client->url, $api, 'get', null, $header);

        if ($response->success) {
            return $response->user->_id;
        }

        return false;
    }

    /**
     * @param $userenrolmentid
     * @throws \dml_exception
     */
    public function update_user_activity($userenrolmentid) {
        global $DB;

        $userenrolment = $DB->get_record('user_enrolments', ['id' => $userenrolmentid]);

        $user = $DB->get_record('user', ['id' => $userenrolment->userid]);

        $isactive = false;
        if ($userenrolment->status !== '1') {
            $isactive = true;
        }

        $rocketchatuser = $this->get_user($user);

        if ($rocketchatuser) {
            $api = '/api/v1/users.update';

            $data = [
                    'userId' => $rocketchatuser->_id,
                    'active' => $isactive
            ];

            $header = $this->client->authentication_headers();
            $header[] = $this->client->contenttype_headers();

            utilities::make_request($this->client->url, $api, 'post', $data, $header);
        }
    }
}
