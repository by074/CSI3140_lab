<?php

define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require_once(__DIR__ . '/../../config.php');

use local_securecoursehub\local\request_service;

/**
 * Send a JSON response and stop execution.
 *
 * @param array $data
 * @param int $status
 * @return void
 */
function local_securecoursehub_json_response(
    array $data,
    int $status = 200
): void {
    http_response_code($status);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    local_securecoursehub_json_response(
        [
            'success' => false,
            'error' => 'Only POST requests are accepted.',
        ],
        405
    );
}

try {
    $rawbody = file_get_contents('php://input');
    $data = json_decode($rawbody);

    if (
        !is_object($data) ||
        json_last_error() !== JSON_ERROR_NONE
    ) {
        local_securecoursehub_json_response(
            [
                'success' => false,
                'error' => get_string(
                    'invalidjson',
                    'local_securecoursehub'
                ),
            ],
            400
        );
    }

    $action = clean_param(
        $data->action ?? '',
        PARAM_ALPHANUMEXT
    );

    $courseid = (int) clean_param(
        $data->courseid ?? 0,
        PARAM_INT
    );

    $requestid = (int) clean_param(
        $data->id ?? 0,
        PARAM_INT
    );

    $status = clean_param(
        $data->status ?? '',
        PARAM_ALPHA
    );

    /*
     * Response text remains plain text. It is length-validated
     * by the service and escaped before rendering.
     */
    $response = isset($data->response)
        ? trim((string) $data->response)
        : '';

    if (
        $action !== 'update_teacher_request' ||
        $courseid <= 0 ||
        $requestid <= 0
    ) {
        local_securecoursehub_json_response(
            [
                'success' => false,
                'error' => get_string(
                    'invalidaction',
                    'local_securecoursehub'
                ),
            ],
            400
        );
    }

    $course = get_course($courseid);

    /*
     * Authentication must be checked before returning data.
     */
    require_login($course);

    $context = context_course::instance($courseid);

    /*
     * The interface button is not trusted.
     * The capability is checked again on the endpoint.
     */
    require_capability(
        'local/securecoursehub:managecourserequests',
        $context
    );

    /*
     * JSON data is not automatically placed in $_POST,
     * so confirm_sesskey() receives the supplied value directly.
     */
    $providedsesskey = isset($data->sesskey)
        ? (string) $data->sesskey
        : '';

    if (
        $providedsesskey === '' ||
        !confirm_sesskey($providedsesskey)
    ) {
        local_securecoursehub_json_response(
            [
                'success' => false,
                'error' => get_string(
                    'invalidsesskey',
                    'local_securecoursehub'
                ),
            ],
            403
        );
    }

    $record = request_service::update_course_request(
        $requestid,
        $courseid,
        $status,
        $response
    );

    $statuskey = 'status' . $record->status;

    local_securecoursehub_json_response(
        [
            'success' => true,
            'message' => get_string(
                'teacherrequestupdated',
                'local_securecoursehub'
            ),
            'request' => [
                'id' => (int) $record->id,
                'status' => $record->status,
                'statuslabel' => get_string(
                    $statuskey,
                    'local_securecoursehub'
                ),
                'response' => $record->response ?? '',
                'modified' => userdate(
                    $record->timemodified
                ),
            ],
        ]
    );
} catch (require_login_exception $exception) {
    local_securecoursehub_json_response(
        [
            'success' => false,
            'error' => get_string(
                'sessionexpired',
                'local_securecoursehub'
            ),
        ],
        401
    );
} catch (required_capability_exception $exception) {
    local_securecoursehub_json_response(
        [
            'success' => false,
            'error' => get_string(
                'accessdenied',
                'local_securecoursehub'
            ),
        ],
        403
    );
} catch (moodle_exception $exception) {
    $knownerrors = [
        'requestnotfound',
        'invalidstatus',
        'invalidresponse',
        'invalidcourse',
    ];

    if (
        in_array(
            $exception->errorcode,
            $knownerrors,
            true
        )
    ) {
        $statuscode =
            $exception->errorcode === 'requestnotfound'
                ? 404
                : 400;

        local_securecoursehub_json_response(
            [
                'success' => false,
                'error' => $exception->getMessage(),
            ],
            $statuscode
        );
    }

    local_securecoursehub_json_response(
        [
            'success' => false,
            'error' => get_string(
                'unexpectederror',
                'local_securecoursehub'
            ),
        ],
        400
    );
} catch (Throwable $exception) {
    /*
     * Internal PHP, database, configuration and path details
     * are deliberately not sent to the browser.
     */
    local_securecoursehub_json_response(
        [
            'success' => false,
            'error' => get_string(
                'unexpectederror',
                'local_securecoursehub'
            ),
        ],
        500
    );
}