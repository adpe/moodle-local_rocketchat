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
 * Site administration page for Course Integration in Rocket.Chat
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_rocketchat_course_integration');

$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_rocketchat/rocketchat', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('heading_course', 'local_rocketchat'));
echo html_writer::tag('p', get_string('course_desc', 'local_rocketchat'));

echo html_writer::start_tag('table', ['class' => 'admintable generaltable', 'id' => 'integrated-courses']);
echo html_writer::start_tag('thead');
echo html_writer::tag('th', get_string('coursetable_column_1', 'local_rocketchat'));
echo html_writer::tag('th', get_string('coursetable_column_2', 'local_rocketchat'));
echo html_writer::tag('th', get_string('coursetable_column_3', 'local_rocketchat'));
echo html_writer::tag('th', get_string('coursetable_column_4', 'local_rocketchat'));
echo html_writer::end_tag('thead');

echo html_writer::start_tag('tbody');

// Get all courses and list in table.
$rocketchatenabledcourses = \local_rocketchat\utilities::get_courses();
$courses = get_courses();

foreach ($courses as $course) {
    $isrocketchatcourse = false;
    $rocketchatcourse = null;

    foreach ($rocketchatenabledcourses as $rocketchatcourse) {
        if ($course->id == $rocketchatcourse->courseid) {
            echo html_writer::start_tag('tr');
            echo html_writer::start_tag('td');
            $courseurl = new moodle_url($CFG->wwwroot . '/course/view.php', ['id' => $course->id]);
            echo html_writer::tag('a', $course->fullname, ['href' => $courseurl]);
            echo html_writer::end_tag('td');

            echo html_writer::start_tag('td');
            echo html_writer::checkbox('eventbasedsync', null,
                    $rocketchatcourse->eventbasedsync, '', ['data-courseid' => $course->id]);
            echo html_writer::end_tag('td');

            echo html_writer::start_tag('td');
            echo html_writer::checkbox('pendingsync', null,
                    $rocketchatcourse->pendingsync, '', ['data-courseid' => $course->id]);
            echo html_writer::end_tag('td');

            echo html_writer::start_tag('td');

            if ($rocketchatcourse->lastsync) {
                $alert = ($rocketchatcourse->error) ? 'alert-danger' : 'alert-success';

                echo html_writer::start_tag('div', ['style' => 'margin-bottom: 0', 'class' => 'alert ' . $alert]);
                echo userdate($rocketchatcourse->lastsync, '%Y/%m/%d, %H:%M');

                if ($rocketchatcourse->error) {
                    echo html_writer::tag('span', ' ...', ['title' => $rocketchatcourse->error]);
                }

                echo html_writer::end_tag('div');
            }

            echo html_writer::end_tag('td');

            echo html_writer::start_tag('td');
            echo html_writer::tag("button", get_string('button_sync', 'local_rocketchat'),
                ["type" => "button",
                    "class" => "btn btn-default btn-xs",
                    "id" => "manual-sync",
                    "data-courseid" => $course->id,
                    "style" => "margin-bottom: 0"]
                );
            echo html_writer::end_tag('td');
            echo html_writer::end_tag('tr');
        }
    }
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// Show some additional information and hints.
echo html_writer::start_tag('div', ["class" => 'alert alert-info']);
echo html_writer::start_tag('ul', ["style" => "margin-top: 1rem"]);
echo html_writer::tag('li', get_string('courseinfo_1', 'local_rocketchat'));
echo html_writer::tag('li', get_string('courseinfo_2', 'local_rocketchat'));
echo html_writer::tag('li', get_string('courseinfo_3', 'local_rocketchat'));
echo html_writer::tag('li', get_string('courseinfo_4', 'local_rocketchat'));
echo html_writer::end_tag('ul');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
