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
 * token enrol plugin implementation.
 *
 * @package    enrol_token
 * @copyright  2020 tim st. clair <tim.stclair@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once ("$CFG->libdir/formslib.php");

// used on the manage.php page
class view_enrol_token_usage_form extends moodleform
{
    function definition() {
        global $DB;

        $mform = $this->_form;

        // filters
        $mform->addElement('header', 'filter', get_string('filter'));

        $mform->addElement('text', 'token', get_string('promptfiltertoken', 'enrol_token'), 'maxlength="12" size="12"');
        $mform->setDefault('token','*');
        $mform->addHelpButton('token','promptfiltertoken','enrol_token');
        $mform->setType('token', PARAM_TEXT);

        // buttons
        $this->add_action_buttons(true, get_string('viewusers', 'enrol_token'));
    }

    function definition_after_data() {
        $mform = $this->_form;
        $mform->applyFilter('token', 'trim');
    }
}


class enrol_token_enrol_form extends moodleform
{
    protected $instance;

    /**
     * Overriding this function to get unique form id for multiple token enrolments.
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->id . '_' . get_class($this);
        return $formid;
    }

    public function definition() {
        $mform = $this->_form;
        $instance = $this->_customdata;
        $this->instance = $instance;

        $mform->addElement('html', '<div id="tokenenrolarea">');

        $mform->addElement('html', '<h3>' . get_string('enrol_header', 'enrol_token') . '</h3>');
        $mform->addElement('html', '<p>' . get_string('tokeninput', 'enrol_token') . '</p>');

        $mform->addElement('text', 'enroltoken', get_string('enrol_label', 'enrol_token'), array('id' => 'enroltoken_' . $instance->id));
        $mform->setType('enroltoken', PARAM_ALPHANUMEXT);

        $mform->addElement('submit', 'submitbutton', get_string('enrolme', 'enrol_token'));

        $mform->addElement('html', '</div>');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);
    }

    public function validation($data, $files) {
        return parent::validation($data, $files);
    }

    public function setElementError($element, $msg) {
        $this->_form->setElementError($element, $msg);
    }
}


// used on the create.php page
class create_enrol_tokens_form extends moodleform
{
    function definition() {
        global $USER;
        $mform = $this->_form;

        // course
        $courses = array();
        $dbcourses = get_courses();
        foreach ($dbcourses as $dbcourseid => $dbcourse) {
             if ($dbcourse->id > 1) { // 1 = system course
                $courses[$dbcourseid] = $dbcourse->fullname;
             }
        } var_dump($this->_customdata);

        // cohorts
        $context = context_system::instance();
        $cohorts = array();
        $dbcohorts = cohort_get_cohorts($context->id);
        foreach ($dbcohorts['cohorts'] as $dbcohort) $cohorts[$dbcohort->id] = $dbcohort->name;

        // cohort selection
        $mform->addElement('header', 'cohorts', get_string('create_cohort_header', 'enrol_token'));

        $mform->addElement('static', '', get_string('create_cohort_help', 'enrol_token'), '');
        $mform->addElement('select', 'cohortexisting', get_string('create_cohort_select', 'enrol_token'), $cohorts);
        $mform->addElement('static', '', '', 'OR');
        $mform->addElement('text', 'cohortnew', get_string('create_cohort_new', 'enrol_token'), 'maxlength="253" size="25"');
        $mform->setType('cohortnew', PARAM_CLEANHTML);

        // token parameters
        $mform->addElement('header', 'tokens', get_string('create_token_header', 'enrol_token'));

        $mform->addElement('text', 'tokennumber', get_string('create_token_count', 'enrol_token'), 'maxlength="4" size="5"');
        $mform->addHelpButton('tokennumber', 'create_token_count', 'enrol_token');
        $mform->setDefault('tokennumber', 1);
        $mform->setType('tokennumber', PARAM_INT);
        $mform->addRule('tokennumber', 'Enter a number between 1 and 1000', 'regex', '/^0*(?:[1-9][0-9][0-9]?|[1-9]|1000)$/', 'client', false, false );

        $mform->addElement('text', 'seatspertoken', get_string('create_token_seats', 'enrol_token'), 'maxlength="4" size="5"');
        $mform->addHelpButton('seatspertoken', 'create_token_seats', 'enrol_token');
        $mform->setDefault('seatspertoken', 1);
        $mform->setType('seatspertoken', PARAM_INT);
        $mform->addRule('seatspertoken', 'Enter a number between 1 and 1000', 'regex', '/^0*(?:[1-9][0-9][0-9]?|[1-9]|1000)$/', 'client', false, false );

        $mform->addElement('text', 'prefix', get_string('create_token_prefix', 'enrol_token'), 'maxlength="8" size="8"');
        $mform->addHelpButton('prefix', 'create_token_prefix', 'enrol_token');
        $mform->setType('prefix', PARAM_ALPHANUMEXT);

        $mform->addElement('date_selector', 'expirydate', get_string('create_token_expiry', 'enrol_token'), array('optional' => true));

        $mform->addElement('text', 'emailaddress', get_string('create_token_email', 'enrol_token'), 'size="50"');
        $mform->setType('emailaddress', PARAM_EMAIL);
        $mform->setDefault('emailaddress', $USER->email);
        $mform->addElement('text', 'mailsubject', get_string('create_token_email_subject', 'enrol_token'), 'size="50"');
        $mform->setDefault('mailsubject', get_string('create_token_email_subject_default', 'enrol_token', $this->_customdata));
        $mform->setType('mailsubject', PARAM_TEXT);
        $mform->addElement('editor', 'mailbody', get_string('create_token_email_body', 'enrol_token'), null, ['autosave' => false])->setValue(['text' => get_string('create_token_email_body_default', 'enrol_token')]);
        $mform->setType('mailbody', PARAM_RAW);
        $mform->addHelpButton('mailbody', 'create_token_email_body', 'enrol_token');

        // buttons
        $this->add_action_buttons(true, get_string('create_token_submit', 'enrol_token'));
    }

    function definition_after_data() {
        $mform = $this->_form;
        $mform->applyFilter('cohortnew', 'trim');
        $mform->applyFilter('emailaddress', 'trim');
        $mform->applyFilter('prefix', 'trim');
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // seats per token
        if (($data['seatspertoken'] < 1) || ($data['seatspertoken'] > 10000)) $errors['seatspertoken'] = get_string('seatsoutofrange', 'enrol_token');

        // number of token
        if (($data['tokennumber'] < 1) || ($data['tokennumber'] > 10000)) $errors['tokennumber'] = get_string('tokensoutofrange', 'enrol_token');

        // email address
        if ((isset($data['emailaddress']) === true) && (trim($data['emailaddress'] != ''))) {
            if (validate_email($data['emailaddress']) === false) $errors['emailaddress'] = get_string('invalidemail');
        }

        return $errors;
    }
}

function appendSqlWhereClause(&$existingClause, $newClause) {
    $existingClause.= (($existingClause != '') ? ' AND ' : '') . $newClause;
}

function enrol_token_manager_contains_naughty_words($token) {
    global $CFG;

    // setup naughty words filter
    static $badwords = '-';
    if ($badwords == '-') {
        $badwords = (empty($CFG->filter_censor_badwords)) ? explode(',', get_string('badwords', 'filter_censor')) : explode(',', $CFG->filter_censor_badwords);
        foreach ($badwords as &$badword) $badword = trim($badword);
    }

    // see if any naughty words exist in the token
    foreach ($badwords as $badword) {
        if (stripos($token, $badword) !== false) return true;
    }

    return false;
}

// generate a number of string, prefixed with $prefix, that do not already exist in the database or contain filtered words
function enrol_token_manager_generate_token_data($tokennumber, $prefix) {
    global $DB;
    $tokens = [];
    $characters = '23456789abcdefghiknpqrstuwxyzABCDFGHJKLMPQRSTVXYZ'; // skip confusing letters
    $len_characters = strlen($characters);

    if (strlen($prefix) > 4) $prefix = substr($dprefix, 0, 4);
    for ($count = 0; ($count < $tokennumber); ++$count) {
        for ($goodToken = false; ($goodToken === false); /* empty */ ) {
            $goodToken = false;
            $token = $prefix;
            while (strlen($token) < 15) $token.= $characters[rand(0, $len_characters - 1)];
            if (enrol_token_manager_contains_naughty_words($token) === false) {
                if ($DB->count_records('enrol_token_tokens', array('id' => $token)) === 0) {
                    $goodToken = true;
                }
            }
        }
        $tokens[] = $token;
    }
    return $tokens;
}

function enrol_token_manager_create_cohort_id($cohort_name, $cohort_idnumber) {
    global $DB;
    $context = context_system::instance();
    $cohort = new stdClass();
    $cohort->contextid = $context->id;
    $cohort->name = $cohort_name;
    $cohort->idnumber = $cohort_idnumber;
    $cohort->description = 'Created by enrol_token_manager';
    $cohort->descriptionformat = 1;
    $cohort->component = '';
    $cohort->timecreated = time();
    $cohort->timemodified = $cohort->timecreated;
    $cohortid = $DB->insert_record('cohort', $cohort);
    return $cohortid;
}

function enrol_token_manager_insert_tokens($cohort_id, $course_id, $tokens, $places_per_seat, $expirydate) {
    global $DB, $USER;
    $expiry_date = ($expirydate == 0) ? 0 : ($expirydate + (24 * 60 * 60)); // date specified is inclusive
    if (($transaction = $DB->start_delegated_transaction()) === null) throw new coding_exception('Invalid delegated transaction object');
    try {
        foreach ($tokens as $token) {
            $tokenRec = new stdClass();
            $tokenRec->id = $token;
            $tokenRec->cohortid = $cohort_id;
            $tokenRec->courseid = $course_id;
            $tokenRec->numseats = $places_per_seat;
            $tokenRec->seatsavailable = $places_per_seat;
            $tokenRec->createdby = $USER->id;
            $tokenRec->timecreated = time();
            $tokenRec->timeexpire = $expiry_date;
            if ($DB->insert_record_raw('enrol_token_tokens', $tokenRec, false, false, true) === false) throw new Excpetion('enrol_token_manager: token storage failed');
        }
        $transaction->allow_commit();
    } catch(Exception $e) {
        $transaction->rollback($e);
        notice("There was an error storing the generated tokens into the database. Please try again.");
        exit();
    }
}

function enrol_token_manager_create_tokens_external($course_idnumber, $num_seats, $places_per_seat, $expirydate, $prefix, $cohort_idnumber) {
    global $DB;

    // look up row id's from idnumbers
    $course_id = $DB->get_field("course", "id", array("idnumber" => $course_idnumber), MUST_EXIST);

    if (($cohort_id = $DB->get_field("cohort", "id", array("idnumber" => $cohort_idnumber), IGNORE_MISSING)) == false) {
        $cohort_id = enrol_token_manager_create_cohort_id("token_external_" . $cohort_idnumber, $cohort_idnumber);
    }

    // make an array of tokens
    $tokens = enrol_token_manager_generate_token_data($num_seats, $prefix);

    // save them into the database
    enrol_token_manager_insert_tokens($cohort_id, $course_id, $tokens, $places_per_seat, $expirydate);

    // return the tokens
    return $tokens;
}

/**
 * Finds one or more tokens and their usage details based on a filter
 * @param int $istance Enrolment instance 
 * @param string $filter like query to search for
 * @param boolean $include_row whether to populate a row-number column
 * @return $DB rows
 */
function enrol_token_manager_find_tokens($instance, $filter = '*', $include_row = true) {
    global $DB;

    // build SQL statement from given options
    $query = ['t.courseid = ?'];
    $params = [(int)$instance->courseid];
    if ($filter != '') {
        $query[] = 't.id LIKE ?';
        $params[] = str_replace(['*', '?'], ['%', '_'], $filter);
    }
    $where = "WHERE " . implode(' AND ', $query);

    // get_records_sql uses the first column as the key and discards duplicate keys ... so we have to ensure the first column is a unique value
    // see https://stackoverflow.com/a/55866244/1238884
    $fields = '
            t.id token,
            h.name cohort,
            h.id cohortid,
            t.numseats total,
            t.seatsavailable remaining,
            t.createdby createdby,
            t.timecreated created,
            t.timeexpire expires,
            l.`userid` usedby,
            l.`timecreated` timeused ';
    $from = '{cohort} h
        inner join {enrol_token_tokens} t on t.`cohortid` = h.`id`
        left outer join {enrol_token_log} l on t.id = l.`token`
    ';
    $order = 't.timecreated desc,
            l.timecreated desc';

    // the recordset's id column is text (the token key); we might need a numeric id column, so create one
    if ($include_row) {
        // this window function requires mariadb 10.2 or higher
        // https://stackoverflow.com/a/57766055/1238884
        // $fields = 'ROW_NUMBER() OVER (), ' . $fields;

        // whereas this alternate seems to be fine
        $fields = '@row_num:= @row_num + 1, ' . $fields;
        $from .= ', (select @row_num:=0 as num) as c';
    }

    return $DB->get_records_sql("SELECT {$fields} FROM {$from} {$where} ORDER BY {$order}", $params);
}