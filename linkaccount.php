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
 * Rocket.Chat user preference.
 *
 * @package     local_rocketchat
 * @copyright   2021 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use local_rocketchat\client;
use local_rocketchat\form\account;
use local_rocketchat\utilities;

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_user::instance($USER->id);
require_capability('local/rocketchat:linkaccount', $context);

$disconnect = optional_param('disconnect', false, PARAM_BOOL);

if (!utilities::is_external_connection_allowed()) {
    redirect($CFG->wwwroot);
}

$url = new moodle_url('/local/rocketchat/linkaccount.php');
$PAGE->set_url($url);
$PAGE->set_context($context);

$title = get_string('linkaccount', 'local_rocketchat');
$PAGE->set_title($title);
$PAGE->set_heading(fullname($USER));
$PAGE->set_pagelayout('standard');

$linked = get_user_preferences('local_rocketchat_external_token');
if ($disconnect && $linked) {
    require_sesskey();

    unset_user_preference('local_rocketchat_external_user');
    unset_user_preference('local_rocketchat_external_token');
    redirect($url, get_string('linkaccount_disconnected', 'local_rocketchat'), null, notification::NOTIFY_SUCCESS);
}

if ($linked) {
    $form = new account($url, ['email' => get_user_preferences('local_rocketchat_external_user'), 'linked' => true]);
} else {
    $form = new account($url, ['linked' => false]);
    if ($form->is_cancelled()) {
        redirect(new moodle_url('/user/preferences.php'));
    }

    if ($form->is_submitted()) {
        if ($data = $form->get_data()) {
            $rocketchat = new client();
            $response = $rocketchat->authenticate($data->email, $data->password);

            if (is_null($response)) {
                redirect($url, get_string('connection_failure', 'local_rocketchat'), null, notification::NOTIFY_ERROR);
            }

            if (isset($response->status) && $response->status == 'success') {
                set_user_preference('local_rocketchat_external_user', $data->email);
                set_user_preference('local_rocketchat_external_token', $response->data->authToken);

                redirect($url, get_string('linkaccount_connected', 'local_rocketchat'), null, notification::NOTIFY_SUCCESS);
            }
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$form->display();
echo $OUTPUT->footer();
