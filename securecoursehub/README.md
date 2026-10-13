# Secure Course Hub

Secure Course Hub is a Moodle local plugin developed for
CSI 3140 Laboratory 5.

The plugin allows students to submit course-related help
requests and allows authorized teaching staff to manage those
requests securely.

## Environment

- Operating system: Windows
- Moodle version: Moodle 4.5.12+ (Build: 20260708)
- PHP version: PHP 8.2.4
- Database: MariaDB 10.4.28
- Web server: Apache
- Local URL: http://localhost
- Plugin component: local_securecoursehub
- Plugin release: 1.1.0
- Plugin version: 2026071301

## Installation

1. Copy the `securecoursehub` folder to:

   `<moodle-root>/local/securecoursehub`

2. Log in as a Moodle site administrator.

3. Open:

   `Site administration > Notifications`

4. Complete the Moodle database upgrade.

5. Purge Moodle caches:

   `Site administration > Development > Purge caches`

6. Create a demonstration course.

7. Enrol:
   - one Teacher
   - Student A
   - Student B

8. Open:

   `/local/securecoursehub/index.php?courseid=COURSE_ID`

## Required capabilities

- `local/securecoursehub:viewown`
- `local/securecoursehub:createrequest`
- `local/securecoursehub:managecourserequests`

The capabilities are checked in the course context.

## Test accounts

Synthetic test accounts are used.

- Administrator
- Teacher
- Student A
- Student B

Passwords are not included in this repository or document.

## Student operations

A student can:

- create a request in an enrolled course;
- view only their own requests;
- edit their own request while it is open;
- delete their own request while it is open;
- view the teacher response.

A student cannot:

- view another student's request;
- edit or delete another student's request;
- edit or delete an in-progress or resolved request;
- call a teacher-only operation successfully.

## Teacher operations

An authorized teacher can:

- view all requests in the authorized course;
- filter requests by status;
- change a request to open, in progress, or resolved;
- add or update a response of no more than 500 characters.

A teacher is denied access in a course where the required
course capability is not granted.

## Status transition rule

An authorized teacher may move a request between any of the
three allowed statuses:

- open
- inprogress
- resolved

Students may edit or delete only open requests.

## JSON interaction

Teacher updates use:

- JavaScript `fetch()`
- a JSON request body
- `ajax.php`
- a structured JSON response
- dynamic page updates without a full reload

The endpoint repeats authentication, capability, course,
record, status, response-length, and sesskey checks.

## Security controls

### Authentication

Protected pages and endpoints call Moodle `require_login()`.

### Authorization

Capabilities are enforced in the relevant course context.

### Ownership

Student database operations require the request ID, course ID,
and authenticated Moodle user ID to match.

### CSRF protection

Every state-changing form includes a Moodle sesskey.
The JSON endpoint validates the supplied sesskey.

### XSS protection

Untrusted values are rendered using safe Moodle output
functions and JavaScript `textContent`.

### Input validation

The server validates:

- required fields;
- integer IDs;
- title length;
- description length;
- response length;
- allowed status values;
- request existence;
- course association;
- ownership;
- permitted request state.

### Injection prevention

Database access uses Moodle's Database API and parameterized
queries.

### Safe errors

Browser responses do not expose database errors, stack traces,
password hashes, credentials, server paths, or configuration
values.

## Privacy

The plugin stores only:

- Moodle course ID;
- Moodle user ID;
- request title;
- request description;
- request status;
- teacher response;
- creation and modification timestamps.

Students can access only their own records. Authorized teachers
can access requests in their authorized course.

The plugin does not store passwords, cookies, sesskeys, or
separate authentication data.

Test data is synthetic. Retention is assumed to follow the
local course or laboratory retention policy.

## Testing

Mandatory acceptance testing will be documented separately with:

- account role;
- action;
- expected result;
- actual result;
- Pass/Fail status;
- selected screenshots.

## Known limitations

- The plugin is designed for a local laboratory environment.
- Email notifications are not implemented.
- File attachments are not implemented.
- Pagination is not implemented.
- Status changes do not keep a separate audit-history table.
- Public deployment has not been configured.