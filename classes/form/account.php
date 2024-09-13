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
 * Form class for linkaccount.php
 *
 * @package     local_rocketchat
 * @copyright   2021 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use coding_exception;
use html_writer;
use local_rocketchat\client;
use moodleform;

/**
 * Form to edit the account details.
 *
 */
class account extends moodleform {
    /**
     * Defines the form
     *
     * @throws coding_exception
     */
    public function definition(): void {
        global $USER;

        $linked = $this->_customdata['linked'];
        $rocketchat = new client();

        $mform = $this->_form;
        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('static', 'url', get_string('url'), $rocketchat->get_instance_url());

        $status = html_writer::tag(
            'span',
            get_string('notconnected', 'badges'),
            ['class' => 'notconnected', 'id' => 'connection-status']
        );
        if ($linked) {
            $status = html_writer::tag(
                'span',
                get_string('connected', 'badges'),
                ['class' => 'connected', 'id' => 'connection-status']
            );
        }

        $mform->addElement('static', 'status', get_string('status'), $status);

        if ($linked) {
            $mform->addElement('static', 'email', get_string('usernameemail'), $this->_customdata['email']);
            $mform->addElement('submit', 'disconnect', get_string('disconnect', 'badges'));
        } else {
            $this->add_auth_fields($USER->email);
            $this->add_action_buttons(false, get_string('connect', 'badges'));
        }
    }

    /**
     * Validates form data
     *
     * @param array $data
     * @param array $files
     * @return array
     * @throws coding_exception
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $rocketchat = new client();

        $result = $rocketchat->authenticate($data['email'], $data['password']);
        if ($result === false || !empty($result->error)) {
            $errors['email'] = get_string('linkaccount_unexpectedresult', 'local_rocketchat');

            if (isset($result->message)) {
                $msg = $result->message;
            } else {
                $msg = $result->error;
            }

            if (!empty($msg)) {
                $errors['email'] .= get_string('linkaccount_unexpectedmessage', 'local_rocketchat', $msg);
            }
        }

        return $errors;
    }

    /**
     * Add Rocket.Chat specific auth details.
     *
     * @param string $email Use users email address from Moodle as placeholder.
     * @throws coding_exception
     */
    protected function add_auth_fields(string $email): void {
        $mform = $this->_form;

        $mform->addElement('text', 'email', get_string('usernameemail'));
        $mform->addRule('email', null, 'required');
        $mform->setType('email', PARAM_TEXT);
        $mform->setDefault('email', $email);

        $mform->addElement('passwordunmask', 'password', get_string('password'));
        $mform->addRule('password', null, 'required');
        $mform->setType('password', PARAM_RAW);
    }
}
