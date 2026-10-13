<?php

use local_securecoursehub\local\request_service;

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);

require_login($course);

$context = context_course::instance($courseid);

require_capability(
    'local/securecoursehub:viewown',
    $context
);

$pageurl = new moodle_url(
    '/local/securecoursehub/index.php',
    ['courseid' => $courseid]
);

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(
    get_string('pluginname', 'local_securecoursehub')
);
$PAGE->set_heading(format_string($course->fullname));

$ismanager = has_capability(
    'local/securecoursehub:managecourserequests',
    $context
);

$errormessage = '';
$editid = optional_param('editid', 0, PARAM_INT);
$filterstatus = optional_param('status', '', PARAM_ALPHA);

if (
    $filterstatus !== '' &&
    !in_array(
        $filterstatus,
        request_service::allowed_statuses(),
        true
    )
) {
    $filterstatus = '';
}

/*
 * Handle student state-changing form submissions.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = optional_param(
        'action',
        '',
        PARAM_ALPHANUMEXT
    );

    try {
        /*
         * Every state-changing action requires a valid sesskey.
         */
        require_sesskey();

        if ($action === 'create_request') {
            require_capability(
                'local/securecoursehub:createrequest',
                $context
            );

            $title = required_param(
                'title',
                PARAM_RAW_TRIMMED
            );

            $description = required_param(
                'description',
                PARAM_RAW_TRIMMED
            );

            request_service::create_request(
                $courseid,
                (int) $USER->id,
                $title,
                $description
            );

            redirect(
                $pageurl,
                get_string(
                    'requestcreated',
                    'local_securecoursehub'
                ),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        if ($action === 'update_request') {
            $requestid = required_param(
                'requestid',
                PARAM_INT
            );

            $title = required_param(
                'title',
                PARAM_RAW_TRIMMED
            );

            $description = required_param(
                'description',
                PARAM_RAW_TRIMMED
            );

            request_service::update_own_open_request(
                $requestid,
                $courseid,
                (int) $USER->id,
                $title,
                $description
            );

            redirect(
                $pageurl,
                get_string(
                    'requestupdated',
                    'local_securecoursehub'
                ),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        if ($action === 'delete_request') {
            $requestid = required_param(
                'requestid',
                PARAM_INT
            );

            request_service::delete_own_open_request(
                $requestid,
                $courseid,
                (int) $USER->id
            );

            redirect(
                $pageurl,
                get_string(
                    'requestdeleted',
                    'local_securecoursehub'
                ),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        throw new moodle_exception(
            'invalidaction',
            'local_securecoursehub'
        );
    } catch (moodle_exception $exception) {
        $errormessage = $exception->getMessage();

        if (
            $action === 'update_request' &&
            isset($requestid)
        ) {
            $editid = (int) $requestid;
        }
    } catch (Throwable $exception) {
        $errormessage = get_string(
            'unexpectederror',
            'local_securecoursehub'
        );
    }
}

/*
 * Load data according to the current user's capability.
 */
if ($ismanager) {
    $requests = request_service::get_course_requests(
        $courseid,
        $filterstatus
    );
} else {
    $requests = request_service::get_own_requests(
        $courseid,
        (int) $USER->id
    );
}

/*
 * An edit form is loaded only after course and ownership checks.
 */
$editrequest = null;

if (!$ismanager && $editid > 0) {
    try {
        $editrequest = request_service::get_own_request(
            $editid,
            $courseid,
            (int) $USER->id
        );

        if (
            $editrequest->status !==
            request_service::STATUS_OPEN
        ) {
            throw new moodle_exception(
                'requestnotopen',
                'local_securecoursehub'
            );
        }
    } catch (moodle_exception $exception) {
        $errormessage = $exception->getMessage();
        $editrequest = null;
    }
}

$statuslabels = [
    request_service::STATUS_OPEN =>
        get_string(
            'statusopen',
            'local_securecoursehub'
        ),

    request_service::STATUS_INPROGRESS =>
        get_string(
            'statusinprogress',
            'local_securecoursehub'
        ),

    request_service::STATUS_RESOLVED =>
        get_string(
            'statusresolved',
            'local_securecoursehub'
        ),
];

/*
 * Load teacher AJAX code only for authorized managers.
 */
if ($ismanager) {
    $ajaxurl = new moodle_url(
        '/local/securecoursehub/ajax.php'
    );

    $PAGE->requires->js_call_amd(
        'local_securecoursehub/dashboard',
        'init',
        [[
            'endpoint' => $ajaxurl->out(false),
            'courseId' => $courseid,
            'successMessage' => get_string(
                'teacherrequestupdated',
                'local_securecoursehub'
            ),
            'failureMessage' => get_string(
                'unexpectederror',
                'local_securecoursehub'
            ),
            'invalidResponseMessage' => get_string(
                'invalidserverresponse',
                'local_securecoursehub'
            ),
            'savingMessage' => get_string(
                'saving',
                'local_securecoursehub'
            ),
        ]]
    );
}

echo $OUTPUT->header();

echo html_writer::start_div(
    'local-securecoursehub'
);

echo $OUTPUT->heading(
    get_string(
        'dashboardheading',
        'local_securecoursehub'
    )
);

echo html_writer::tag(
    'p',
    get_string(
        'welcomeuser',
        'local_securecoursehub',
        fullname($USER)
    ),
    ['class' => 'sch-welcome']
);

if ($errormessage !== '') {
    echo $OUTPUT->notification(
        s($errormessage),
        'notifyproblem'
    );
}

/*
 * ==========================
 * TEACHER INTERFACE
 * ==========================
 */
if ($ismanager) {
    echo $OUTPUT->heading(
        get_string(
            'teacherpanel',
            'local_securecoursehub'
        ),
        3
    );

    echo html_writer::tag(
        'div',
        '',
        [
            'id' => 'sch-ajax-message',
            'class' => 'alert',
            'hidden' => 'hidden',
            'role' => 'status',
            'aria-live' => 'polite',
        ]
    );
    ?>

    <form method="get"
          action="<?php echo s($pageurl->out(false)); ?>"
          class="sch-filter-form mb-4">

        <input type="hidden"
               name="courseid"
               value="<?php echo (int) $courseid; ?>">

        <label for="sch-status-filter"
               class="form-label">
            <?php
            echo get_string(
                'filterstatus',
                'local_securecoursehub'
            );
            ?>
        </label>

        <select id="sch-status-filter"
                name="status"
                class="form-select sch-filter-select">

            <option value="">
                <?php
                echo get_string(
                    'statusall',
                    'local_securecoursehub'
                );
                ?>
            </option>

            <?php foreach ($statuslabels as $value => $label) { ?>
                <option
                    value="<?php echo s($value); ?>"
                    <?php
                    echo $filterstatus === $value
                        ? 'selected'
                        : '';
                    ?>>
                    <?php echo s($label); ?>
                </option>
            <?php } ?>
        </select>

        <button type="submit"
                class="btn btn-secondary">
            <?php
            echo get_string(
                'applyfilter',
                'local_securecoursehub'
            );
            ?>
        </button>
    </form>

    <?php

    echo $OUTPUT->heading(
        get_string(
            'courserequests',
            'local_securecoursehub'
        ),
        4
    );

    if (empty($requests)) {
        echo $OUTPUT->notification(
            get_string(
                'nocourserequests',
                'local_securecoursehub'
            ),
            'notifyinfo'
        );
    } else {
        ?>

        <div class="table-responsive">
            <table class="table table-striped sch-request-table">
                <thead>
                    <tr>
                        <th>
                            <?php
                            echo get_string(
                                'student',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'requesttitle',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'requestdescription',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'status',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'response',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'modified',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'actions',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($requests as $request) { ?>
                    <tr data-request-id="<?php
                        echo (int) $request->id;
                    ?>">
                        <td>
                            <?php
                            echo s(
                                fullname(
                                    (object) [
                                        'firstname' =>
                                            $request->firstname,
                                        'lastname' =>
                                            $request->lastname,
                                    ]
                                )
                            );
                            ?>
                        </td>

                        <td>
                            <?php echo s($request->title); ?>
                        </td>

                        <td>
                            <?php
                            echo nl2br(
                                s($request->description)
                            );
                            ?>
                        </td>

                        <td>
                            <span class="badge bg-secondary sch-status-label">
                                <?php
                                echo s(
                                    $statuslabels[
                                        $request->status
                                    ]
                                );
                                ?>
                            </span>

                            <select name="status"
                                    form="teacher-form-<?php
                                        echo (int) $request->id;
                                    ?>"
                                    class="form-select mt-2">
                                <?php
                                foreach (
                                    $statuslabels
                                    as $value => $label
                                ) {
                                    ?>
                                    <option
                                        value="<?php echo s($value); ?>"
                                        <?php
                                        echo $request->status === $value
                                            ? 'selected'
                                            : '';
                                        ?>>
                                        <?php echo s($label); ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </td>

                        <td>
                            <textarea
                                name="response"
                                form="teacher-form-<?php
                                    echo (int) $request->id;
                                ?>"
                                class="form-control sch-response"
                                maxlength="500"
                                rows="4"><?php
                                echo s(
                                    $request->response ?? ''
                                );
                            ?></textarea>
                        </td>

                        <td class="sch-modified">
                            <?php
                            echo s(
                                userdate(
                                    $request->timemodified
                                )
                            );
                            ?>
                        </td>

                        <td>
                            <form
                                id="teacher-form-<?php
                                    echo (int) $request->id;
                                ?>"
                                class="sch-teacher-form">

                                <input type="hidden"
                                       name="requestid"
                                       value="<?php
                                           echo (int) $request->id;
                                       ?>">

                                <button type="submit"
                                        class="btn btn-primary btn-sm">
                                    <?php
                                    echo get_string(
                                        'saveteacherchanges',
                                        'local_securecoursehub'
                                    );
                                    ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <?php
    }
}

/*
 * ==========================
 * STUDENT INTERFACE
 * ==========================
 */
if (!$ismanager) {
    echo $OUTPUT->heading(
        get_string(
            'studentpanel',
            'local_securecoursehub'
        ),
        3
    );

    if (
        has_capability(
            'local/securecoursehub:createrequest',
            $context
        )
    ) {
        echo $OUTPUT->heading(
            get_string(
                'createnewrequest',
                'local_securecoursehub'
            ),
            4
        );
        ?>

        <form method="post"
              action="<?php echo s($pageurl->out(false)); ?>"
              class="sch-card mb-4">

            <input type="hidden"
                   name="courseid"
                   value="<?php echo (int) $courseid; ?>">

            <input type="hidden"
                   name="action"
                   value="create_request">

            <input type="hidden"
                   name="sesskey"
                   value="<?php echo s(sesskey()); ?>">

            <div class="mb-3">
                <label for="request-title"
                       class="form-label">
                    <?php
                    echo get_string(
                        'requesttitle',
                        'local_securecoursehub'
                    );
                    ?>
                </label>

                <input type="text"
                       id="request-title"
                       name="title"
                       class="form-control"
                       maxlength="150"
                       required>
            </div>

            <div class="mb-3">
                <label for="request-description"
                       class="form-label">
                    <?php
                    echo get_string(
                        'requestdescription',
                        'local_securecoursehub'
                    );
                    ?>
                </label>

                <textarea
                    id="request-description"
                    name="description"
                    class="form-control"
                    maxlength="2000"
                    rows="5"
                    required></textarea>
            </div>

            <button type="submit"
                    class="btn btn-primary">
                <?php
                echo get_string(
                    'submitrequest',
                    'local_securecoursehub'
                );
                ?>
            </button>
        </form>

        <?php
    }

    if ($editrequest !== null) {
        echo $OUTPUT->heading(
            get_string(
                'editrequest',
                'local_securecoursehub'
            ),
            4
        );
        ?>

        <form method="post"
              action="<?php echo s($pageurl->out(false)); ?>"
              class="sch-card mb-4">

            <input type="hidden"
                   name="courseid"
                   value="<?php echo (int) $courseid; ?>">

            <input type="hidden"
                   name="action"
                   value="update_request">

            <input type="hidden"
                   name="requestid"
                   value="<?php
                       echo (int) $editrequest->id;
                   ?>">

            <input type="hidden"
                   name="sesskey"
                   value="<?php echo s(sesskey()); ?>">

            <div class="mb-3">
                <label for="edit-request-title"
                       class="form-label">
                    <?php
                    echo get_string(
                        'requesttitle',
                        'local_securecoursehub'
                    );
                    ?>
                </label>

                <input
                    type="text"
                    id="edit-request-title"
                    name="title"
                    class="form-control"
                    maxlength="150"
                    value="<?php
                        echo s($editrequest->title);
                    ?>"
                    required>
            </div>

            <div class="mb-3">
                <label for="edit-request-description"
                       class="form-label">
                    <?php
                    echo get_string(
                        'requestdescription',
                        'local_securecoursehub'
                    );
                    ?>
                </label>

                <textarea
                    id="edit-request-description"
                    name="description"
                    class="form-control"
                    maxlength="2000"
                    rows="5"
                    required><?php
                    echo s($editrequest->description);
                ?></textarea>
            </div>

            <button type="submit"
                    class="btn btn-primary">
                <?php
                echo get_string(
                    'updaterequest',
                    'local_securecoursehub'
                );
                ?>
            </button>

            <a href="<?php
                echo s($pageurl->out(false));
            ?>"
               class="btn btn-secondary">
                <?php
                echo get_string(
                    'cancelrequestedit',
                    'local_securecoursehub'
                );
                ?>
            </a>
        </form>

        <?php
    }

    echo $OUTPUT->heading(
        get_string(
            'myrequests',
            'local_securecoursehub'
        ),
        4
    );

    if (empty($requests)) {
        echo $OUTPUT->notification(
            get_string(
                'norequests',
                'local_securecoursehub'
            ),
            'notifyinfo'
        );
    } else {
        ?>

        <div class="table-responsive">
            <table class="table table-striped sch-request-table">
                <thead>
                    <tr>
                        <th>
                            <?php
                            echo get_string(
                                'requesttitle',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'requestdescription',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'status',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'response',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'modified',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                        <th>
                            <?php
                            echo get_string(
                                'actions',
                                'local_securecoursehub'
                            );
                            ?>
                        </th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($requests as $request) { ?>
                    <tr>
                        <td>
                            <?php echo s($request->title); ?>
                        </td>

                        <td>
                            <?php
                            echo nl2br(
                                s($request->description)
                            );
                            ?>
                        </td>

                        <td>
                            <span class="badge bg-secondary">
                                <?php
                                echo s(
                                    $statuslabels[
                                        $request->status
                                    ]
                                );
                                ?>
                            </span>
                        </td>

                        <td>
                            <?php
                            if (
                                $request->response === null ||
                                trim($request->response) === ''
                            ) {
                                echo s(
                                    get_string(
                                        'noresponse',
                                        'local_securecoursehub'
                                    )
                                );
                            } else {
                                echo nl2br(
                                    s($request->response)
                                );
                            }
                            ?>
                        </td>

                        <td>
                            <?php
                            echo s(
                                userdate(
                                    $request->timemodified
                                )
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            if (
                                $request->status ===
                                request_service::STATUS_OPEN
                            ) {
                                $editurl = new moodle_url(
                                    '/local/securecoursehub/index.php',
                                    [
                                        'courseid' => $courseid,
                                        'editid' => $request->id,
                                    ]
                                );

                                echo html_writer::link(
                                    $editurl,
                                    get_string(
                                        'editrequest',
                                        'local_securecoursehub'
                                    ),
                                    [
                                        'class' =>
                                            'btn btn-secondary btn-sm mb-1',
                                    ]
                                );
                                ?>

                                <form method="post"
                                      action="<?php
                                          echo s(
                                              $pageurl->out(false)
                                          );
                                      ?>"
                                      class="d-inline">

                                    <input type="hidden"
                                           name="courseid"
                                           value="<?php
                                               echo (int) $courseid;
                                           ?>">

                                    <input type="hidden"
                                           name="action"
                                           value="delete_request">

                                    <input type="hidden"
                                           name="requestid"
                                           value="<?php
                                               echo (int) $request->id;
                                           ?>">

                                    <input type="hidden"
                                           name="sesskey"
                                           value="<?php
                                               echo s(sesskey());
                                           ?>">

                                    <button type="submit"
                                            class="btn btn-danger btn-sm mb-1">
                                        <?php
                                        echo get_string(
                                            'deleterequest',
                                            'local_securecoursehub'
                                        );
                                        ?>
                                    </button>
                                </form>

                                <?php
                            } else {
                                echo s(
                                    get_string(
                                        'noactionsavailable',
                                        'local_securecoursehub'
                                    )
                                );
                            }
                            ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <?php
    }
}

echo html_writer::end_div();

echo $OUTPUT->footer();