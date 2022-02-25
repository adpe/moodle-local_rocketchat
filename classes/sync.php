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
 * Sync functions for Rocket.Chat integration.
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

class sync {

    private $client;
    private $errors = [];

    /**
     * sync constructor.
     *
     * @throws \dml_exception
     */
    public function __construct() {
        $this->client = new client();
    }

    private function reset_errors() {
        $this->errors = [];
    }

    /**
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public function sync_pending_courses() {
        global $DB;

        $rocketchatcourses = $DB->get_records('local_rocketchat_courses', ['pendingsync' => true]);
        foreach ($rocketchatcourses as $rocketchatcourse) {
            $this->sync_pending_course($rocketchatcourse->course);
        }
    }

    /**
     * @param $courseid
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public function sync_pending_course($courseid) {
        global $DB;

        if (!$rocketchatcourse = $DB->get_record('local_rocketchat_courses', ['course' => $courseid])) {
            $rocketchatcourseid = $this->create_rocketchat_course($courseid);
            $rocketchatcourse = $DB->get_record('local_rocketchat_courses', ['id' => $rocketchatcourseid]);
        }

        $this->run_sync($rocketchatcourse);
        $this->record_result($rocketchatcourse);
    }

    /**
     * @param $courseid
     * @return bool
     * @throws \dml_exception
     */
    public static function is_event_based_sync_on_course($courseid) {
        global $DB;

        $rocketchatcourse = $DB->get_record('local_rocketchat_courses', ['course' => $courseid]);

        return $rocketchatcourse ? $rocketchatcourse->eventbasedsync : false;
    }

    /**
     * @param $courseid
     * @return bool|int
     * @throws \dml_exception
     */
    private function create_rocketchat_course($courseid) {
        global $DB;

        $rocketchatcourse = new \stdClass();
        $rocketchatcourse->course = $courseid;
        $rocketchatcourse->pendingsync = true;
        $rocketchatcourseid = $DB->insert_record('local_rocketchat_courses', $rocketchatcourse);

        return $rocketchatcourseid;
    }

    /**
     * @param $rocketchatcourse
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    private function run_sync($rocketchatcourse) {
        global $DB;

        if (!$this->client->authenticated) {
            $object = [];
            $object->code = get_string('auth_failure', 'local_rocketchat');
            $object->error = get_string('connection_failure', 'local_rocketchat');

            array_push($this->errors, $object);

            return false;
        }

        $course = $DB->get_record('course', ['id' => $rocketchatcourse->course]);

        $channelapi = new integration\channels($this->client);
        $channelapi->create_channels_for_course($rocketchatcourse);
        $this->errors = array_merge($this->errors, $channelapi->errors);

        $userapi = new integration\users($this->client);
        $userapi->create_users_for_course($rocketchatcourse);
        $this->errors = array_merge($this->errors, $userapi->errors);

        $subscriptionapi = new integration\subscriptions($this->client);
        $subscriptionapi->add_subscriptions_for_course($course);
        $this->errors = array_merge($this->errors, $subscriptionapi->errors);
    }

    /**
     * @param $rocketchatcourse
     * @throws \dml_exception
     */
    private function record_result($rocketchatcourse) {
        if (count($this->errors) == 0) {
            $this->pass_sync($rocketchatcourse);
        } else {
            $this->fail_sync($rocketchatcourse);
        }

        $this->reset_errors();
    }

    /**
     * @param $rocketchatcourse
     * @throws \dml_exception
     */
    private function pass_sync($rocketchatcourse) {
        global $DB;

        $rocketchatcourse->pendingsync = 0;
        $rocketchatcourse->lastsync = time();
        $rocketchatcourse->error = null;

        $DB->update_record('local_rocketchat_courses', $rocketchatcourse);
    }

    /**
     * @param $rocketchatcourse
     * @throws \dml_exception
     */
    private function fail_sync($rocketchatcourse) {
        global $DB;

        $errorstring = '';
        foreach ($this->errors as $error) {
            $errorstring = $errorstring . '[' . $error->code  . '] ' . $error->error . "\r\n";
        }

        $rocketchatcourse->pendingsync = 0;
        $rocketchatcourse->lastsync = time();
        $rocketchatcourse->error = $errorstring;

        $DB->update_record('local_rocketchat_courses', $rocketchatcourse);
    }
}
