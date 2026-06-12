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
 * Unit tests for the local_rocketchat user integration.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use local_rocketchat\integration\users;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the local_rocketchat user integration.
 *
 * All Rocket.Chat HTTP traffic is simulated through \curl::mock_response().
 * Note that the mocked responses form a stack (LIFO), so they are pushed in
 * reverse order of consumption.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(users::class)]
final class integration_users_test extends \advanced_testcase {
    /**
     * Configure the plugin and queue a successful admin login for the client constructor.
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
     * get_user() must return the Rocket.Chat user id as a plain string.
     *
     * Regression test: update_user_activity() used to dereference ->_id on
     * this return value a second time, so users.update was always called
     * with a null userId and suspensions were never propagated.
     */
    public function test_get_user_returns_id_string(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: users.info first, login (consumed first) last.
        \curl::mock_response(json_encode([
            'success' => true,
            'user' => ['_id' => 'rcid123', 'username' => 'jane'],
        ]));
        \curl::mock_response($this->login_success_response());

        $users = new users(new client());

        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);

        $this->assertSame('rcid123', $users->get_user($user));
    }

    /**
     * update_user_activity() must complete without errors when the API responds.
     *
     * Before the fix this raised an attempt to read property "_id" on string.
     */
    public function test_update_user_activity_completes_with_mocked_api(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setup_client_config();

        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $userenrolment = $DB->get_record('user_enrolments', ['userid' => $user->id], '*', MUST_EXIST);

        // LIFO: users.update first, users.info second, login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true]));
        \curl::mock_response(json_encode([
            'success' => true,
            'user' => ['_id' => 'rcid123', 'username' => 'jane'],
        ]));
        \curl::mock_response($this->login_success_response());

        $users = new users(new client());
        $users->update_user_activity($userenrolment->id);

        $this->assertEmpty($users->errors);
    }

    /**
     * create_user() must record an error instead of crashing when the API
     * returns no parseable response.
     *
     * Regression test: the error handler used to read ->success and ->error
     * off a null response.
     */
    public function test_create_user_records_error_on_missing_response(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: failed users.create first, login (consumed first) last.
        \curl::mock_response('');
        \curl::mock_response($this->login_success_response());

        $users = new users(new client());

        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $users->create_user($user);

        $this->assertCount(1, $users->errors);
        $this->assertStringContainsString('no response from server', $users->errors[0]->error);
    }

    /**
     * user_exists() must treat a failed users.list response as "no users"
     * instead of iterating over null.
     */
    public function test_user_exists_returns_false_on_failed_response(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: failed users.list first, login (consumed first) last.
        \curl::mock_response('');
        \curl::mock_response($this->login_success_response());

        $users = new users(new client());

        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);

        $this->assertFalse($users->user_exists($user));
    }

    /**
     * user_exists() must match on the username derived from the email.
     */
    public function test_user_exists_returns_true_for_matching_username(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: users.list first, login (consumed first) last.
        \curl::mock_response(json_encode([
            'success' => true,
            'users' => [['username' => 'jane'], ['username' => 'john']],
        ]));
        \curl::mock_response($this->login_success_response());

        $users = new users(new client());

        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);

        $this->assertTrue($users->user_exists($user));
    }

    /**
     * A successful user creation must not record any error.
     */
    public function test_create_user_succeeds(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: users.create first, login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true]));
        \curl::mock_response($this->login_success_response());

        $users = new users(new client());

        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $users->create_user($user);

        $this->assertEmpty($users->errors);
    }

    /**
     * Enrolled users missing on Rocket.Chat are created for the course.
     */
    public function test_create_users_for_course_creates_missing_users(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // LIFO: users.create, users.list (user missing), login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true]));
        \curl::mock_response(json_encode(['success' => true, 'users' => []]));
        \curl::mock_response($this->login_success_response());

        $users = new users(new client());
        $users->create_users_for_course((object) ['course' => $course->id]);

        $this->assertEmpty($users->errors);
    }

    /**
     * update_user_activity() must not call users.update for unknown users.
     *
     * Only the failing users.info lookup is mocked: a users.update request
     * would hit the empty mock stack and fail the test.
     */
    public function test_update_user_activity_skips_unknown_user(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setup_client_config();

        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $userenrolment = $DB->get_record('user_enrolments', ['userid' => $user->id], '*', MUST_EXIST);

        // LIFO: failed users.info first, login (consumed first) last.
        \curl::mock_response('');
        \curl::mock_response($this->login_success_response());

        $users = new users(new client());
        $users->update_user_activity($userenrolment->id);

        $this->assertEmpty($users->errors);
    }
}
