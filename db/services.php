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

    defined('MOODLE_INTERNAL') || die();



    $functions = array(
        'block_panoptolite_get_content_text' => array(
            'classname'     => 'block_panoptolite\external',
            'methodname'    => 'get_content_text',
            'classpath'     => '',
            'description'   => 'Refresh the block content text from an AJAX call.',
            'type'          => 'read',
            'ajax'          => true,
            'services'      => 'PanoptoLite')
    );

    $services = array(
        'PanoptoLite' => array(
            'functions' => array('block_panoptolite_get_content_text'),
            'requiredcapability' => '',
            'restrictedusers' => 0,
            'enabled' => 1)
    );
