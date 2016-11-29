<?php

/**
 * download.php, Kopano Webapp contact to vcf im/exporter
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
class DownloadHandler
{
	/**
	 * Download the given vcf file.
	 */
	public static function doDownload()
	{
		if (isset($_GET["token"])) {
			$token = $_GET["token"];
		} else {
			return false;
		}

		if (isset($_GET["filename"])) {
			$filename = $_GET["filename"];
		} else {
			return false;
		}

		// validate token
		if (!ctype_alnum($token)) { // token is a md5 hash
			return false;
		}

		$file = PLUGIN_CONTACTIMPORTER_TMP_UPLOAD . "vcf_" . $token . ".vcf";

		if (!file_exists($file)) { // invalid token
			return false;
		}

		// set headers here
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		// no caching
		header('Expires: 0'); // set expiration time
		header('Content-Description: File Transfer');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Length: ' . filesize($file));
		header('Content-Type: application/octet-stream');
		header('Pragma: public');
		flush();

		// print the downloaded file
		readfile($file);
		ignore_user_abort(true);
		unlink($file);
	}
}