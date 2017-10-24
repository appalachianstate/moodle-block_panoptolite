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

    require_once(__DIR__ . '/lib.php');



    /**
     * Block instance configuration extension, provides the override
     * method specific_definition(), called from block_edit_form's
     * definition() method.
     */
    class block_panoptolite_edit_form extends block_edit_form
    {

        /**
         * {@inheritDoc}
         * @see block_edit_form::specific_definition()
         */
        protected function specific_definition($mform)
        {

            // No folder selection when on a user's /my page
            if ($this->block->context->get_parent_context()->contextlevel == CONTEXT_USER) {
                return;
            }

            $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

            // If plugin not configured (sitewide), then can not set up
            // an instance configuration
            if (!block_panoptolite_lib::plugin_configured()) {
                $mform->addElement('warning', 'noconfigerror', 'error',
                    get_string('noconfigerror', 'block_panoptolite'));
                return;
            }

            // For which folders does this user have creator privilege
            $options = block_panoptolite_lib::get_folder_options_for_user(null, true);
            if (empty($this->block->config->bespokefolderid)) {
                // No bespoke folder so provide option to create one
                $options = array_merge(array('0' => get_string('newfolder', 'block_panoptolite')), $options);
            } elseif (!array_key_exists($this->block->config->bespokefolderid, $options)) {
                // If course's bespoke folder not in list for current
                // user, put it there
                $options = array_merge(array($this->block->config->bespokefolderid => $this->block->config->bespokefoldername), $options);
            } else {
                // Do have a bespoke folder, and it is in the list, put
                // it at the top
                $options = array_diff_key($options, array($this->block->config->bespokefolderid));
                $options = array($this->block->config->bespokefolderid => empty($this->block->config->bespokefoldername) ? get_string('bespokefolder', 'block_panoptolite') : $this->block->config->bespokefoldername) + $options;
            }

            $mform->addElement('select', 'config_folderid', get_string('folderid', 'block_panoptolite'), $options);
            $mform->addHelpButton('config_folderid', 'folderid', 'block_panoptolite');

            $mform->addElement('selectyesno', 'config_synconsave', get_string('synconsave', 'block_panoptolite'));
            $mform->addHelpButton('config_synconsave', 'synconsave', 'block_panoptolite');

        }

    }
