<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade script for block_coupon
 *
 * File         upgrade.php
 * Encoding     UTF-8
 *
 * @package     block_coupon
 *
 * @copyright   Sebsoft.nl
 * @author      Menno de Ridder <menno@sebsoft.nl>
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Upgrade
 *
 * @param int $oldversion old (current) plugin version
 * @return boolean
 */
function xmldb_block_coupon_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    if ($oldversion < 2016011000) {
        // Add activity completion table.
        $table = new xmldb_table('block_coupon_errors');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('couponid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('errortype', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'couponid');
        $table->add_field('errormessage', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, 'errortype');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'errormessage');
        // Add KEYS.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Add INDEXES.
        $table->add_index('couponid', XMLDB_INDEX_NOTUNIQUE, array('couponid'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // We shall add indexes to link tables!
        $table = new xmldb_table('block_coupon_cohorts');
        $index = new xmldb_index('couponid', XMLDB_INDEX_NOTUNIQUE, array('couponid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('cohortid', XMLDB_INDEX_NOTUNIQUE, array('cohortid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('block_coupon_groups');
        $index = new xmldb_index('couponid', XMLDB_INDEX_NOTUNIQUE, array('couponid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('groupid', XMLDB_INDEX_NOTUNIQUE, array('groupid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('block_coupon_courses');
        $index = new xmldb_index('couponid', XMLDB_INDEX_NOTUNIQUE, array('couponid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Block_tped savepoint reached.
        upgrade_block_savepoint(true, 2016011000, 'coupon');

    }

    if ($oldversion < 2017050100) {
        // Add activity completion table.
        $table = new xmldb_table('block_coupon');
        $field = new xmldb_field('logoid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'submission_code');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('typ', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'logoid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Detect all coupon types and set them.
        // Set cohort types.
        $cids = $DB->get_fieldset_sql('SELECT DISTINCT couponid FROM {block_coupon_cohorts}');
        list($insql, $params) = $DB->get_in_or_equal($cids, SQL_PARAMS_QM, 'unused', true, 0);
        array_unshift($params, 'cohort');
        $DB->execute('UPDATE {block_coupon} SET typ = ? WHERE id '.$insql, $params);
        // Set course types.
        list($notinsql, $params) = $DB->get_in_or_equal($cids, SQL_PARAMS_QM, 'unused', false, 0);
        array_unshift($params, 'course');
        $DB->execute('UPDATE {block_coupon} SET typ = ? WHERE id '.$notinsql, $params);

        // Now IF we have a custom logo, please place into Moodle's Filesystem.
        $logofile = $CFG->dataroot.'/coupon_logos/couponlogo.png';
        if (file_exists($logofile)) {
            // Store.
            $content = file_get_contents($logofile);
            \block_coupon\logostorage::store_from_content('couponlogo.png', $content);
            // Delete original.
            unlink($logofile);
            // ANd remove dir.
            remove_dir(dirname($logofile));
        }

        // Block_tped savepoint reached.
        upgrade_block_savepoint(true, 2017050100, 'coupon');

    }

    if ($oldversion < 2017050102) {
        // Add claimed bit.
        $table = new xmldb_table('block_coupon');
        $field = new xmldb_field('claimed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'typ');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Set all that have userids claimed.
        $sql = 'UPDATE {block_coupon} SET claimed = 1 WHERE (userid IS NOT NULL OR userid = 0)';
        $DB->execute($sql);

        // Block_tped savepoint reached.
        upgrade_block_savepoint(true, 2017050102, 'coupon');

    }

    if ($oldversion < 2017050103) {
        // Transform enrolperiod column to contain seconds instead of days.
        $sql = 'UPDATE {block_coupon} SET enrolperiod = enrolperiod * 86400 WHERE enrolperiod <> 0';
        $DB->execute($sql);

        // Block_tped savepoint reached.
        upgrade_block_savepoint(true, 2017050103, 'coupon');

    }

    return true;
}