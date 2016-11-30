<?php

/**
 * upload.php, Kopano Webapp contact to vcf im/exporter
 *
 * Author: Christoph Haas <christoph.h@sprinternet.at>
 * Copyright (C) 2012-2016 Christoph Haas
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/helper.php");

require_once(__DIR__ . '/../../../init.php');
require_once(__DIR__ . "/../../../server/includes/core/class.webappauthentication.php"); // for checking the session

use contactimporter\Helper;

/* disable error printing - otherwise json communication might break... */
ini_set('display_errors', '0');

// check session
// otherwise a DOS attack might be possible
if (!WebAppAuthentication::getUserName() || WebAppAuthentication::getUserName() == "") {
    Helper::respondJSON(array('success' => false, 'error' => dgettext("plugin_contactimporter", "Not authenticated!")));
    die();
}

if (is_readable($_FILES['vcfdata']['tmp_name'])) {
    $dstPath = PLUGIN_CONTACTIMPORTER_TMP_UPLOAD;
    $dstPath .= $_FILES['vcfdata']['name'] . Helper::randomstring();

    $result = move_uploaded_file($_FILES['vcfdata']['tmp_name'], $dstPath);

    if ($result) {
        Helper::respondJSON(array('success' => true, 'vcf_file' => $dstPath));
    } else {
        Helper::respondJSON(array('success' => false, 'error' => dgettext("plugin_contactimporter", "File could not be moved to TMP path! Check plugin config and folder permissions!")));
    }
} else {
    Helper::respondJSON(array('success' => false, 'error' => dgettext("plugin_contactimporter", "File could not be read by server, upload error!")));
}