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

$enrolid      	= optional_param('enrolid',0,PARAM_INT);
// $roleid       	= optional_param('roleid', -1, PARAM_INT);
$force 			= optional_param('execute',0,PARAM_INT);
$download 		= optional_param('download',0,PARAM_INT);
$courseid 		= optional_param('courseid',0,PARAM_INT);

if ($courseid > 0) { // look up using a course

	$course 		= $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
	$instance 		= $DB->get_record('enrol', array('enrol' => 'token', 'courseid' => $course->id), '*', MUST_EXIST);
	$enrolid 		= $instance->id;

} else if ($enrolid > 0) { // look up using an enrolment instance

	$instance 		= $DB->get_record('enrol', array('id'=>$enrolid, 'enrol'=>'token'), '*', MUST_EXIST);
	$course 		= $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);

} else { // unsupported use

	throw new moodle_exception('invalidaction');

}
$context 		= context_course::instance($course->id, MUST_EXIST);

require_login($course);

$canconfigure 	= has_capability('enrol/token:config', $context);
$canmanage 		= has_capability('enrol/token:manage', $context);

$feedback		= '';

$edit = optional_param('edit', false, PARAM_BOOL);
$token = optional_param('token', '', PARAM_TEXT);
$editing = ($edit && confirm_sesskey());

if (!$canconfigure and !$canmanage) {
    // No need to invent new error strings here...
    require_capability('enrol/token:config', $context);
    require_capability('enrol/token:manage', $context);
}

// if ($roleid < 0) {
//     $roleid = $instance->roleid;
// }
$roles = get_assignable_roles($context);
$roles = array('0'=>get_string('none')) + $roles;

// if (!isset($roles[$roleid])) {
//     // Weird - security always first!
//     $roleid = 0;
// }

if (!$enrol_token = enrol_get_plugin('token')) {
    throw new coding_exception('Can not instantiate enrol_token');
}

$instancename = $enrol_token->get_instance_name($instance);

$PAGE->set_url('/enrol/token/manage.php', array('enrolid'=>$instance->id));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($enrol_token->get_instance_name($instance));
$PAGE->set_heading($course->fullname);

// so when you cancel you go back to the enrolment instances screen
navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id'=>$instance->id)));

// initialise the form using the enrolid so it works after postback
$url = new moodle_url('/enrol/token/manage.php', ['enrolid' => $enrolid]);
$form = new view_enrol_token_usage_form($url);

if ($editing) {
	$form->set_data(['token' => $token]);
	$token_row = $DB->get_record('enrol_token_tokens', array('id'=>$token), '*', MUST_EXIST);
	$editform = new modify_token_form(null, [
		'enrolid'=>$enrolid,
		'token'=>$token,
		'seats'=>$token_row->numseats,
		'remaining'=>$token_row->seatsavailable,
		'expires'=>$token_row->timeexpire,
	]);

	if ($editform->is_cancelled()) {
		$url = new moodle_url('/enrol/token/manage.php', ['enrolid' => $enrolid]);
		redirect($url);

	} else if (($data = $editform->get_data()) !== null) {

		$token_row->numseats = $data->seats;
		$token_row->seatsavailable = $data->available;
		$token_row->timeexpire = $data->expires ?: 0;
		//var_dump($data,$token_row);exit;
		$DB->update_record('enrol_token_tokens', $token_row);
		$editing = false;

	}
}


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

// download a spreadsheet of the report data (excel file)
if ($download === 1) {

	require_once($CFG->libdir . '/excellib.class.php');

	// generate the report data based on the existing paramaeters
	$data = enrol_token_manager_find_tokens($instance, $token, false);
	$reportdata = enrol_token_format_data_for_report($data);

	// render the report to a html table
	$renderer = $PAGE->get_renderer('enrol_token');
	$report = new \enrol_token\output\export($reportdata);
	$table = $renderer->render_export($report);

	// create an excel spreadsheet
	$objOutput = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$table = "<!doctype html><html><body>{$table}</body></html>";

	// save the html to a temp file
	$tmpfile = tempnam(sys_get_temp_dir(), 'html');
	file_put_contents($tmpfile, $table);

	// read the html back in as a sheet (table conversion happens automatically when loading html)
	$excelHTMLReader = new \PhpOffice\PhpSpreadsheet\Reader\Html;
	$excelHTMLReader->loadIntoExisting($tmpfile, $objOutput);
	unlink($tmpfile);

    $objOutput->setActiveSheetIndex(0);

    // send to browser as an attachment
	$filename = clean_filename("TokenReport_".date_format(date_create("now"),"YmdHis")).'.xls';
    header('Content-type: application/excel');
    header("Content-Disposition:attachment;filename={$filename}");

    // send to php output stream directly
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objOutput);
    $writer->save('php://output');

    exit(0);

}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('view_token_usage', 'enrol_token'));
echo html_writer::tag('p', "Course: {$course->fullname}, Enrolment instance: {$instancename}");

if (!empty($feedback)) echo $feedback;

if ($editing) {

	$editform->display();

} else {

	$form->display();

	if (($data = $form->get_data()) !== null || $force === 1) {

		if (is_null($data)) $data = new stdClass();
		if ($force === 1) $data->token = '*';

		$records = enrol_token_manager_find_tokens($instance, $data->token);

		if (count($records) === 0) {
			echo $OUTPUT->error_text('No records');
		} else {
			$table = new html_table();
			$table->downloadable = true;
			$table->id = 'viewtokenusage';
			$table->head = [
				get_string('manage_token_header_token','enrol_token'),
				get_string('manage_token_header_cohort','enrol_token'),
				get_string('manage_token_header_seatsremaining','enrol_token'),
				get_string('manage_token_header_createdby','enrol_token'),
				get_string('manage_token_header_datecreated','enrol_token'),
				get_string('manage_token_header_dateexpires','enrol_token'),
				get_string('manage_token_header_usedby','enrol_token'),
				// get_string('manage_token_header_dateused','enrol_token'),
				get_string('manage_token_header_action','enrol_token'),
				get_string('manage_token_header_revoke','enrol_token')];
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

			foreach ($records as $record) {

				// var_dump($record);

				$url = new \moodle_url('/cohort/assign.php', array('id' => $record->cohortid, 'returnurl' => '%2Fcohort%2Findex.php%3Fpage%3D0'));
				$cohort = \html_writer::link($url, $record->cohort);

				$rec = $DB->get_record('user', array('id' => $record->createdby));
				$url = new \moodle_url('/user/view.php', array('id' => $record->createdby, 'course' => $course->id));
				$usercreated = \html_writer::link($url, fullname($rec));
				$usedby = enrol_token_get_token_users_list($record->token, $record->courseid);
				// $dateused = $record->timeused;

				$datecreated = userdate($record->created);
				$dateexpires = (intval($record->expires) > 0) ? userdate($record->expires) : '-';

				// if (!is_null($usedby)) {
				// 	$rec = $DB->get_record('user', array('id' => $record->usedby));
				// 	$url = new \moodle_url('/user/view.php', array('id' => $record->usedby, 'course' => $course->id));
				// 	$usedby = \html_writer::link($url, fullname($rec));
				// }

				// if (!is_null($dateused)) {
				// 	$dateused = userdate($dateused);
				// }

				$action = html_writer::link(
					new \moodle_url('/enrol/token/manage.php', array('enrolid' => $enrolid, 'token' => $record->token, 'edit' => true, 'sesskey' => sesskey())),
					get_string('manage_token_action_edit', 'enrol_token')
				);

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
					// $dateused,
					$action,
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

			echo html_writer::link(new moodle_url('/enrol/token/manage.php', ['enrolid' => $enrolid, 'execute' => 1, 'download' => 1]), html_writer::tag('button', get_string('downloadexcel'), ['class' => 'btn btn-secondary']));

		}
	}
}

echo $OUTPUT->footer();
/* ------------------------------------------------------------------------------------- */


