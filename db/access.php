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



    $capabilities = array(

        'block/panoptolite:addinstance' => array(
            'riskbitmask'   => RISK_SPAM | RISK_XSS,
            'captype'       => 'write',
            'contextlevel'  => CONTEXT_BLOCK,
            'archetypes'    => array(
                'editingteacher' => CAP_ALLOW,
                'manager'        => CAP_ALLOW
            ),
            'clonepermissionsfrom' => 'moodle/site:manageblocks'
        ),

        'block/panoptolite:myaddinstance' => array(
            'riskbitmask'   => RISK_SPAM | RISK_XSS,
            'captype'       => 'write',
            'contextlevel'  => CONTEXT_SYSTEM,
            'archetypes'    => array(
                'user' => CAP_ALLOW,
            ),
            'clonepermissionsfrom' => 'moodle/my:manageblocks'
        )

    );
