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

/**
 * Block course_recommendations is defined here.
 *
 * @package     block_course_recommendations
 * @copyright   2024 HPS eAcademy
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_course_recommendations extends block_base {

    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_course_recommendations');
    }

    public function hide_header() {
        return true;
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {

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

        // Get the categoryid from the url
        $category_id = optional_param('categoryid', 0, PARAM_INT);

        // Show course categories count if categoryid is not null
        if ($category_id) {
            $sql = "
                SELECT cc1.id, cc1.name, cc1.timemodified,
                (SELECT COUNT(*) FROM {course_categories} cc2 WHERE cc2.parent = cc1.id) AS coursecategoriescount
                FROM {course_categories} cc1
                WHERE cc1.parent = :categoryid";
            $params = ['categoryid' => $category_id];
            $categories = $DB->get_records_sql($sql, $params);

            $data = [
                'pluginname' => get_string('pluginname', 'block_course_recommendations'),
                'categories' => []
            ];
        
            foreach ($categories as $category) {
                $data['categories'][] = [
                    'name' => $category->name,
                    'image_url' => (new moodle_url('/theme/edash/pix/category.jpg'))->out(),
                    'course_url' => (new moodle_url('/course/index.php', ['categoryid' => $category->id]))->out(),
                    'modified' => userdate($category->timemodified, '%d %B %Y', 0),
                    'coursecategoriescount' => $category->coursecategoriescount
                ];
            }
        
            $this->content->text = $OUTPUT->render_from_template('block_course_recommendations/default', $data);
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
    public function specialization() {

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
    public function has_config() {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats() {
        return array(
            'course' => true,
        );
    }

     /**
     * Returns the role that best describes the featured course categories block.
     * 
     * @return string
     */
    public function get_aria_role() {
        return 'navigation';
    }

    function _self_test() {
        return true;
    }
}
