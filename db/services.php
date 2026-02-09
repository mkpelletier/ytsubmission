<?php
/**
 * Web service definitions for YouTube submission plugin.
 *
 * @package   assignsubmission_ytsubmission
 * @copyright 2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'assignsubmission_ytsubmission_add_comment' => [
        'classname'   => 'assignsubmission_ytsubmission\\external\\add_comment',
        'description' => 'Add a timestamped comment to a YouTube submission.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/assign:grade'
    ],
    'assignsubmission_ytsubmission_delete_comment' => [
        'classname'   => 'assignsubmission_ytsubmission\\external\\delete_comment',
        'description' => 'Delete a timestamped comment from a YouTube submission.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/assign:grade'
    ],
    'assignsubmission_ytsubmission_get_library' => [
        'classname'   => 'assignsubmission_ytsubmission\\external\\get_library',
        'description' => 'Get comment library items (personal and course-shared).',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'mod/assign:grade'
    ],
    'assignsubmission_ytsubmission_save_library_comment' => [
        'classname'   => 'assignsubmission_ytsubmission\\external\\save_library_comment',
        'description' => 'Save a comment to the library.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/assign:grade'
    ],
    'assignsubmission_ytsubmission_delete_library_comment' => [
        'classname'   => 'assignsubmission_ytsubmission\\external\\delete_library_comment',
        'description' => 'Delete a comment from the library.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/assign:grade'
    ],
];

$services = [
    'YouTube submission services' => [
        'functions' => [
            'assignsubmission_ytsubmission_add_comment',
            'assignsubmission_ytsubmission_delete_comment',
            'assignsubmission_ytsubmission_get_library',
            'assignsubmission_ytsubmission_save_library_comment',
            'assignsubmission_ytsubmission_delete_library_comment',
        ],
        'restrictedusers' => 0,
        'enabled'         => 1,
        'shortname'       => 'assignsubmission_ytsubmission_services'
    ],
];