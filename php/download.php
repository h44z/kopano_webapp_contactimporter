<?php
/**
 * download.php, zarafa contact to vcf im/exporter
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
$basedir  = $_GET["basedir"];
$secid    = $_GET["secid"];
$fileid   = $_GET["fileid"];
$realname = $_GET["realname"];

$secfile = $basedir . "/secid." . $secid;
$vcffile = $basedir . "/" . $fileid . "." . $secid;

// if the secid file exists -> download!
if(file_exists($secfile)) {
	@header("Last-Modified: " . @gmdate("D, d M Y H:i:s",time()) . " GMT");
	@header("Content-type: text/vcard");
	header("Content-Length: " . filesize($vcffile));
	header("Content-Disposition: attachment; filename=" . $realname . ".vcf");

	//write vcf
	readfile($vcffile);
	unlink($secfile);
	unlink($vcffile);
}

?>