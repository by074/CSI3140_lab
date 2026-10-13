<?php

namespace local_securecoursehub\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Business and database operations for Secure Course Hub.
 */
class request_service {
    public const STATUS_OPEN = 'open';
    public const STATUS_INPROGRESS = 'inprogress';
    public const STATUS_RESOLVED = 'resolved';

    /**
     * Return all allowed request statuses.
     *
     * @return array
     */
    public static function allowed_statuses(): array {
        return [
            self::STATUS_OPEN,
            self::STATUS_INPROGRESS,
            self::STATUS_RESOLVED,
        ];
    }

    /**
     * Validate a title.
     *
     * @param string $title
     * @return string
     */
    private static function validate_title(string $title): string {
        $title = trim($title);

        if (
            $title === '' ||
            \core_text::strlen($title) > 150
        ) {
            throw new \moodle_exception(
                'invalidtitle',
                'local_securecoursehub'
            );
        }

        return $title;
    }

    /**
     * Validate a description.
     *
     * @param string $description
     * @return string
     */
    private static function validate_description(
        string $description
    ): string {
        $description = trim($description);

        if (
            $description === '' ||
            \core_text::strlen($description) > 2000
        ) {
            throw new \moodle_exception(
                'invaliddescription',
                'local_securecoursehub'
            );
        }

        return $description;
    }

    /**
     * Validate a teacher response.
     *
     * Empty responses are allowed.
     *
     * @param string $response
     * @return string
     */
    private static function validate_response(
        string $response
    ): string {
        $response = trim($response);

        if (\core_text::strlen($response) > 500) {
            throw new \moodle_exception(
                'invalidresponse',
                'local_securecoursehub'
            );
        }

        return $response;
    }

    /**
     * Validate a status.
     *
     * @param string $status
     * @return string
     */
    private static function validate_status(
        string $status
    ): string {
        if (
            !in_array(
                $status,
                self::allowed_statuses(),
                true
            )
        ) {
            throw new \moodle_exception(
                'invalidstatus',
                'local_securecoursehub'
            );
        }

        return $status;
    }

    /**
     * Return an owned request in the specified course.
     *
     * The same safe not-found response is used for missing and
     * unauthorized records to avoid exposing another student's data.
     *
     * @param int $requestid
     * @param int $courseid
     * @param int $userid
     * @return \stdClass
     */
    private static function get_owned_record(
        int $requestid,
        int $courseid,
        int $userid
    ): \stdClass {
        global $DB;

        $record = $DB->get_record(
            'local_sch_request',
            [
                'id' => $requestid,
                'courseid' => $courseid,
                'userid' => $userid,
            ]
        );

        if (!$record) {
            throw new \moodle_exception(
                'requestnotfound',
                'local_securecoursehub'
            );
        }

        return $record;
    }

    /**
     * Return a request in an authorized course.
     *
     * @param int $requestid
     * @param int $courseid
     * @return \stdClass
     */
    private static function get_course_record(
        int $requestid,
        int $courseid
    ): \stdClass {
        global $DB;

        $record = $DB->get_record(
            'local_sch_request',
            [
                'id' => $requestid,
                'courseid' => $courseid,
            ]
        );

        if (!$record) {
            throw new \moodle_exception(
                'requestnotfound',
                'local_securecoursehub'
            );
        }

        return $record;
    }

    /**
     * Create a new student request.
     *
     * @param int $courseid
     * @param int $userid Authenticated Moodle user ID.
     * @param string $title
     * @param string $description
     * @return int New record ID.
     */
    public static function create_request(
        int $courseid,
        int $userid,
        string $title,
        string $description
    ): int {
        global $DB;

        $title = self::validate_title($title);
        $description =
            self::validate_description($description);

        if (!$DB->record_exists('course', ['id' => $courseid])) {
            throw new \moodle_exception(
                'invalidcourse',
                'local_securecoursehub'
            );
        }

        $now = time();

        $record = (object) [
            'courseid' => $courseid,
            'userid' => $userid,
            'title' => $title,
            'description' => $description,
            'status' => self::STATUS_OPEN,
            'response' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        return (int) $DB->insert_record(
            'local_sch_request',
            $record
        );
    }

    /**
     * Return only the authenticated user's requests.
     *
     * @param int $courseid
     * @param int $userid
     * @return array
     */
    public static function get_own_requests(
        int $courseid,
        int $userid
    ): array {
        global $DB;

        return $DB->get_records(
            'local_sch_request',
            [
                'courseid' => $courseid,
                'userid' => $userid,
            ],
            'timemodified DESC'
        );
    }

    /**
     * Return one owned request.
     *
     * @param int $requestid
     * @param int $courseid
     * @param int $userid
     * @return \stdClass
     */
    public static function get_own_request(
        int $requestid,
        int $courseid,
        int $userid
    ): \stdClass {
        return self::get_owned_record(
            $requestid,
            $courseid,
            $userid
        );
    }

    /**
     * Update a student's own open request.
     *
     * @param int $requestid
     * @param int $courseid
     * @param int $userid
     * @param string $title
     * @param string $description
     * @return void
     */
    public static function update_own_open_request(
        int $requestid,
        int $courseid,
        int $userid,
        string $title,
        string $description
    ): void {
        global $DB;

        $record = self::get_owned_record(
            $requestid,
            $courseid,
            $userid
        );

        if ($record->status !== self::STATUS_OPEN) {
            throw new \moodle_exception(
                'requestnotopen',
                'local_securecoursehub'
            );
        }

        $record->title = self::validate_title($title);
        $record->description =
            self::validate_description($description);
        $record->timemodified = time();

        $DB->update_record(
            'local_sch_request',
            $record
        );
    }

    /**
     * Delete a student's own open request.
     *
     * @param int $requestid
     * @param int $courseid
     * @param int $userid
     * @return void
     */
    public static function delete_own_open_request(
        int $requestid,
        int $courseid,
        int $userid
    ): void {
        global $DB;

        $record = self::get_owned_record(
            $requestid,
            $courseid,
            $userid
        );

        if ($record->status !== self::STATUS_OPEN) {
            throw new \moodle_exception(
                'requestnotopen',
                'local_securecoursehub'
            );
        }

        $DB->delete_records(
            'local_sch_request',
            ['id' => $record->id]
        );
    }

    /**
     * Return course requests for a teacher or manager.
     *
     * @param int $courseid
     * @param string $status Empty string means all statuses.
     * @return array
     */
    public static function get_course_requests(
        int $courseid,
        string $status = ''
    ): array {
        global $DB;

        $params = ['courseid' => $courseid];

        $sql = "
            SELECT
                r.*,
                u.firstname,
                u.lastname
            FROM {local_sch_request} r
            JOIN {user} u
              ON u.id = r.userid
            WHERE r.courseid = :courseid
        ";

        if ($status !== '') {
            self::validate_status($status);
            $sql .= " AND r.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY r.timemodified DESC";

        return $DB->get_records_sql(
            $sql,
            $params
        );
    }

    /**
     * Update a request as an authorized course teacher.
     *
     * The controller must check the management capability in
     * the correct course context before calling this method.
     *
     * @param int $requestid
     * @param int $courseid
     * @param string $status
     * @param string $response
     * @return \stdClass Updated record.
     */
    public static function update_course_request(
        int $requestid,
        int $courseid,
        string $status,
        string $response
    ): \stdClass {
        global $DB;

        $status = self::validate_status($status);
        $response = self::validate_response($response);

        $record = self::get_course_record(
            $requestid,
            $courseid
        );

        /*
         * Documented teacher transition rule:
         * an authorized teacher may move a request between any
         * of the three allowed statuses.
         */
        $record->status = $status;
        $record->response =
            $response === '' ? null : $response;
        $record->timemodified = time();

        $DB->update_record(
            'local_sch_request',
            $record
        );

        return $record;
    }
}