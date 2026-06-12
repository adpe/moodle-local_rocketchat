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
 * Unit tests for the local_rocketchat link account form.
 *
 * @package    local_rocketchat
 * @category   test
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rocketchat;

use local_rocketchat\form\account;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the local_rocketchat link account form.
 *
 * All Rocket.Chat HTTP traffic is simulated through \curl::mock_response().
 * Note that mocked responses form a stack (LIFO): the form definition
 * constructs a client (one login request) and validation() constructs
 * another one (a second login request) before verifying the credentials
 * (a third request).
 *
 * @copyright  2026 Adrian Perez <me@adrianperez.me> {@link https://adrianperez.me}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(account::class)]
final class form_account_test extends \advanced_testcase {
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
            'data' => ['authToken' => 'token123', 'userId' => 'someid'],
        ]);
    }

    /**
     * The inner quickform instance of a moodleform.
     *
     * @param account $form
     * @return \MoodleQuickForm
     */
    private function get_quickform(account $form): \MoodleQuickForm {
        $property = new \ReflectionProperty(\moodleform::class, '_form');
        $property->setAccessible(true);

        return $property->getValue($form);
    }

    /**
     * An unlinked account gets the credential fields.
     */
    public function test_definition_for_unlinked_account(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        \curl::mock_response($this->login_success_response());

        $form = new account(null, ['linked' => false]);
        $mform = $this->get_quickform($form);

        $this->assertTrue($mform->elementExists('email'));
        $this->assertTrue($mform->elementExists('password'));
        $this->assertFalse($mform->elementExists('disconnect'));
    }

    /**
     * A linked account gets the disconnect button instead of credentials.
     */
    public function test_definition_for_linked_account(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        \curl::mock_response($this->login_success_response());

        $form = new account(null, ['linked' => true, 'email' => 'jane@example.com']);
        $mform = $this->get_quickform($form);

        $this->assertTrue($mform->elementExists('disconnect'));
        $this->assertFalse($mform->elementExists('password'));
    }

    /**
     * Valid credentials pass the validation.
     */
    public function test_validation_accepts_valid_credentials(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        // LIFO: credential check, validation client login, definition client login (consumed first) last.
        \curl::mock_response($this->login_success_response());
        \curl::mock_response($this->login_success_response());
        \curl::mock_response($this->login_success_response());

        $form = new account(null, ['linked' => false]);
        $errors = $form->validation(['email' => 'jane@example.com', 'password' => 'secret'], []);

        $this->assertSame([], $errors);
    }

    /**
     * Rejected credentials flag the email field with the Rocket.Chat message.
     */
    public function test_validation_rejects_failed_authentication(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        // LIFO: failed credential check, validation client login, definition client login (consumed first) last.
        \curl::mock_response(json_encode([
            'status' => 'error',
            'error' => 'Unauthorized',
            'message' => 'Unauthorized',
        ]));
        \curl::mock_response($this->login_success_response());
        \curl::mock_response($this->login_success_response());

        $form = new account(null, ['linked' => false]);
        $errors = $form->validation(['email' => 'jane@example.com', 'password' => 'wrong'], []);

        $this->assertArrayHasKey('email', $errors);
        $this->assertStringContainsString(get_string('linkaccount_unexpectedresult', 'local_rocketchat'), $errors['email']);
        $this->assertStringContainsString('Unauthorized', $errors['email']);
    }

    /**
     * An unparseable response must flag the email field instead of crashing.
     *
     * Regression test: validation() only compared the response against false,
     * so the null returned for transport failures slipped through and the
     * error handler read ->error off null - the form then accepted the
     * credentials although they were never verified.
     */
    public function test_validation_rejects_missing_response(): void {
        $this->resetAfterTest();
        $this->setup_client_config();
        $this->setUser($this->getDataGenerator()->create_user());

        // LIFO: unparseable credential check, validation client login, definition client login (consumed first) last.
        \curl::mock_response('this is not json');
        \curl::mock_response($this->login_success_response());
        \curl::mock_response($this->login_success_response());

        $form = new account(null, ['linked' => false]);
        $errors = $form->validation(['email' => 'jane@example.com', 'password' => 'secret'], []);

        $this->assertArrayHasKey('email', $errors);
        $this->assertSame(get_string('linkaccount_unexpectedresult', 'local_rocketchat'), $errors['email']);
    }
}
