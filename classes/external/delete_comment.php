<?php
/**
 * External function: Delete a timestamped comment from a YouTube submission.
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

/**
 * External function to delete a timestamped comment.
 */
class delete_comment extends external_api {

    /**
     * Describes the parameters for delete_comment.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'commentid' => new external_value(PARAM_INT, 'Comment ID to delete'),
        ]);
    }

    /**
     * Delete a timestamped comment.
     *
     * @param int $commentid Comment ID
     * @return array Result with success flag and message
     */
    public static function execute(int $commentid): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'commentid' => $commentid,
        ]);

        try {
            debugging('ytsubmission delete_comment: Starting deletion for comment ID: ' . $params['commentid'], DEBUG_DEVELOPER);

            // Fetch comment to get submission and assignment context.
            $comment = $DB->get_record(
                'assignsubmission_ytsubmission_comments',
                ['id' => $params['commentid']],
                '*',
                MUST_EXIST
            );

            debugging('ytsubmission delete_comment: Comment found for submission ID: ' . $comment->submissionid, DEBUG_DEVELOPER);

            // Fetch the submission to get the assignment ID.
            $submission = $DB->get_record(
                'assign_submission',
                ['id' => $comment->submissionid],
                '*',
                MUST_EXIST
            );

            debugging('ytsubmission delete_comment: Submission found for assignment ID: ' . $submission->assignment, DEBUG_DEVELOPER);

            // Get the course module and validate context.
            $cm = get_coursemodule_from_instance('assign', $submission->assignment, 0, false, MUST_EXIST);
            $context = context_module::instance($cm->id);

            self::validate_context($context);
            require_capability('mod/assign:grade', $context);

            debugging('ytsubmission delete_comment: Context validated, user has capability', DEBUG_DEVELOPER);

            // Delete the comment.
            $deleted = $DB->delete_records('assignsubmission_ytsubmission_comments', ['id' => $params['commentid']]);

            if (!$deleted) {
                throw new \moodle_exception('errordeletecomment', 'assignsubmission_ytsubmission', '', null,
                    'Failed to delete comment from database');
            }

            // Clean up any attached files.
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'assignsubmission_ytsubmission', 'commentfiles', $params['commentid']);

            debugging('ytsubmission delete_comment: Comment deleted successfully', DEBUG_DEVELOPER);

            // Build the response with explicit type casting.
            $response = [
                'success' => true,
                'message' => (string)'Comment deleted successfully.',
            ];

            debugging('ytsubmission delete_comment: Response prepared: ' . json_encode($response), DEBUG_DEVELOPER);

            return $response;

        } catch (\moodle_exception $e) {
            debugging('ytsubmission delete_comment: Moodle exception caught: ' . $e->getMessage(), DEBUG_DEVELOPER);

            return [
                'success' => false,
                'message' => (string)('Error deleting comment: ' . $e->getMessage()),
            ];

        } catch (\Exception $e) {
            debugging('ytsubmission delete_comment: General exception caught: ' . $e->getMessage(), DEBUG_DEVELOPER);

            return [
                'success' => false,
                'message' => (string)('Unexpected error deleting comment: ' . $e->getMessage()),
            ];
        }
    }

    /**
     * Describes the return value for delete_comment.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}