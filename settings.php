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



    // Included from (plugininfo)block::load_settings(). Globals
    // declared: $CFG, $USER, $DB, $OUTPUT, and $PAGE. Three arguments
    // passed into the function: $adminroot, $parentnodename, and
    // $hassiteconfig. Local scoped variables assigned:
    // $plugininfo = $this,
    // $block = $this, where $this is the plugininfo instance,
    // $ADMIN = $adminroot,
    // and $settings = new admin_settingpage() instance.

    if ($hassiteconfig) {

        $pluginname = 'block_panoptolite';

        $field = "connection";
        $adminSetting = new admin_setting_heading(
            "{$pluginname}/{$field}",
            get_string("{$field}_lbl",  $pluginname),
            get_string("{$field}_desc", $pluginname));
        $settings->add($adminSetting);
        unset($adminSetting);

        $field = "apihost";
        $adminSetting = new admin_setting_configtext(
            "{$pluginname}/{$field}",
            get_string("{$field}_lbl",  $pluginname),
            get_string("{$field}_desc", $pluginname),
            "", PARAM_HOST, 30);
        $settings->add($adminSetting);
        unset($adminSetting);

        $field = "apiinstance";
        $adminSetting = new admin_setting_configtext(
            "{$pluginname}/{$field}",
            get_string("{$field}_lbl",  $pluginname),
            get_string("{$field}_desc", $pluginname),
            "", PARAM_ALPHANUMEXT,30);
        $settings->add($adminSetting);
        unset($adminSetting);

        $field = "apikey";
        $adminSetting = new admin_setting_configtext(
            "{$pluginname}/{$field}",
            get_string("{$field}_lbl",  $pluginname),
            get_string("{$field}_desc", $pluginname),
            "", PARAM_ALPHANUMEXT,30);
        $settings->add($adminSetting);
        unset($adminSetting);

        $field = "apiusername";
        $adminSetting = new admin_setting_configtext(
            "{$pluginname}/{$field}",
            get_string("{$field}_lbl",  $pluginname),
            get_string("{$field}_desc", $pluginname),
            "", PARAM_ALPHANUMEXT,30);
        $settings->add($adminSetting);
        unset($adminSetting);



        $field = "roles";
        $adminSetting = new admin_setting_heading(
            "{$pluginname}/{$field}",
            get_string("{$field}_lbl",  $pluginname),
            get_string("{$field}_desc", $pluginname));
        $settings->add($adminSetting);
        unset($adminSetting);

        $roles = get_assignable_roles(context_course::instance(SITEID));

        $field = "creatorrolemap";
        $adminSetting = new admin_setting_configmultiselect(
            "{$pluginname}/{$field}",
            get_string("{$field}_lbl",  $pluginname),
            get_string("{$field}_desc", $pluginname),
            null, $roles);
        $settings->add($adminSetting);



        $field = "ajax";
        $adminSetting = new admin_setting_heading(
            "{$pluginname}/{$field}",
            get_string("{$field}_lbl",  $pluginname),
            get_string("{$field}_desc", $pluginname));
        $settings->add($adminSetting);
        unset($adminSetting);

        $field = "ajaxfirstload";
        $adminSetting = new admin_setting_configselect(
            "{$pluginname}/{$field}",
            get_string("{$field}_lbl",  $pluginname),
            get_string("{$field}_desc", $pluginname),
            "1", array('0' => get_string('no'), '1' => get_string('yes')));
        $settings->add($adminSetting);
        unset($adminSetting);

    }
