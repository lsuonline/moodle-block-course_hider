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
 * Course Hider Tool
 *
 * @package   block_course_hider
 * @copyright 2008 onwards Louisiana State University
 * @copyright 2008 onwards David Lowe
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_course_hider\controllers;

// use block_course_hider\persistents\course_hider;

class form_controller {

    private $partial;
    /**
     * Let's process the form by getting the results and showing it.
     * @param  object - the form data.
     * @return array - list of courses.
     */
    public function process_form($params = false) {
        global $DB;

        // Check raw input field and use if there's stuff.
        if ($params->raw_input != "") {
            // Store the partial for later use.
            $this->partial = $params->raw_input;
            $snippet = "SELECT * FROM {course} WHERE visible='1' AND ".
                "shortname LIKE '".$params->raw_input." %'";
        } else {

            $showhidden = (isset($params->hiddenonly) && $params->hiddenonly == 1) ? '0' : '1';

            $years = \course_hider_helpers::getYears()[$params->ch_years] . " ";
            $semester = \course_hider_helpers::getSemester()[$params->ch_semester];
            $semtype = "";
            $section = "";
            
            if ($params->ch_semester_type != "0") {
                $semtype = \course_hider_helpers::getSemesterType()[$params->ch_semester_type];
                $semtype .= " ";
            }
            if ($params->ch_semester_section != "0") {
                $section = " ". \course_hider_helpers::getSemesterSection()[$params->ch_semester_section];
            }

            // Store the partial for later use.
            $this->partial = $years.$semtype.$semester.$section;
            $snippet = "SELECT * FROM {course} WHERE visible=$showhidden AND ".
                "shortname LIKE '".$this->partial." %'";
        }

        $courses = $DB->get_records_sql($snippet);

        return $courses;
    }

    /**
     * Execute the form to make the courses either hidden or visible.
     * @param  array - list of courses to process.
     * @param  array - the form data.
     * @return null
     */
    public function execute_hider($courses = array(), $fdata = array()) {
        global $DB, $CFG;
        $updatecount = 0;
        $time_start = microtime(true);
        
        if (isset($fdata->hiddenonly) && $fdata->hiddenonly == 1) {
            // Execute on the hidden courses and make them visible.
            $showhidden = '1';
            $hiddentext = "visible";
        } else {
            // Execute on the visible courses and make them hidden.
            $showhidden = '0';
            $hiddentext = "hidden";
        }

        foreach($courses as $course) {
            $dataobject = [
                'id' => $course->id,
                'visible' => $showhidden,
            ];
            // Update the course to be hidden.
            $result = $DB->update_record('course', $dataobject, $bulk = false);
            $updatecount++;
            mtrace("Course (".$course->id. "): <a href='".$CFG->wwwroot."/course/view.php?id=".$course->id."' target='_blank'>" .$course->shortname. " </a>has been updated to be ".$hiddentext.".<br>");
        }
        $time_end = microtime(true);
        if ($updatecount == 0) {
            mtrace("<br><br>Ummmm......nothing was updated ya idiot!<br>");
        } else {
            $execution_time = $time_end - $time_start;
            mtrace("A total of ". $updatecount. " courses have been hidden and took ". number_format($execution_time, 2). " seconds.<br>");
        }
        
        mtrace("<br>--- Process Complete ---<br>");
    }    
}
