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
 * Coursegrouping coupon generator form (step 1)
 *
 * File         page1.php
 * Encoding     UTF-8
 *
 * @package     block_coupon
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coupon\forms\coupon\coursegrouping;

defined('MOODLE_INTERNAL') || die();

use block_coupon\helper;
use block_coupon\coupon\generatoroptions;

require_once($CFG->libdir . '/formslib.php');

/**
 * block_coupon\forms\coupon\coursegrouping\page1
 *
 * @package     block_coupon
 *
 * @copyright   Sebsoft.nl
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page1 extends \moodleform {

    /**
     * @var generatoroptions
     */
    protected $generatoroptions;

    /**
     * Get reference to database
     * @return \moodle_database
     */
    protected function db() {
        global $DB;
        return $DB;
    }

    /**
     * form definition
     */
    public function definition() {
        $mform = & $this->_form;

        list($this->generatoroptions) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('heading:coursegroupingandvars', 'block_coupon'));

        // Select grouping.
        $fields = 'id, CONCAT(name, \' (\', idnumber, \')\') as name';
        $options = $this->db()->get_records_menu('block_coupon_coursegroupings', [], 'name ASC', $fields);
        $mform->addElement('select', 'coursegroupingid', get_string('coursegrouping', 'block_coupon'), $options);

        // Coupon logo selection.
        \block_coupon\logostorage::add_select_form_elements($mform);

        // Add custom batchid.
        $mform->addElement('text', 'batchid', get_string('label:batchid', 'block_coupon'));
        $mform->setType('batchid', PARAM_TEXT);
        $mform->addHelpButton('batchid', 'label:batchid', 'block_coupon');

        // Select role(s).
        $roles = helper::get_role_menu(null, true);
        $attributes = [];
        // Role id.
        $selectrole = &$mform->addElement('select', 'coupon_role',
                get_string('label:coupon_role', 'block_coupon'), $roles, $attributes);
        $mform->setDefault('coupon_role', helper::get_default_coupon_role()->id);
        $mform->addHelpButton('coupon_role', 'label:coupon_role', 'block_coupon');

        // Configurable enrolment time.
        $mform->addElement('duration', 'enrolment_period',
                get_string('label:enrolment_period', 'block_coupon'), array('size' => 40, 'optional' => true));
        $mform->setDefault('enrolment_period', '0');
        $mform->addHelpButton('enrolment_period', 'label:enrolment_period', 'block_coupon');

        $this->add_action_buttons(true, get_string('button:next', 'block_coupon'));
    }

    /**
     * Validate input
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;
        // Make sure batch id is unique if provided.
        $err = parent::validation($data, $files);
        if (!empty($data['batchid']) && $DB->record_exists('block_coupon', ['batchid' => $data['batchid']])) {
            $err['batchid'] = get_string('err:batchid', 'block_coupon');
        }
        return $err;
    }

}
