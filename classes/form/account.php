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

use html_writer;
use moodleform;
use stdClass;

/**
 * Form to edit backpack initial details.
 *
 */
class account extends moodleform {

    /**
     * Defines the form
     */
    public function definition() {
        global $USER;

        $linked = $this->_customdata['linked'];
        $rocketchat = new \local_rocketchat\client();

        $mform = $this->_form;
        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('static', 'url', get_string('url'), $rocketchat->get_instance_url());

        $status = html_writer::tag('span', get_string('notconnected', 'badges'),
                ['class' => 'notconnected', 'id' => 'connection-status']);
        if ($linked) {
            $status = html_writer::tag('span', get_string('connected', 'badges'),
                    ['class' => 'connected', 'id' => 'connection-status']);
        }

        $mform->addElement('static', 'status', get_string('status'), $status);

        if ($linked) {
            $mform->addElement('static', 'email', get_string('email'), $this->_customdata['email']);
            $mform->addElement('submit', 'disconnect', get_string('disconnect', 'badges'));
        } else {
            $this->add_auth_fields($USER->email);
            $this->add_action_buttons(false, get_string('connect', 'badges'));
        }
    }

    /**
     * Validates form data
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $rocketchat = new \local_rocketchat\client();

        $result = $rocketchat->authenticate($data['email'], $data['password']);
        if ($result === false || !empty($result->error)) {
            $errors['email'] = get_string('linkaccount_unexpectedresult', 'local_rocketchat');

            $msg = $result->message;
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
     */
    protected function add_auth_fields(string $email) {
        $mform = $this->_form;

        $mform->addElement('text', 'email', get_string('email'));
        $mform->addRule('email', null, 'required');
        $mform->setType('email', PARAM_EMAIL);
        $mform->setDefault('email', $email);

        $mform->addElement('passwordunmask', 'password', get_string('password'));
        $mform->addRule('password', null, 'required');
        $mform->setType('password', PARAM_RAW);
    }
}
