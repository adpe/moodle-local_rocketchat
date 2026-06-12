# Changelog

All notable changes to the Rocket.Chat local plugin are documented in this file.
Releases use Moodle-style names (e.g. `v5.1-r3`); see the Git tags for the full history.

## v5.1-r3

Requires Moodle 5.1 (`2025041400`).

### Changed

- **Security:** the `local_rocketchat_*` web service functions (course/role/event based sync toggles and
  the manual sync trigger) now validate the system context and require the `local/rocketchat:manage`
  capability. Previously any logged-in user could call them via AJAX.

### Fixed

- The scheduled **"Sync students" task crashed on every run after the first one** (wrong class name), so
  the periodic sync never actually ran.
- A sync with an **unreachable Rocket.Chat server** is now recorded as a sync error on the course instead
  of crashing the whole sync task.
- Toggling the course, role or event based sync **no longer fails on PHP 8** when the helper record is
  created for the first time (accidental variable variables).
- User, channel and subscription sync are now **null-safe on failed API responses**: transport errors are
  recorded as sync errors ("no response from server") instead of aborting with a crash.
- **Enrolment suspensions are propagated again**: the user activity update called `users.update` with an
  invalid user id and never took effect.
- Adding or removing a group member whose **channel or user does not exist** on Rocket.Chat no longer
  raises a type error during event based sync.
- The **link account form** no longer accepts credentials unverified when the Rocket.Chat server returns
  no parseable response; it now shows a validation error.
- Three web service functions returned an `external_value` object instead of the confirmation string,
  failing the return value validation of the web service layer.

### Developer

- Extensive PHPUnit coverage for the API client, sync, channels, users, subscriptions, event observers,
  external functions, link account form, navigation hook and privacy provider. All Rocket.Chat traffic is
  simulated through `\curl::mock_response()`, so the suite runs without a Rocket.Chat instance.
- Test metadata uses PHPUnit attributes (`#[CoversClass]`, `#[DataProvider]`) instead of the deprecated
  doc-comment annotations (removed in PHPUnit 12).
- CI uploads PHPUnit coverage to Codecov (badge in the README).

## Earlier releases

- **v5.1-r1 / v5.1-r2** (2026-01-18) - Moodle 5.1 support; Bootstrap 5 markup and improved settings tables.
- **v4.0 - v4.5** (2022-2026) - Moodle 4.x support.
- **v3.9 - v3.11** (2021-2022) - initial public releases.
