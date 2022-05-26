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
 * Token admin report - all token.
 *
 * @package    enrol_token
 * @copyright  2022 Tim St.Clair <tim.stclair@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once ($CFG->dirroot.'/enrol/token/locallib.php');
require_once ($CFG->libdir.'/adminlib.php');
require_once ($CFG->libdir . '/formslib.php');
require_once ($CFG->libdir . '/tablelib.php');

$syscontext = context_system::instance();
$PAGE->set_url('/enrol/token/report.php');
$PAGE->set_context($syscontext);

require_admin();
require_sesskey();

$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('admin_token_report', 'enrol_token'));
$PAGE->set_heading(get_string('admin_token_report', 'enrol_token'));

navigation_node::override_active_url(new moodle_url('/admin/settings.php', array('section'=>'enrolsettingstoken')));

$download = optional_param('download', 0, PARAM_INT);

if (!$enrol_token = enrol_get_plugin('token')) {
    throw new coding_exception('Can not instantiate enrol_token');
}

$data = enrol_token_manager_find_tokens(0, '*');
$reportdata = enrol_token_format_data_for_report($data);

// render the report to a html table
$renderer = $PAGE->get_renderer('enrol_token');
$report = new \enrol_token\output\export($reportdata);
$table = $renderer->render_export($report);

// download a spreadsheet of the report data (excel file)
if ($download === 1) {

	require_once($CFG->libdir . '/excellib.class.php');

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
echo $OUTPUT->heading(get_string('admin_token_report', 'enrol_token'));

$button = new single_button(new moodle_url('/enrol/token/report.php', array('download'=>1)), get_string('downloadexcel'));
echo $OUTPUT->render($button);
echo str_replace('<table>', '<table class="generaltable">', $table);
echo $OUTPUT->footer();

/* ------------------------------------------------------------------------------------- */


