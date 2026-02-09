<?php
/**
 * External function: Delete a comment from the library.
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

class delete_library_comment extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID'),
            'itemid'       => new external_value(PARAM_INT, 'Library item ID to delete'),
        ]);
    }

    public static function execute(int $assignmentid, int $itemid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'assignmentid' => $assignmentid,
            'itemid'       => $itemid,
        ]);

        $cm = get_coursemodule_from_instance('assign', $params['assignmentid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/assign:grade', $context);

        $item = $DB->get_record('assignsubmission_ytsubmission_comlib', ['id' => $params['itemid']], '*', MUST_EXIST);

        // Personal items: owner only. Shared items: any grader in the course.
        if ((int)$item->courseid === 0 && (int)$item->userid !== (int)$USER->id) {
            throw new \moodle_exception('nopermission');
        }

        $DB->delete_records('assignsubmission_ytsubmission_comlib', ['id' => $params['itemid']]);

        return [
            'success' => true,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation succeeded'),
        ]);
    }
}
