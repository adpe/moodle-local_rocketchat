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
 * Client functions for Rocket.Chat authentication.
 *
 * @package     local_rocketchat
 * @copyright   2016 GetSmarter {@link http://www.getsmarter.co.za}
 * @author      2019 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use dml_exception;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/filelib.php');

/**
 * Class which handles authentication.
 */
class client {
    /**
     * Is the user already authenticated.
     *
     * @var bool
     */
    public bool $authenticated = false;

    /**
     * The endpoint URL to authenticate.
     *
     * @var string
     */
    public string $url;

    /**
     * Holds the auth token which can be used for future authentications (skip manual login).
     *
     * @var string
     */
    private string $authtoken;

    /**
     * The unique user id.
     *
     * @var string
     */
    private string $userid;

    /**
     * The unique username.
     *
     * @var mixed|false|object|string
     */
    private mixed $username;

    /**
     * The inputted clear text password.
     *
     * @var mixed|false|object|string
     */
    private mixed $password;

    /**
     * Client constructor to get settings for API calls.
     *
     * @throws dml_exception
     */
    public function __construct() {
        $host = get_config('local_rocketchat', 'host');
        $port = !empty($port = get_config('local_rocketchat', 'port')) ? ':' . $port : '';
        $protocol = get_config('local_rocketchat', 'protocol') == 0 ? 'https' : 'http';

        $this->url = $protocol . '://' . $host . $port;
        $this->username = get_config('local_rocketchat', 'username');
        $this->password = get_config('local_rocketchat', 'password');

        $this->authenticate($this->username, $this->password);
    }

    /**
     * The unique url for the block instance.
     *
     * @return string
     */
    public function get_instance_url(): string {
        return $this->url;
    }

    /**
     * The content type header.
     *
     * @return string
     */
    public function contenttype_headers(): string {
        return 'Content-Type: application/json';
    }

    /**
     * The authentication header.
     *
     * @return string[]
     */
    public function authentication_headers(): array {
        return ['X-Auth-Token: ' . $this->authtoken, 'X-User-Id: ' . $this->userid];
    }

    /**
     * Call authentication and store the credentials from response.
     *
     * @param string $user
     * @param string $password
     * @return bool|mixed
     */
    public function authenticate(string $user, string $password): mixed {
        $response = $this->request_login_credentials($user, $password);

        if (isset($response->status) && $response->status == 'success') {
            $this->store_credentials($response->data);
            $this->authenticated = true;
        }

        return $response;
    }

    /**
     * Authenticate user on Rocket.Chat endpoint.
     *
     * @param string $user
     * @param string $password
     * @return bool|mixed
     */
    private function request_login_credentials(string $user, string $password): mixed {
        $api = '/api/v1/login';

        $data = [
                'user' => $user,
                'password' => $password,
        ];

        $header[] = $this->contenttype_headers();

        return utilities::make_request($this->url, $api, 'post', $data, $header);
    }

    /**
     * Map the credentials from data to object.
     *
     * @param mixed $data
     */
    private function store_credentials(mixed $data): void {
        if (isset($data->authToken) && isset($data->userId)) {
            $this->authtoken = $data->authToken;
            $this->userid = $data->userId;
        }
    }
}
