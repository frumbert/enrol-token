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
 * Token user enrolment creation.
 *
 * @package    enrol_token
 * @copyright  2020 Tim St.Clair <tim.stclair@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once ($CFG->dirroot.'/enrol/token/locallib.php');
require_once ($CFG->libdir . '/formslib.php');
require_once ($CFG->libdir . '/tablelib.php');

$enrolid      	= required_param('enrolid', PARAM_INT);
$roleid       	= optional_param('roleid', -1, PARAM_INT);
$force 			= optional_param('execute',0,PARAM_INT);
$download 		= optional_param('download',0,PARAM_INT);

$instance 		= $DB->get_record('enrol', array('id'=>$enrolid, 'enrol'=>'token'), '*', MUST_EXIST);
$course 		= $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);
$context 		= context_course::instance($course->id, MUST_EXIST);

require_login($course);

$canconfigure 	= has_capability('enrol/token:config', $context);
$canmanage 		= has_capability('enrol/token:manage', $context);

$feedback		= '';


if (!$canconfigure and !$canmanage) {
    // No need to invent new error strings here...
    require_capability('enrol/token:config', $context);
    require_capability('enrol/token:manage', $context);
}

if ($roleid < 0) {
    $roleid = $instance->roleid;
}
$roles = get_assignable_roles($context);
$roles = array('0'=>get_string('none')) + $roles;

if (!isset($roles[$roleid])) {
    // Weird - security always first!
    $roleid = 0;
}

if (!$enrol_token = enrol_get_plugin('token')) {
    throw new coding_exception('Can not instantiate enrol_token');
}

$instancename = $enrol_token->get_instance_name($instance);

$PAGE->set_url('/enrol/token/manage.php', array('enrolid'=>$instance->id));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($enrol_token->get_instance_name($instance));
$PAGE->set_heading($course->fullname);

navigation_node::override_active_url(new moodle_url('/user/index.php', array('id'=>$course->id)));

// initialise the form using the enrolid so it works after postback
$url = new moodle_url('/enrol/token/manage.php', ['enrolid' => $enrolid]);
$form = new view_enrol_token_usage_form($url);

if ($form->is_cancelled()) {
	$url = new moodle_url('/enrol/instances.php', ['id' => $course->id]);
	redirect($url);
}

// revoke tokens if need be
if ((isset($_REQUEST) === true) && (isset($_REQUEST['del']) === true)) {
	$revokeTokens = array_keys($_REQUEST['del']);
	if ($DB->delete_records_list('enrol_token_tokens', 'id', $revokeTokens) === true) {

	  $feedback = $OUTPUT->notification(get_string('tokens_revoked', 'enrol_token', (object)["count" => count($revokeTokens)]), 'notifysuccess');

	} else {

	 $feedback = $OUTPUT->error_text(get_string('tokens_revoked_error','enrol_token'));

	}
}

// a basic csv downloader of all tokens
if ($download === 1) {


	$data = enrol_token_manager_find_tokens('*', false);
	$fields = ['token','cohort','cohortid','total','remaining','createdby','created','expires','usedby','timeused'];
    $exportdata = new ArrayObject($data);
    $iterator = $exportdata->getIterator();

    $filename = clean_filename('tokens');
    \core\dataformat::download_data(
        $filename,
    	'csv',
        $fields,
	    $iterator,
    	function($exportdata) use ($fields) {
            $data = new stdClass();

            foreach ($fields as $field) {
                // Set data field's value from the export data's equivalent field by default.
                $data->$field = $exportdata->$field ?? null;
			}

            $data->created = userdate($data->created);
            $data->expires = is_null($data->expires) ? '' : userdate($data->expires);
            $data->timeused = is_null($data->timeused) ? '' : userdate($data->timeused);

			return $data;

    	}
    );
    die();

}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('view_token_usage', 'enrol_token'));
echo html_writer::tag('p', "Course: {$course->fullname}, Enrolment instance: {$instancename}");

if (!empty($feedback)) echo $feedback;

$form->display();

if (($data = $form->get_data()) !== null || $force === 1) {

	if (is_null($data)) $data = new stdClass();
	if ($force === 1) $data->token = '*';

	$data = enrol_token_manager_find_tokens($data->token);

	if (count($data) === 0) {
		echo $OUTPUT->error_text('No records');
	} else {
		$table = new html_table();
		$table->downloadable = true;
		$table->id = 'viewtokenusage';
		$table->head = [get_string('manage_token_header_token','enrol_token'),get_string('manage_token_header_cohort','enrol_token'),get_string('manage_token_header_seatsremaining','enrol_token'),get_string('manage_token_header_createdby','enrol_token'),get_string('manage_token_header_datecreated','enrol_token'),get_string('manage_token_header_dateexpires','enrol_token'),get_string('manage_token_header_usedby','enrol_token'),get_string('manage_token_header_dateused','enrol_token'),get_string('manage_token_header_revoke','enrol_token')];
		$rows = [];

		// the problem with flexible_table is that it posts back sorting informatio via http get and we are inside a postback
		// $table = new \flexible_table('enrol_token_manage');
		// $table->define_columns(['token','cohort','remaining','creator','created','usedby','usedon']);
		// $table->define_headers([get_string('manage_token_header_token','enrol_token'),get_string('manage_token_header_cohort','enrol_token'),get_string('manage_token_header_seatsremaining','enrol_token'),get_string('manage_token_header_createdby','enrol_token'),get_string('manage_token_header_datecreated','enrol_token'),get_string('manage_token_header_usedby','enrol_token'),get_string('manage_token_header_dateused','enrol_token')]);
		// $table->define_baseurl($PAGE->url);
		// $table->sortable(true);
		// $table->collapsible(true);
		// $table->no_sorting('remaining');
		// $table->no_sorting('cohort');
		// $table->setup();

		foreach ($data as $record) {

			// var_dump($record);

			$url = new \moodle_url('/cohort/assign.php', array('id' => $record->cohortid, 'returnurl' => '%2Fcohort%2Findex.php%3Fpage%3D0'));
			$cohort = \html_writer::link($url, $record->cohort);

			$rec = $DB->get_record('user', array('id' => $record->createdby));
			$url = new \moodle_url('/user/view.php', array('id' => $record->createdby, 'course' => $course->id));
			$usercreated = \html_writer::link($url, fullname($rec));
			$usedby = $record->usedby;
			$dateused = $record->timeused;

			$datecreated = userdate($record->created);
			$dateexpires = (intval($record->expires) > 0) ? userdate($record->expires) : '-';

			if (!is_null($usedby)) {
				$rec = $DB->get_record('user', array('id' => $record->usedby));
				$url = new \moodle_url('/user/view.php', array('id' => $record->usedby, 'course' => $course->id));
				$usedby = \html_writer::link($url, fullname($rec));
			}

			if (!is_null($dateused)) {
				$dateused = userdate($dateused);
			}

			$checkbox = html_writer::checkbox("del[{$record->token}]", 1, false);

			// $table->add_data([ ... ]);
			$rows[] = [
            	$record->token,
            	$cohort,
            	get_string('manage_token_aofb','enrol_token', (object)["a" => $record->remaining, "b" => $record->total]),
            	$usercreated,
            	$datecreated,
            	$dateexpires,
            	$usedby,
            	$dateused,
            	$checkbox
            ];
		}
		// $table->finish_output();
		$table->data = $rows;

		$output = html_writer::table($table);
		$output .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('revoketokens', 'enrol_token'), 'class' => 'btn btn-warning'));

		$attributes = array('method' => 'post', 'action' => new moodle_url('/enrol/token/manage.php', ["enrolid" => $enrolid]));
		echo html_writer::tag('form', $output, $attributes);

		echo html_writer::empty_tag('br');

		echo html_writer::link(new moodle_url('/enrol/token/manage.php', ['enrolid' => $enrolid, 'execute' => 1, 'download' => 1]), html_writer::tag('button', get_string('downloadtext'), ['class' => 'btn btn-secondary']));

	}
}


echo $OUTPUT->footer();
/* ------------------------------------------------------------------------------------- */


