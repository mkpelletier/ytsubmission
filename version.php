<?php
/**
 * Version information for the YouTube submission plugin
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026021300;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2024042200;        // Requires Moodle 5.0 or later.
$plugin->component = 'assignsubmission_ytsubmission'; // Full name of the plugin.
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '2.0';
$plugin->dependencies = array(
    'mod_assign' => 2024042200
);