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
 * Unit tests for the local_rocketchat subscriptions integration.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use local_rocketchat\integration\subscriptions;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the local_rocketchat subscriptions integration.
 *
 * All Rocket.Chat HTTP traffic is simulated through \curl::mock_response().
 * Note that the mocked responses form a stack (LIFO), so they are pushed in
 * reverse order of consumption. The client constructor always performs the
 * first (login) request.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(subscriptions::class)]
final class integration_subscriptions_test extends \advanced_testcase {
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
     * has_subscription() must return the joined state on a successful lookup.
     */
    public function test_has_subscription_returns_joined_state(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: groups.counters first, login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true, 'joined' => true]));
        \curl::mock_response($this->login_success_response());

        $subscriptions = new subscriptions(new client());

        $this->assertTrue($subscriptions->has_subscription('groupid1', 'rcid123'));
    }

    /**
     * A missing channel or user must short-circuit without network access.
     *
     * Regression test: the parameters were typed string, so the false
     * returned by has_channel_for_group() or get_user() for unknown channels
     * and users raised a TypeError instead of being handled.
     */
    public function test_has_subscription_without_channel_or_user(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response($this->login_success_response());

        $subscriptions = new subscriptions(new client());

        $this->assertFalse($subscriptions->has_subscription(false, 'rcid123'));
        $this->assertFalse($subscriptions->has_subscription('groupid1', false));
    }

    /**
     * A transport failure must read as "not subscribed" instead of crashing.
     *
     * Regression test: the method used to read ->success off a null response.
     */
    public function test_has_subscription_returns_false_on_failed_response(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: failed groups.counters first, login (consumed first) last.
        \curl::mock_response('');
        \curl::mock_response($this->login_success_response());

        $subscriptions = new subscriptions(new client());

        $this->assertFalse($subscriptions->has_subscription('groupid1', 'rcid123'));
    }

    /**
     * An unsubscribed group member is invited to the matching channel.
     */
    public function test_add_subscriptions_for_course_invites_member(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $user->id]);

        // LIFO: groups.invite, groups.counters, users.info, groups.info, login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true]));
        \curl::mock_response(json_encode(['success' => true, 'joined' => false]));
        \curl::mock_response($this->user_info_response());
        \curl::mock_response($this->group_info_response());
        \curl::mock_response($this->login_success_response());

        $subscriptions = new subscriptions(new client());
        $subscriptions->add_subscriptions_for_course($course);

        $this->assertEmpty($subscriptions->errors);
    }

    /**
     * A failed invite must record an error instead of crashing.
     *
     * Regression test: the error handler used to read ->success and ->error
     * off a null response.
     */
    public function test_add_subscription_for_user_records_error_on_failed_response(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);

        // LIFO: failed groups.invite, groups.counters, users.info, groups.info, login (consumed first) last.
        \curl::mock_response('');
        \curl::mock_response(json_encode(['success' => true, 'joined' => false]));
        \curl::mock_response($this->user_info_response());
        \curl::mock_response($this->group_info_response());
        \curl::mock_response($this->login_success_response());

        $subscriptions = new subscriptions(new client());
        $subscriptions->add_subscription_for_user($user, $group);

        $this->assertCount(1, $subscriptions->errors);
        $this->assertStringContainsString('no response from server', $subscriptions->errors[0]->error);
    }

    /**
     * A group without a matching Rocket.Chat channel is skipped cleanly.
     *
     * Regression test for the TypeError described in
     * {@see self::test_has_subscription_without_channel_or_user()}, hit
     * through the public entry point.
     */
    public function test_add_subscription_for_user_skips_unknown_channel(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);

        // LIFO: users.info, failed groups.info, login (consumed first) last.
        \curl::mock_response($this->user_info_response());
        \curl::mock_response('');
        \curl::mock_response($this->login_success_response());

        $subscriptions = new subscriptions(new client());
        $subscriptions->add_subscription_for_user($user, $group);

        $this->assertEmpty($subscriptions->errors);
    }

    /**
     * A subscribed user is kicked from the matching channel.
     */
    public function test_remove_subscription_for_user_kicks_member(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $user = $this->getDataGenerator()->create_user(['email' => 'jane@example.com']);

        // LIFO: groups.kick, groups.counters, users.info, groups.info, login (consumed first) last.
        \curl::mock_response(json_encode(['success' => true]));
        \curl::mock_response(json_encode(['success' => true, 'joined' => true]));
        \curl::mock_response($this->user_info_response());
        \curl::mock_response($this->group_info_response());
        \curl::mock_response($this->login_success_response());

        $subscriptions = new subscriptions(new client());
        $subscriptions->remove_subscription_for_user($user, $group);

        $this->assertEmpty($subscriptions->errors);
    }
}
