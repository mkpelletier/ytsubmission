<?php
/**
 * External function: Save a comment to the library.
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

class save_library_comment extends external_api {

    /** @var array Allowed comment type values. */
    private static $allowed_types = ['general', 'praise', 'correction', 'suggestion', 'question'];

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID'),
            'commenttext'  => new external_value(PARAM_RAW, 'Comment text HTML'),
            'commenttype'  => new external_value(PARAM_ALPHA, 'Comment category', VALUE_DEFAULT, 'general'),
            'courseid'     => new external_value(PARAM_INT, 'Course ID (0 = personal)', VALUE_DEFAULT, 0),
            'itemid'       => new external_value(PARAM_INT, 'Existing item ID to update (0 = new)', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $assignmentid, string $commenttext, string $commenttype = 'general',
            int $courseid = 0, int $itemid = 0): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'assignmentid' => $assignmentid,
            'commenttext'  => $commenttext,
            'commenttype'  => $commenttype,
            'courseid'     => $courseid,
            'itemid'       => $itemid,
        ]);

        $cm = get_coursemodule_from_instance('assign', $params['assignmentid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/assign:grade', $context);

        $type = in_array($params['commenttype'], self::$allowed_types) ? $params['commenttype'] : 'general';

        if ($params['itemid'] > 0) {
            // Update existing item (owner only).
            $existing = $DB->get_record('assignsubmission_ytsubmission_comlib', ['id' => $params['itemid']], '*', MUST_EXIST);
            if ((int)$existing->userid !== (int)$USER->id) {
                throw new \moodle_exception('nopermission');
            }
            $existing->commenttext = clean_text(trim($params['commenttext']));
            $existing->commenttype = $type;
            $existing->timemodified = time();
            $DB->update_record('assignsubmission_ytsubmission_comlib', $existing);
            $newid = (int)$existing->id;
        } else {
            // Insert new item.
            $record = new stdClass();
            $record->userid = $USER->id;
            $record->courseid = $params['courseid'];
            $record->commenttext = clean_text(trim($params['commenttext']));
            $record->commenttype = $type;
            $record->sortorder = 0;
            $record->timecreated = time();
            $record->timemodified = time();
            $newid = (int)$DB->insert_record('assignsubmission_ytsubmission_comlib', $record);
        }

        return [
            'success' => true,
            'itemid'  => $newid,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation succeeded'),
            'itemid'  => new external_value(PARAM_INT, 'Library item ID'),
        ]);
    }
}
