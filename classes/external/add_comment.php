<?php
/**
 * External function: Add timestamped comment to a YouTube submission.
 *
 * @package   assignsubmission_ytsubmission
 * @copyright 2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_ytsubmission\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use context_module;
use stdClass;

class add_comment extends external_api {

    /** @var array Allowed comment type values. */
    private static $allowed_types = ['general', 'praise', 'correction', 'suggestion', 'question'];

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'submissionid' => new external_value(PARAM_INT, 'Submission ID'),
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID'),
            'timestamp'    => new external_value(PARAM_INT, 'Video timestamp (sec)'),
            'comment'      => new external_value(PARAM_RAW, 'Comment HTML content'),
            'commenttype'  => new external_value(PARAM_ALPHA, 'Comment category', VALUE_DEFAULT, 'general'),
            'draftitemid'  => new external_value(PARAM_INT, 'Draft area item ID for file attachments', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $submissionid, int $assignmentid, int $timestamp, string $comment,
            string $commenttype = 'general', int $draftitemid = 0): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'submissionid' => $submissionid,
            'assignmentid' => $assignmentid,
            'timestamp'    => $timestamp,
            'comment'      => $comment,
            'commenttype'  => $commenttype,
            'draftitemid'  => $draftitemid,
        ]);

        try {
            $cm = get_coursemodule_from_instance('assign', $params['assignmentid'], 0, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            self::validate_context($context);
            require_capability('mod/assign:grade', $context);

            // Validate comment type.
            $type = $params['commenttype'];
            if (!in_array($type, self::$allowed_types)) {
                $type = 'general';
            }

            $record = new stdClass();
            $record->submissionid = $params['submissionid'];
            $record->assignmentid = $params['assignmentid'];
            $record->graderid     = $USER->id;
            $record->timestamp    = $params['timestamp'];
            $record->comment      = clean_text(trim($params['comment']));
            $record->commenttype  = $type;
            $record->timecreated  = time();
            $record->timemodified = time();

            $commentid = $DB->insert_record('assignsubmission_ytsubmission_comments', $record);

            // Handle file attachments from the draft area.
            if ($params['draftitemid'] > 0) {
                $record->id = $commentid;
                // Rewrite @@PLUGINFILE@@ URLs in the comment text.
                $record->comment = file_rewrite_urls_to_pluginfile($record->comment, $params['draftitemid']);
                $DB->update_record('assignsubmission_ytsubmission_comments', $record);

                // Save draft files to permanent storage.
                file_save_draft_area_files(
                    $params['draftitemid'],
                    $context->id,
                    'assignsubmission_ytsubmission',
                    'commentfiles',
                    $commentid
                );

                // Rewrite for display in response.
                $record->comment = file_rewrite_pluginfile_urls(
                    $record->comment,
                    'pluginfile.php',
                    $context->id,
                    'assignsubmission_ytsubmission',
                    'commentfiles',
                    $commentid
                );
            }

            $grader = $DB->get_record('user', ['id' => $USER->id], 'firstname,lastname');
            $gradername = fullname($grader);
            $formattedtime = userdate($record->timecreated, get_string('strftimedatetime', 'langconfig'));

            return [
                'success' => true,
                'message' => 'Comment added successfully.',
                'comment' => [
                    'id'          => (int)$commentid,
                    'timestamp'   => (int)$params['timestamp'],
                    'comment'     => (string)$record->comment,
                    'commenttype' => (string)$type,
                    'gradername'  => (string)$gradername,
                    'timecreated' => (string)$formattedtime,
                ]
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error adding comment: '.$e->getMessage(),
                'comment' => null,
            ];
        }
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation succeeded'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'comment' => new external_single_structure([
                'id'          => new external_value(PARAM_INT, 'Comment ID'),
                'timestamp'   => new external_value(PARAM_INT, 'Video timestamp'),
                'comment'     => new external_value(PARAM_RAW, 'Comment HTML content'),
                'commenttype' => new external_value(PARAM_ALPHA, 'Comment category'),
                'gradername'  => new external_value(PARAM_TEXT, 'Grader name'),
                'timecreated' => new external_value(PARAM_TEXT, 'Time created'),
            ], 'Comment data', VALUE_DEFAULT, null),
        ]);
    }
}