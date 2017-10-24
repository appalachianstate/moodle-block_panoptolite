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

    require_once(__DIR__ . '/../../config.php');
    require_once("{$CFG->libdir}/weblib.php");



    // This page is used in two cases: 1) called in response to a user
    // authenticating with Panopto at their site, after selecting which
    // identity provider to use. Panopto references the configured IdP
    // (bounce page URL) and redirects the user (GET) appending several
    // query string vars; 2) User clicked on a link here in Moodle that
    // references a Panopto resource, i.e. folder listing or recording.
    // The click event was handled by js, so instead of a GET redirect
    // a POST is made to that resource URL using a minimal form with a
    // single hidden input value containing the name of the configured
    // IdP for Panopto to use in authenticating the user. As a response
    // to the POST, Panopto does just as in case 1, redirects the user
    // here with query string vars, just the return (callback) URL is
    // different.
    // The page validates the incoming request, then redirects the user
    // back to Panopto with some query string vars, one of which is the
    // username of the currently logged in user--which means this page
    // will always need access to the PHP session where authentication
    // state ($USER) is kept between page requests.

    $servername = required_param('serverName', PARAM_HOST);
    $callbackurl = required_param('callbackURL', PARAM_URL);
    $requestauthcode = required_param('authCode', PARAM_ALPHANUM);
    $action = optional_param('action', '', PARAM_ALPHA);

    // Use raw type for expiration because float doesn't have required
    // precision, but validate with regex
    $expiration = preg_replace('/[^0-9\.]/', '', required_param('expiration', PARAM_RAW));

    if ($action == 'relogin' || (isguestuser())) {
        // Force user to reauthenticate in Moodle before responding
        // to Panopto with the username, so logout user, and redirect
        // here (same URL), except without an 'action' query str var
        require_logout();
        redirect(new moodle_url("{$CFG->wwwroot}/blocks/panoptolite/idpauth.php", array(
            'authCode' => $requestauthcode,
            'serverName' => $servername,
            'expiration' => $expiration,
            'callbackURL' => $callbackurl)));
    }

    // For use case 1, just need a site login, but do not auto-login
    // or allow guest login. For use case 2, login call will pick up
    // on the existing $USER authentication state. All we want from
    // here forward is to have a valid Moodle username.
    require_login(null, false);

    $config = get_config('block_panoptolite');

    // Verfiy the auth code passed to us by creating an auth code
    // hash with the necessary elements and compare
    $verifyauthcode = strtoupper(sha1("serverName={$servername}&expiration={$expiration}|{$config->apikey}"));
    if ($verifyauthcode != $requestauthcode) {
        print_error('invalidauthcode', 'block_panoptolite');
    }

    // Fix up the parameters to put in response that will identify user
    // logged in to the Panopto site.
    $userkey = "{$config->apiinstance}\\{$USER->username}";
    $responseauthcode = strtoupper(sha1("serverName={$servername}&externalUserKey={$userkey}&expiration={$expiration}|{$config->apikey}"));

    // Fix up the redirect URL using the callback provided as the
    // starter, then add our params to it.
    $redirecturl = new moodle_url($callbackurl);
    $redirecturl->param('serverName', $servername);
    $redirecturl->param('externalUserKey', $userkey);
    $redirecturl->param('expiration', $expiration);
    $redirecturl->param('authCode', $responseauthcode);

    redirect($redirecturl);
