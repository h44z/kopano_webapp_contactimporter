<?php
/**
 * upload.php, zarafa contact to vcf im/exporter
 *
 * Author: Christoph Haas <christoph.h@sprinternet.at>
 * Copyright (C) 2012-2013 Christoph Haas
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
 
require_once("../config.php");

/* disable error printing - otherwise json communication might break... */
ini_set('display_errors', '0');

 /**
  * respond/echo JSON
  * @param $arr 
  */
function respondJSON($arr) {
	echo json_encode($arr);
}

/**
 * Generates a random string with variable length.
 * @param $length the lenght of the generated string
 * @return string a random string
 */
function randomstring($length = 6) {
	// $chars - all allowed charakters
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";

	srand((double)microtime()*1000000);
	$i = 0;
	$pass = "";
	while ($i < $length) {
		$num = rand() % strlen($chars);
		$tmp = substr($chars, $num, 1);
		$pass = $pass . $tmp;
		$i++;
	}
	return $pass;
}

$destpath = PLUGIN_CONTACTIMPORTER_TMP_UPLOAD;
$destpath .= $_FILES['vcfdata']['name'] . randomstring();

if(is_readable ($_FILES['vcfdata']['tmp_name'])) {
	$result = move_uploaded_file($_FILES['vcfdata']['tmp_name'],$destpath);
	
	if($result) {
		respondJSON(array ('success'=>true, 'vcf_file'=>$destpath));
	} else {
		respondJSON(array ('success'=>false,'error'=>"File could not be moved to TMP path! Check plugin config and folder permissions!"));
	}
} else {
	respondJSON(array ('success'=>false,'error'=>"File could not be read by server, upload error!"));
}
?>