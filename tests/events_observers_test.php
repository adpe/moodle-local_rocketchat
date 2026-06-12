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
 * Unit tests for the local_rocketchat event observers.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use local_rocketchat\events\observers\group_member_added;
use local_rocketchat\events\observers\group_member_removed;
use local_rocketchat\events\observers\user_enrolment_created;
use local_rocketchat\events\observers\user_enrolment_updated;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the local_rocketchat event observers.
 *
 * The observers are exercised through real Moodle events. All Rocket.Chat
 * HTTP traffic is simulated through \curl::mock_response(); the mocked
 * responses form a stack (LIFO), so they are pushed in reverse order of
 * consumption and the observer's client login always consumes the first one.
 * Tests only mock the exact number of requests the observer must make: any
 * additional request would hit the empty mock stack and fail.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(group_member_added::class)]
#[CoversClass(group_member_removed::class)]
#[CoversClass(user_enrolment_created::class)]
#[CoversClass(user_enrolment_updated::class)]
final class events_observers_test extends \advanced_testcase {
    /**
     * Configure the plugin so the client can be constructed.
     */
    private function setup_client_config(): void {
        set_config('host', 'chat.example.com', 'local_rocketchat');
        set_config('port', '', 'local_rocketchat');
        set_config('protocol', 0, 'local_rocketchat');
        set_config('username', 'apiadmin', 'local_rocketchat');
        set_config('password', 'apipassword', 'local_rocketchat');
    }

    /**
     * The JSON body of a successful /api/v1/login response.
     *
     * @return string
     */
    private function login_success_response(): string {
        return json_encode([
            'status' => 'success',
            'data' => ['authToken' => 'token123', 'userId' => 'adminid'],
        ]);
    }

    /**
     * The JSON body of a successful /api/v1/groups.info response.
     *
     * @return string
     */
    private function group_info_response(): string {
        return json_encode([
            'success' => true,
            'group' => ['_id' => 'groupid1', 'name' => 'course-group'],
        ]);
    }

    /**
     * The JSON body of a successful /api/v1/users.info response.
     *
     * @return string
     */
    private function user_info_response(): string {
        return json_encode([
            'success' => true,
            'user' => ['_id' => 'rcid123', 'username' => 'jane'],
        ]);
    }

    /**
     * Without event based sync an enrolment must not contact Rocket.Chat.
     *
     * No response is mocked on purpose: any request would hit the empty mock
     * stack and fail the test.
     */
    public function test_user_enrolment_created_ignored_without_event_based_sync(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setup_client_config();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->assertFalse($DB->record_exists('local_rocketchat_courses', ['course' => $course->id]));
    }

    /**
     * An enrolment on an event based sync course checks the user on Rocket.Chat.
     */
    public function test_user_enrolment_created_checks_existing_user(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        utilities::set_rocketchat_event_based_sync($course->id, 1);

        // LIFO: users.list (user already exists) first, login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true, 'users' => [['username' => 'jane']]]));
        \curl::mock_response($this->login_success_response());

        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->assertEquals(1, integration\sync::is_event_based_sync_on_course($course->id));
    }

    /**
     * An enrolment status change propagates the activity state to Rocket.Chat.
     */
    public function test_user_enrolment_updated_syncs_activity(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setup_client_config();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        utilities::set_rocketchat_event_based_sync($course->id, 1);

        // LIFO: users.update, users.info, login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true]));
        \curl::mock_response($this->user_info_response());
        \curl::mock_response($this->login_success_response());

        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        enrol_get_plugin('manual')->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);

        $userenrolment = $DB->get_record('user_enrolments', ['userid' => $user->id], '*', MUST_EXIST);
        $this->assertEquals(ENROL_USER_SUSPENDED, $userenrolment->status);
    }

    /**
     * A new group member is invited to the matching Rocket.Chat channel.
     */
    public function test_group_member_added_invites_user(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        utilities::set_rocketchat_event_based_sync($course->id, 1);

        // LIFO: groups.invite, groups.counters (not joined), users.info, groups.info, login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true]));
        \curl::mock_response(json_encode(['success' => true, 'joined' => false]));
        \curl::mock_response($this->user_info_response());
        \curl::mock_response($this->group_info_response());
        \curl::mock_response($this->login_success_response());

        groups_add_member($group->id, $user->id);

        $this->assertTrue(groups_is_member($group->id, $user->id));
    }

    /**
     * A removed group member is kicked from the matching Rocket.Chat channel.
     */
    public function test_group_member_removed_kicks_user(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        groups_add_member($group->id, $user->id);
        utilities::set_rocketchat_event_based_sync($course->id, 1);

        // LIFO: groups.kick, groups.counters (joined), users.info, groups.info, login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true]));
        \curl::mock_response(json_encode(['success' => true, 'joined' => true]));
        \curl::mock_response($this->user_info_response());
        \curl::mock_response($this->group_info_response());
        \curl::mock_response($this->login_success_response());

        groups_remove_member($group->id, $user->id);

        $this->assertFalse(groups_is_member($group->id, $user->id));
    }
}
