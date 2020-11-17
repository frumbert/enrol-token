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
 * Adds new instance of enrol_token to specified course
 * or edits current instance.
 *
 * @package    enrol_token
 * @copyright  2020 tim st. clair <tim.stclair@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->libdir . '/formslib.php');

class enrol_token_edit_form extends moodleform
{

    function definition() {
        global $DB;

        $mform = $this->_form;

        list($instance, $plugin, $context) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname', 'enrol_token'));

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'ipthrottlingperiod', get_string('ipthrottlingperiod', 'enrol_token'));
        $mform->setType('ipthrottlingperiod', PARAM_INT);
        $mform->addHelpButton('ipthrottlingperiod', 'ipthrottlingperiod', 'enrol_token');

        $mform->addElement('text', 'userthrottlingperiod', get_string('userthrottlingperiod', 'enrol_token'));
        $mform->setType('userthrottlingperiod', PARAM_INT);
        $mform->addHelpButton('userthrottlingperiod', 'userthrottlingperiod', 'enrol_token');

        $options = array(ENROL_INSTANCE_ENABLED => get_string('yes'), ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_token'), $options);
        $mform->addHelpButton('status', 'status', 'enrol_token');

        $options = array(1 => get_string('yes'), 0 => get_string('no'));
        $mform->addElement('select', 'customint6', get_string('newenrols', 'enrol_token'), $options);
        $mform->addHelpButton('customint6', 'newenrols', 'enrol_token');
        $mform->disabledIf('customint6', 'status', 'eq', ENROL_INSTANCE_DISABLED);

        $roles = $this->extend_assignable_roles($context, $instance->roleid, 5);
        $mform->setDefault('roleid', 0);
        $mform->addElement('select', 'roleid', get_string('role', 'enrol_token'), $roles);

        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_token'), array('optional' => true, 'defaultunit' => 86400));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_token');

        $options = array(0 => get_string('no'), 1 => get_string('expirynotifyenroller', 'core_enrol'), 2 => get_string('expirynotifyall', 'core_enrol'));
        $mform->addElement('select', 'expirynotify', get_string('expirynotify', 'core_enrol'), $options);
        $mform->addHelpButton('expirynotify', 'expirynotify', 'core_enrol');

        $mform->addElement('duration', 'expirythreshold', get_string('expirythreshold', 'core_enrol'), array('optional' => false, 'defaultunit' => 86400));
        $mform->addHelpButton('expirythreshold', 'expirythreshold', 'core_enrol');
        $mform->disabledIf('expirythreshold', 'expirynotify', 'eq', 0);

        $mform->addElement('date_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_token'), array('optional' => true));
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_token');

        $mform->addElement('date_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_token'), array('optional' => true));
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_token');

        $options = array(0 => get_string('never'), 1800 * 3600 * 24 => get_string('numdays', '', 1800), 1000 * 3600 * 24 => get_string('numdays', '', 1000), 365 * 3600 * 24 => get_string('numdays', '', 365), 180 * 3600 * 24 => get_string('numdays', '', 180), 150 * 3600 * 24 => get_string('numdays', '', 150), 120 * 3600 * 24 => get_string('numdays', '', 120), 90 * 3600 * 24 => get_string('numdays', '', 90), 60 * 3600 * 24 => get_string('numdays', '', 60), 30 * 3600 * 24 => get_string('numdays', '', 30), 21 * 3600 * 24 => get_string('numdays', '', 21), 14 * 3600 * 24 => get_string('numdays', '', 14), 7 * 3600 * 24 => get_string('numdays', '', 7));
        $mform->addElement('select', 'customint2', get_string('longtimenosee', 'enrol_token'), $options);
        $mform->addHelpButton('customint2', 'longtimenosee', 'enrol_token');

        $mform->addElement('advcheckbox', 'customint4', get_string('sendcoursewelcomemessage', 'enrol_token'));
        $mform->addHelpButton('customint4', 'sendcoursewelcomemessage', 'enrol_token');

        $mform->addElement('textarea', 'customtext1', get_string('customwelcomemessage', 'enrol_token'), array('cols' => '60', 'rows' => '8'));
        $mform->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_token');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        list($instance, $plugin, $context) = $this->_customdata;

        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_token');
            }
        }

        if ($data['expirynotify'] > 0 and $data['expirythreshold'] < 86400) {
            $errors['expirythreshold'] = get_string('errorthresholdlow', 'core_enrol');
        }

        return $errors;
    }

    /**
     * Gets a list of roles that this user can assign for the course as the default for token-enrolment.
     *
     * @param context $context the context.
     * @param integer $defaultrole the id of the role that is set as the default for token-enrolment
     * @return array index is the role id, value is the role name
     */
    function extend_assignable_roles($context, $defaultrole) {
        global $DB;

        $roles = get_assignable_roles($context, ROLENAME_BOTH);
        if (!isset($roles[$defaultrole])) {
            if ($role = $DB->get_record('role', array('id' => $defaultrole))) {
                $roles[$defaultrole] = role_get_name($role, $context, ROLENAME_BOTH);
            }
        }
        return $roles;
    }
}
