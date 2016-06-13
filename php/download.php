<?php

class DownloadHandler
{

	public static function doDownload() {
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

		if(!file_exists($file)) { // invalid token
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