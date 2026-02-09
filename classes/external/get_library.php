<?php
/**
 * External function: Get comment library items.
 *
 * @package   assignsubmission_ytsubmission
 * @copyright 2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_ytsubmission\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use context_module;

class get_library extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID'),
            'courseid'     => new external_value(PARAM_INT, 'Course ID for shared items'),
        ]);
    }

    public static function execute(int $assignmentid, int $courseid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'assignmentid' => $assignmentid,
            'courseid'     => $courseid,
        ]);

        $cm = get_coursemodule_from_instance('assign', $params['assignmentid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/assign:grade', $context);

        // Personal items (courseid = 0, owned by current user).
        $personal = $DB->get_records('assignsubmission_ytsubmission_comlib', [
            'userid' => $USER->id,
            'courseid' => 0,
        ], 'sortorder ASC, timecreated DESC');

        // Course-shared items.
        $shared = [];
        if ($params['courseid'] > 0) {
            $shared = $DB->get_records('assignsubmission_ytsubmission_comlib', [
                'courseid' => $params['courseid'],
            ], 'sortorder ASC, timecreated DESC');
        }

        $format = function($items) use ($USER) {
            $result = [];
            foreach ($items as $item) {
                $result[] = [
                    'id'          => (int)$item->id,
                    'commenttext' => (string)$item->commenttext,
                    'commenttype' => (string)$item->commenttype,
                    'isowner'     => ((int)$item->userid === (int)$USER->id),
                ];
            }
            return $result;
        };

        return [
            'personal' => $format($personal),
            'shared'   => $format($shared),
        ];
    }

    public static function execute_returns(): external_single_structure {
        $itemstructure = new external_single_structure([
            'id'          => new external_value(PARAM_INT, 'Library item ID'),
            'commenttext' => new external_value(PARAM_RAW, 'Comment text HTML'),
            'commenttype' => new external_value(PARAM_ALPHA, 'Comment category'),
            'isowner'     => new external_value(PARAM_BOOL, 'Whether current user owns this item'),
        ]);

        return new external_single_structure([
            'personal' => new external_multiple_structure($itemstructure, 'Personal library items'),
            'shared'   => new external_multiple_structure($itemstructure, 'Course-shared library items'),
        ]);
    }
}
