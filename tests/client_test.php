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
 * Unit tests for the local_rocketchat API client.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the local_rocketchat API client.
 *
 * All Rocket.Chat HTTP traffic is simulated through \curl::mock_response().
 * The client constructor performs the login request, so every test mocks at
 * least that one response.
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(client::class)]
final class client_test extends \advanced_testcase {
    /**
     * Configure the plugin so the client can be constructed.
     *
     * @param int $protocol 0 for https, 1 for http
     * @param string $port an optional port
     */
    private function setup_client_config(int $protocol = 0, string $port = ''): void {
        set_config('host', 'chat.example.com', 'local_rocketchat');
        set_config('port', $port, 'local_rocketchat');
        set_config('protocol', $protocol, 'local_rocketchat');
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
     * The constructor logs in with the stored settings and keeps the credentials.
     */
    public function test_construct_authenticates_with_stored_settings(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response($this->login_success_response());

        $client = new client();

        $this->assertTrue($client->authenticated);
        $this->assertSame('https://chat.example.com', $client->get_instance_url());
        $this->assertContains('X-Auth-Token: token123', $client->authentication_headers());
        $this->assertContains('X-User-Id: adminid', $client->authentication_headers());
    }

    /**
     * A non-default protocol and port are reflected in the instance url.
     */
    public function test_instance_url_with_http_and_port(): void {
        $this->resetAfterTest();
        $this->setup_client_config(1, '3000');

        \curl::mock_response($this->login_success_response());

        $client = new client();

        $this->assertSame('http://chat.example.com:3000', $client->get_instance_url());
    }

    /**
     * A failed login leaves the client unauthenticated.
     */
    public function test_construct_with_failed_login(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response(json_encode(['status' => 'error', 'message' => 'Unauthorized']));

        $client = new client();

        $this->assertFalse($client->authenticated);
    }

    /**
     * authenticate() returns the raw response and flips the flag on success.
     */
    public function test_authenticate_after_failed_login(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        // LIFO: user login (success) first, constructor login (failure, consumed first) last.
        \curl::mock_response($this->login_success_response());
        \curl::mock_response(json_encode(['status' => 'error', 'message' => 'Unauthorized']));

        $client = new client();
        $this->assertFalse($client->authenticated);

        $response = $client->authenticate('jane', 'secret');

        $this->assertTrue($client->authenticated);
        $this->assertSame('success', $response->status);
    }

    /**
     * The content type header advertises JSON.
     */
    public function test_contenttype_headers(): void {
        $this->resetAfterTest();
        $this->setup_client_config();

        \curl::mock_response($this->login_success_response());

        $client = new client();

        $this->assertSame('Content-Type: application/json', $client->contenttype_headers());
    }
}
