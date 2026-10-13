# CSI3140 Lab 5 — Secure Course Hub

A Moodle local plugin for course help requests. Students manage their own requests, while authorized teaching staff respond and update request status in the same course.

## Features

- Students create requests with a title (1–150 characters) and description (1–2000 characters).
- Students list only their own requests, edit or delete them while open, and read teacher responses.
- Teachers list course requests, filter by status, and save responses (up to 500 characters) through JSON AJAX without reloading the page.
- Supported statuses: `open`, `inprogress`, `resolved`. Teachers may move between any of these statuses; students cannot edit or delete non-open requests.
- Server controllers enforce Moodle login, course capabilities, and sesskeys. Student operations constrain records by request ID, course ID, and authenticated user ID; teacher updates constrain records by course.
- Plain text is escaped in PHP and displayed with JavaScript `textContent`. Database queries use Moodle's database API and bound parameters.

## Requirements and installation

Requires an existing Moodle 4.5 or compatible installation (minimum Moodle build `2024100700`). This repository contains a plugin, not a standalone website or a full Moodle installation.

The original project README records Windows, Moodle 4.5.12+, PHP 8.2.4, MariaDB 10.4.28, and Apache as the development environment. Plugin release: `1.1.0`; version: `2026071301`.

1. Copy `securecoursehub/` into `<moodle-root>/local/securecoursehub/`.
2. Sign in as a site administrator and visit **Site administration → Notifications** to install the plugin and create its table.
3. Purge Moodle caches.
4. Create a course and enroll an editing teacher and two students.
5. Open `http://localhost/local/securecoursehub/index.php?courseid=COURSE_ID`, replacing the origin and course ID for your installation.

The default teacher role intended here is Moodle's **editing teacher** (`editingteacher`), not the non-editing teacher archetype. The course capabilities are `local/securecoursehub:viewown`, `local/securecoursehub:createrequest`, and `local/securecoursehub:managecourserequests`.

## Repository structure

```text
securecoursehub/
  index.php                       Student and teacher dashboard
  ajax.php                        Teacher JSON update endpoint
  classes/local/request_service.php  Validation and database operations
  db/access.php                   Course capability definitions
  db/install.xml                  Request table schema
  amd/src/dashboard.js            JavaScript source
  amd/build/dashboard.min.js      Browser-loadable AMD module
  lang/en/local_securecoursehub.php  English interface strings
  style.css                       Plugin stylesheet
  version.php                     Moodle plugin metadata
  README.md                       Original plugin documentation
screenshot/                       Existing demonstration screenshots
```

Moodle creates `local_sch_request` using its configured database prefix. No full-site database import is required for a fresh plugin installation. Create your own local demonstration accounts. The original plaintext test credentials and whole-site `moodle.sql` backup are excluded from this repository because they contain authentication-related data.

## Verification and functional assessment

Review date: October 13, 2026.

- All six PHP files passed syntax checks using the bundled PHP executable.
- JavaScript source and the supplied AMD module passed Node.js syntax checks.
- `db/install.xml` parsed successfully as XML.
- Source review found the student create/list/edit/delete workflow, teacher status/response workflow, and the corresponding server-side ownership, course, capability, sesskey, and length checks.
- Both the JavaScript source and supplied AMD module implement teacher updates and safe dynamic text rendering.
- Existing screenshots cover the environment, enrollment, dashboard, student creation/listing/isolation/editing/deletion. They are historical evidence, not tests rerun during this review.

The implementation is functionally complete for the workflow above at the source-code level. A fresh Moodle install and browser end-to-end acceptance test were not performed during this review; runtime correctness is not certified by syntax checks alone.

### Manual acceptance checklist

1. Student A creates, edits, and deletes an open request.
2. Student B cannot see Student A's request; tampering with request IDs cannot edit or delete it.
3. The editing teacher filters requests, changes status, and saves a response without a full page reload.
4. Student A sees the response and cannot edit/delete an in-progress or resolved request.
5. Another course's request ID cannot be updated through the current course endpoint.
6. Guest/student teacher-endpoint calls and missing/invalid sesskeys are rejected.
7. Empty/overlong fields, invalid statuses, and missing records are rejected; HTML/script input displays as text.

Record actual results and screenshots when running this checklist. No completed acceptance report is supplied.

## Limitations

No pagination, email notifications, attachments, status audit history, or Moodle privacy API provider is implemented. The plugin stores user-linked request data and needs additional review before production use. It is supplied as a local laboratory project.

