<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

use block_rss_client\output\item;
use core\context\user;
use core_customfield\category;
use local_course_recommendations_ws\external\get_recommendations;

/**
 * Block course_recommendations is defined here.
 *
 * @package     block_course_recommendations
 * @copyright   2024 HPS eAcademy
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_course_recommendations extends block_base
{

    /**
     * Initializes class member variables.
     */
    public function init()
    {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_course_recommendations');
    }

    public function hide_header()
    {
        return true;
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content()
    {

        global $CFG, $USER, $DB, $OUTPUT, $PAGE;

        $PAGE->requires->css('/blocks/course_recommendations/css/owl.carousel.css');
        $PAGE->requires->css('/blocks/course_recommendations/css/owl.theme.default.css');
        $PAGE->requires->js('/blocks/course_recommendations/js/jquery.min.js');
        $PAGE->requires->js('/blocks/course_recommendations/js/owl.carousel.min.js');
        $PAGE->requires->js('/blocks/course_recommendations/js/main.js');

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $recommendations = get_recommendations::execute();
        print_r($recommendations);

        // Get the categoryid from the url
        $category_id = optional_param('categoryid', 0, PARAM_INT);

        // Show courses under each subcategory
        if ($category_id) {
            // Get the top category
            $category = core_course_category::get($category_id);

            // Get the categories under the top category
            $categories = $category->get_children();

            // Get the title of the block
            $text = html_writer::tag('h3', get_string('pluginname', 'block_course_recommendations'));
            $text .= html_writer::empty_tag('br');

            // Create a container and a carousel
            $text .= html_writer::start_div('container');
            $text .= html_writer::start_div('course-recommendations owl-carousel owl-theme owl-loaded owl-drag');
            $text .= html_writer::start_div('owl-stage-outer');
            $text .= html_writer::start_div('owl-stage');

            foreach ($categories as $category) {
                // Get the subcategories of the category
                $subcategories = $category->get_children();

                foreach ($subcategories as $subcategory) {
                    // Get the courses under the subcategory
                    $courses = $subcategory->get_courses();

                    // Get top 3 courses
                    $courses = array_slice($courses, 0, 3);

                    // Loop through the courses and create the owl-item
                    foreach ($courses as $course) {
                        $text .= html_writer::start_div('owl-item');
                        $text .= html_writer::start_div('single-courses-box');

                            $text .= html_writer::start_div('image');
                                $text .= html_writer::empty_tag('img', ['class' => 'img-whp', 'src' => (new moodle_url('/theme/edash/pix/category.jpg'))->out(), 'alt' => 'image']);
                                $text .= html_writer::link(new moodle_url('/course/view.php', ['id' => $course->id]), "", ['class' => 'link-btn']);
                            $text .= html_writer::end_div();

                            $text .= html_writer::start_div('content');

                                $text .= html_writer::start_tag('h3');
                                    $text .= html_writer::link(new moodle_url('/course/view.php', ['id' => $course->id]), $course->get_formatted_fullname());
                                $text .= html_writer::end_tag('h3');

                                $text .= html_writer::tag('span', 'Modified ' . userdate($course->timemodified, '%d %B %Y'), ['class' => 'author']);
                                
                                $text .= html_writer::start_div('price');
                                    $text .= html_writer::tag('span', $category->get_formatted_name(), ['class' => 'new-price']);
                                $text .= html_writer::end_div();

                            $text .= html_writer::end_div();

                        $text .= html_writer::end_div();
                        $text .= html_writer::end_div();
                    }
                }
            }

            $text .= html_writer::end_div();
            $text .= html_writer::end_div();
            $text .= html_writer::end_div();
            $text .= html_writer::end_div();

            $this->content->text = $text;
            return $this->content;
        }

        if (!empty($this->config->text)) {
            $this->content->text = $this->config->text;
        } else {
            $text = 'Please define the content text in /blocks/course_recommendations/block_course_recommendations.php.';
            $this->content->text = $text;
        }

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediately after init().
     */
    public function specialization()
    {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_course_recommendations');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    public function has_config()
    {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats()
    {
        return array(
            'course' => true,
        );
    }

    /**
     * Returns the role that best describes the featured course categories block.
     * 
     * @return string
     */
    public function get_aria_role()
    {
        return 'navigation';
    }

    function _self_test()
    {
        return true;
    }
}
