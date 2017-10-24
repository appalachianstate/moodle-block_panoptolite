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
     * block_panoptolite
     *
     * @author      Fred Woolard <woolardfa@appstate.edu>
     * @copyright   (c) 2017 Appalachian State Universtiy, Boone, NC
     * @license     GNU General Public License version 3
     * @package     block_panoptolite
     */

    namespace block_panoptolite;

    use core\output\notification;
    use external_api;
    use external_function_parameters;
    use external_value;
    use Exception;

    defined('MOODLE_INTERNAL') || die();

    require_once("{$CFG->libdir}/externallib.php");



    class external extends external_api
    {


        /**
         * Specify input params for get_content_text
         *
         * @return external_function_parameters
         */
        public static function get_content_text_parameters()
        {
            return new external_function_parameters(
                array('bid' => new external_value(PARAM_INT, "Block instance id value.", NULL_NOT_ALLOWED))
            );
        }


        /**
         * Specify return type for get_content_text
         *
         * @return external_value
         */
        public static function get_content_text_returns()
        {
            return new external_value(PARAM_RAW, "Block content text (markup)");
        }


        /**
         * Get block content text for specified block instance id
         *
         * @param int $bid Block instance id
         * @throws Exception
         * @return string
         */
        public static function get_content_text($bid)
        {
            global $DB, $OUTPUT, $PAGE, $USER;


            try {

                // Get the db record for the block instance
                $blockinstance = $DB->get_record('block_instances', array('id' => $bid, 'blockname' => 'panoptolite'));
                if (!$blockinstance) {
                    throw new Exception(get_string('invalidblockinstance', 'error', get_string('pluginname', 'block_panoptolite')));
                }

                // Instantiate a block_instance object so we can access
                // its config and rendering methods
                $block = block_instance('panoptolite', $blockinstance, $PAGE);

                // Make sure user can view this instance of the block
                $parentcontext = $block->context->get_parent_context();
                if ($parentcontext->contextlevel == CONTEXT_USER) {
                    if ($USER->id !== $parentcontext->instanceid) {
                        throw new Exception(get_string('invalidblockinstance', 'error', get_string('pluginname', 'block_panoptolite')));
                    }
                } else {
                    require_login($parentcontext->instanceid, false);
                }

                $PAGE->set_context($block->context->get_parent_context());
                // Add param to page URL so block renderer will refresh
                // content... mimic the non-js link
                $url = $PAGE->url;
                $url->param("bid-{$bid}-refresh", 1);
                $PAGE->set_url($url);

                return $block->get_content()->text;

            }
            catch (Exception $e) {
                return $OUTPUT->notification($e->getMessage(), notification::NOTIFY_ERROR);
            }

        }

    }
