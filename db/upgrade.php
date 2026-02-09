<?php
/**
 * Upgrade script for the YouTube submission plugin
 *
 * @package    assignsubmission_ytsubmission
 * @copyright  2025 Mathieu Pelletier <mathieu@sats.ac.za>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the YouTube submission plugin
 *
 * @param int $oldversion The old version of the plugin
 * @return bool Always returns true
 */
function xmldb_assignsubmission_ytsubmission_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Phase 1: Add commenttype field to comments table.
    if ($oldversion < 2026021100) {
        $table = new xmldb_table('assignsubmission_ytsubmission_comments');
        $field = new xmldb_field('commenttype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'general', 'comment');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026021100, 'assignsubmission', 'ytsubmission');
    }

    // Phase 3: Create comment library table.
    if ($oldversion < 2026021300) {
        $table = new xmldb_table('assignsubmission_ytsubmission_comlib');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('commenttext', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('commenttype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'general');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userfk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        $table->add_index('courseid_ix', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('usertype_ix', XMLDB_INDEX_NOTUNIQUE, ['userid', 'commenttype']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026021300, 'assignsubmission', 'ytsubmission');
    }

    return true;
}