<?php

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Secure Course Hub';

/*
 * Capability names.
 */
$string['securecoursehub:viewown'] =
    'View own Secure Course Hub requests';

$string['securecoursehub:createrequest'] =
    'Create Secure Course Hub requests';

$string['securecoursehub:managecourserequests'] =
    'Manage course requests in Secure Course Hub';

/*
 * General page text.
 */
$string['dashboardheading'] = 'Secure Course Hub Dashboard';
$string['welcomeuser'] = 'Welcome, {$a}.';
$string['studentpanel'] = 'Student request area';
$string['teacherpanel'] = 'Course request management';
$string['createnewrequest'] = 'Create a new request';
$string['myrequests'] = 'My requests';
$string['courserequests'] = 'Course requests';

/*
 * Field labels.
 */
$string['requesttitle'] = 'Title';
$string['requestdescription'] = 'Description';
$string['student'] = 'Student';
$string['status'] = 'Status';
$string['response'] = 'Teacher response';
$string['created'] = 'Created';
$string['modified'] = 'Last modified';
$string['actions'] = 'Actions';

/*
 * Buttons and actions.
 */
$string['submitrequest'] = 'Submit request';
$string['editrequest'] = 'Edit request';
$string['updaterequest'] = 'Update request';
$string['deleterequest'] = 'Delete';
$string['cancelrequestedit'] = 'Cancel';
$string['saveteacherchanges'] = 'Save changes';
$string['applyfilter'] = 'Apply filter';

/*
 * Filtering and statuses.
 */
$string['filterstatus'] = 'Filter by status';
$string['statusall'] = 'All statuses';
$string['statusopen'] = 'Open';
$string['statusinprogress'] = 'In progress';
$string['statusresolved'] = 'Resolved';

/*
 * Success messages.
 */
$string['requestcreated'] =
    'The request was created successfully.';

$string['requestupdated'] =
    'The request was updated successfully.';

$string['requestdeleted'] =
    'The request was deleted successfully.';

$string['teacherrequestupdated'] =
    'The request status and response were updated successfully.';

/*
 * Empty states.
 */
$string['norequests'] =
    'You have not created any requests yet.';

$string['nocourserequests'] =
    'No requests match the selected course and status.';

$string['noresponse'] = 'No response yet.';
$string['noactionsavailable'] =
    'No student actions available.';

/*
 * Validation and safe error messages.
 */
$string['invalidcourse'] =
    'The selected course is invalid.';

$string['invalidtitle'] =
    'The title is required and must not exceed 150 characters.';

$string['invaliddescription'] =
    'The description is required and must not exceed 2000 characters.';

$string['invalidresponse'] =
    'The teacher response must not exceed 500 characters.';

$string['invalidstatus'] =
    'The selected request status is invalid.';

$string['invalidaction'] =
    'The requested operation is invalid.';

$string['requestnotfound'] =
    'The requested record could not be found.';

$string['requestnotopen'] =
    'Only open requests can be edited or deleted by a student.';

$string['accessdenied'] =
    'You do not have permission to perform this operation.';

$string['sessionexpired'] =
    'Your Moodle session has expired. Please log in again.';

$string['invalidjson'] =
    'The server received an invalid JSON request.';

$string['invalidsesskey'] =
    'The security token is missing or invalid.';

$string['unexpectederror'] =
    'An unexpected error occurred. No changes were saved.';

$string['invalidserverresponse'] =
    'The server returned an invalid response.';

$string['networkerror'] =
    'The request could not be completed. Check the connection and try again.';

$string['saving'] = 'Saving...';