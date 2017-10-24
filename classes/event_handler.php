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

    use \core\event\role_assigned;
    use \core\event\role_unassigned;

    defined('MOODLE_INTERNAL') || die();

    require_once(__DIR__ . '/../lib.php');



    /**
     * Event handler configured in db/events.php
     */
    class block_panoptolite_event_handler
    {

        /**
         * Handle the role_assigned event.
         *
         * @param role_assigned $event
         */
        public static function handle_role_assigned_event(role_assigned $event)
        {
            @block_panoptolite_lib::handle_role_event($event);
        }


        /**
         * Handle the role_unassigned event.
         *
         * @param role_unassigned $event
         */
        public static function handle_role_unassigned_event(role_unassigned $event)
        {
            @block_panoptolite_lib::handle_role_event($event);
        }

    }
