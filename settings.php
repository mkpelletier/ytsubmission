<?php
/**
 * Settings for the YouTube submission plugin
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Default setting for whether this plugin is enabled by default for new assignments.
$settings->add(new admin_setting_configcheckbox(
    'assignsubmission_ytsubmission/default',
    get_string('enabled', 'assignsubmission_ytsubmission'),
    get_string('enabled_help', 'assignsubmission_ytsubmission'),
    0
));