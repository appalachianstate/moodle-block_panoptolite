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

define(['jquery', 'core/tree', 'core/ajax'], function($, Tree, ajax) {

    return {

        init: function(blockinstanceid) {

            var module = this;
            var list = $('#bid_' + blockinstanceid + '_list');

            if (list.attr('data-loaded') == 'false') {
                // If initial markup not loaded then fetch it
                module.load(blockinstanceid);
            } else {
                // Fix up tree mechanics
                new Tree(".block_panoptolite .block_tree");
                // Fix up recordings click handlers
                $(".block_panoptolite .block_tree.list a").on('click', function(e) {
                    e.preventDefault(); e.stopPropagation();
                    $('#bid_' + blockinstanceid + '_form').attr('action', e.currentTarget.href).submit();
                });
            }
            // Fix up refresh click handler
            $('#inst' + blockinstanceid + ' .footer a').on('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                module.load(blockinstanceid, true);
            });

        }, // init

        load: function(blockinstanceid) {

            var list = $('#bid_' + blockinstanceid + '_list');
            if (list === null) {
                return;
            }

            list.addClass('loading');
            var promise = ajax.call([
                { methodname: 'block_panoptolite_get_content_text', args: { bid: blockinstanceid } }
            ]).shift();

            promise.done(function(text) {
                // Re-fill the block content->text area
                list.html(text);
                // Old tree gone, fix up new tree
                new Tree(".block_panoptolite .block_tree");
                // Old click handlers gone, fix up new
                $(".block_panoptolite .block_tree.list a").on('click', function(e) {
                    e.preventDefault(); e.stopPropagation();
                    $('#bid_' + blockinstanceid + '_form').attr('action', e.currentTarget.href).submit();
                });
                list.removeClass('loading');
            });

        } // load

    }; // return

});
