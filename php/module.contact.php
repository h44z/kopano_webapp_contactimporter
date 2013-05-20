<?php	
/**
 * class.calendar.php, zarafa contact to vcf im/exporter
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
 
include_once('vcf/class.vCard.php');
 
class ContactModule extends Module {

	private $DEBUG = true; 	// enable error_log debugging

	/**
	 * @constructor
	 * @param $id
	 * @param $data
	 */
	public function __construct($id, $data) {
			parent::Module($id, $data);	
	}

	/**
	 * Executes all the actions in the $data variable.
	 * Exception part is used for authentication errors also
	 * @return boolean true on success or false on failure.
	 */
	public function execute() {
		$result = false;
		
		if(!$this->DEBUG) {
			/* disable error printing - otherwise json communication might break... */
			ini_set('display_errors', '0');
		}
		
		foreach($this->data as $actionType => $actionData) {
			if(isset($actionType)) {
				try {
					if($this->DEBUG) {
						error_log("exec: " . $actionType);
					}
					switch($actionType) {
						case "export":
							$result = $this->exportCalendar($actionType, $actionData);
							break;
						case "import":
							$result = $this->importContacts($actionType, $actionData);
							break;
						case "addattachment":
							$result = $this->addAttachment($actionType, $actionData);
							break;
						case "attachmentpath":
							$result = $this->getAttachmentPath($actionType, $actionData);
							break;
						default:
							$this->handleUnknownActionType($actionType);
					}

				} catch (MAPIException $e) {
					if($this->DEBUG) {
						error_log("mapi exception: " . $e->getMessage());
					}
				} catch (Exception $e) {
					if($this->DEBUG) {
						error_log("exception: " . $e->getMessage());
					}
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Generates a random string with variable length.
	 * @param $length the lenght of the generated string
	 * @return string a random string
	 */
	private function randomstring($length = 6) {
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
	
	/**
	 * Generates the secid file (used to verify the download path)
	 * @param $secid the secid, a random security token
	 */
	private function createSecIDFile($secid) {
		$lockFile = TMP_PATH . "/secid." . $secid;
		$fh = fopen($lockFile, 'w') or die("can't open secid file");
		$stringData = date(DATE_RFC822);
		fwrite($fh, $stringData);
		fclose($fh);
	}
	
	/**
	 * Generates the secid file (used to verify the download path)
	 * @param $time a timestamp
	 * @param $incl_time true if date should include time
	 * @ return date object
	 */
	private function getIcalDate($time, $incl_time = true) {
		return $incl_time ? date('Ymd\THis', $time) : date('Ymd', $time);
	}
	
	/**
	 * Add an attachment to the give contact
	 * @param $actionType
	 * @param $actionData
	 */
	private function addAttachment($actionType, $actionData) {
		// Get Attachment data from state
		$attachment_state = new AttachmentState();
		$attachment_state->open();
		
		$filename = "ContactPicture.jpg";
		$tmppath = $actionData["tmpfile"];
		$filesize = filesize($tmppath);
		$f = getimagesize($tmppath);
		$filetype = $f["mime"];
		
		// Move the uploaded file into the attachment state
		$attachid = $attachment_state->addProvidedAttachmentFile($actionData["storeid"], $filename, $tmppath, array(
			"name"       => $filename,
			"size"       => $filesize,
			"type"       => $filetype,
			"sourcetype" => 'default'
		));
		
		$attachment_state->close();
		
		$response['status'] = true;
		$response['storeid'] = $actionData["storeid"];
		$response['tmpname'] = $attachid;
		$response['name'] = $filename;
		$response['size'] = $filesize;
		$response['type'] = $filetype;
		
		$this->addActionData($actionType, $response);
		$GLOBALS["bus"]->addData($this->getResponseData());
	}

	/**
	 * The main export function, creates the ics file for download
	 * @param $actionType
	 * @param $actionData
	 */
	private function exportContacts($actionType, $actionData) {
		$secid = $this->randomstring();	
		$this->createSecIDFile($secid);
		$tmpname = stripslashes($actionData["calendar"] . ".ics." . $this->randomstring(8));
		$filename = TMP_PATH . "/" . $tmpname . "." . $secid;
		
		if(!is_writable(TMP_PATH . "/")) {
			error_log("could not write to export tmp directory!");
		}
		
		$tz = date("e");	// use php timezone (maybe set up in php.ini, date.timezone)
		
		if($this->DEBUG) {
			error_log("PHP Timezone: " . $tz);
		}		
		
		$config = array(
						"language" => substr($GLOBALS["settings"]->get("zarafa/v1/main/language"),0,2),
						"directory" => TMP_PATH . "/", 
						"filename" => $tmpname . "." . $secid,
						"unique_id" => "zarafa-export-plugin", 
						"TZID" => $tz
						);
		
		$v = new vcalendar($config); 
		$v->setProperty("method", "PUBLISH");							// required of some calendar software
		$v->setProperty("x-wr-calname", $actionData["calendar"]);		// required of some calendar software
		$v->setProperty("X-WR-CALDESC", "Exported Zarafa Calendar");	// required of some calendar software
		$v->setProperty("X-WR-TIMEZONE", $tz); 

		$xprops = array("X-LIC-LOCATION" => $tz);					// required of some calendar software
		iCalUtilityFunctions::createTimezone($v, $tz, $xprops);		// create timezone object in calendar
				
		
		foreach($actionData["data"] as $event) {
			$event["props"]["description"] = $this->loadEventDescription($event);
			$event["props"]["attendees"] = $this->loadAttendees($event);
			
			$vevent = & $v->newComponent("vevent");	// create a new event object
			$this->addEvent($vevent, $event["props"]);
		}
		
		$v->saveCalendar();
		
		$response['status']		= true;
		$response['fileid']		= $tmpname;	// number of entries that will be exported
		$response['basedir']	= TMP_PATH;
		$response['secid']		= $secid;
		$response['realname']	= $actionData["calendar"];
		$this->addActionData($actionType, $response);
		$GLOBALS["bus"]->addData($this->getResponseData());
		
		if($this->DEBUG) {
			error_log("export done, bus data written!");
		}
	}
	
	/**
	 * The main import function, parses the uploaded vcf file
	 * @param $actionType
	 * @param $actionData
	 */
	private function importContacts($actionType, $actionData) {
		if($this->DEBUG) {
			error_log("PHP Timezone: " . $tz);
		}
		
		if(is_readable ($actionData["vcf_filepath"])) {
			$vcard = new vCard($actionData["vcf_filepath"], false, array('Collapse' => false)); // Parse it!
			error_log(print_r($vcard, true));
			if(count($vcard) == 0) {
				$response['status']	= false;
				$response['message']= "No contacts in vcf file";
			} else {
				$vCard = $vcard;
				if (count($vCard) == 1) {
					$vCard = array($vcard);
				}
				
				$response['status']		= true;
				$response['parsed_file']= $actionData["vcf_filepath"];
				$response['parsed']		= array (
					'contacts'	=>	$this->parseContactsToArray($vCard)
				);
			}
		} else {
			$response['status']	= false;
			$response['message']= "File could not be read by server";
		}
		
		$this->addActionData($actionType, $response);
		$GLOBALS["bus"]->addData($this->getResponseData());
		
		if($this->DEBUG) {
			error_log("parsing done, bus data written!");
		}
	}
	
	/**
	 * Create a array with contacts
	 * 
	 * @param contacts vcard or csv contacts
	 * @param csv optional, true if contacts are csv contacts
	 * @return array parsed contacts
	 * @private
	 */
	private function parseContactsToArray($contacts, $csv = false) {
		$carr = array();
		if(!$csv) {
			foreach ($contacts as $Index => $vCard) {
				$properties = array();
				$properties["display_name"] = $vCard -> FN[0];
				$properties["fileas"] = $vCard -> FN[0];
				
				foreach ($vCard -> N as $Name) {
					$properties["given_name"] = $Name['FirstName'];
					$properties["middle_name"] = $Name['AdditionalNames'];
					$properties["surname"] = $Name['LastName'];
					$properties["display_name_prefix"] = $Name['Prefixes'];
				}
				if ($vCard -> TEL) {
					foreach ($vCard -> TEL as $Tel) {
						if(!is_scalar($Tel)) {
							if(in_array("home", $Tel['Type'])) {
								$properties["home_telephone_number"] = $Tel['Value'];
							} else if(in_array("cell", $Tel['Type'])) {
								$properties["cellular_telephone_number"] = $Tel['Value'];
							} else if(in_array("work", $Tel['Type'])) {
								$properties["business_telephone_number"] = $Tel['Value'];
							} else if(in_array("fax", $Tel['Type'])) {
								$properties["business_fax_number"] = $Tel['Value'];
							} else if(in_array("pager", $Tel['Type'])) {
								$properties["pager_telephone_number"] = $Tel['Value'];
							} else if(in_array("isdn", $Tel['Type'])) {
								$properties["isdn_number"] = $Tel['Value'];
							} else if(in_array("car", $Tel['Type'])) {
								$properties["car_telephone_number"] = $Tel['Value'];
							} else if(in_array("modem", $Tel['Type'])) {
								$properties["ttytdd_telephone_number"] = $Tel['Value'];
							}
						}
					}
				}
				if ($vCard -> EMAIL) {
					$e=0;
					foreach ($vCard -> EMAIL as $Email) {
						$fileas = $Email['Value'];
						if(isset($properties["fileas"]) && !empty($properties["fileas"])) {
							$fileas = $properties["fileas"];
						}
						
						if(!is_scalar($Email)) {
							switch($e) {
								case 0:
									$properties["email_address_1"] = $Email['Value'];
									$properties["email_address_display_name_1"] = $fileas . " (" . $Email['Value'] . ")";
									break;
								case 1:
									$properties["email_address_2"] = $Email['Value'];
									$properties["email_address_display_name_2"] = $fileas . " (" . $Email['Value'] . ")";
									break;
								case 2:
									$properties["email_address_3"] = $Email['Value'];
									$properties["email_address_display_name_3"] = $fileas . " (" . $Email['Value'] . ")";
									break;
								default: break;
							}
							$e++;
						}
					}
				}
				if ($vCard -> ORG) {
					foreach ($vCard -> ORG as $Organization) {
						$properties["company_name"] = $Organization['Name'];
						if(empty($properties["display_name"])) {
							$properties["display_name"] = $Organization['Name']; // if we have no displayname - use the company name as displayname
							$properties["fileas"] = $Organization['Name'];
						}
					}
				}
				if ($vCard -> TITLE) {
					$properties["title"] = $vCard -> TITLE[0];
				}
				if ($vCard -> URL) {
					$properties["webpage"] = $vCard -> URL[0];
				}
				if ($vCard -> IMPP) {
					foreach ($vCard -> IMPP as $IMPP) {
						if (!is_scalar($IMPP)) {
							$properties["im"] = $IMPP['Value'];
						}
					}
				}
				if ($vCard -> ADR) {
					foreach ($vCard -> ADR as $Address) {
						if(in_array("work", $Address['Type'])) {
							$properties["business_address_street"] = $Address['StreetAddress'];
							$properties["business_address_city"] = $Address['Locality'];
							$properties["business_address_state"] = $Address['Region'];
							$properties["business_address_postal_code"] = $Address['PostalCode'];
							$properties["business_address_country"] = $Address['Country'];
							$properties["business_address"] = $this->buildAddressString($Address['StreetAddress'], $Address['PostalCode'], $Address['Locality'], $Address['Region'], $Address['Country']);
						} else if(in_array("home", $Address['Type'])) {
							$properties["home_address_street"] = $Address['StreetAddress'];
							$properties["home_address_city"] = $Address['Locality'];
							$properties["home_address_state"] = $Address['Region'];
							$properties["home_address_postal_code"] = $Address['PostalCode'];
							$properties["home_address_country"] = $Address['Country'];
							$properties["home_address"] = $this->buildAddressString($Address['StreetAddress'], $Address['PostalCode'], $Address['Locality'], $Address['Region'], $Address['Country']);
						} else if(in_array("postal", $Address['Type'])||in_array("parcel", $Address['Type'])||in_array("intl", $Address['Type'])||in_array("dom", $Address['Type'])) {
							$properties["other_address_street"] = $Address['StreetAddress'];
							$properties["other_address_city"] = $Address['Locality'];
							$properties["other_address_state"] = $Address['Region'];
							$properties["other_address_postal_code"] = $Address['PostalCode'];
							$properties["other_address_country"] = $Address['Country'];
							$properties["other_address"] = $this->buildAddressString($Address['StreetAddress'], $Address['PostalCode'], $Address['Locality'], $Address['Region'], $Address['Country']);
						}
					}
				}
				if ($vCard -> BDAY) {
					$properties["birthday"] = strtotime($vCard -> BDAY[0]);
				}
				if ($vCard -> NOTE) {
					$properties["body"] = $vCard -> NOTE[0];
				}
				if ($vCard -> PHOTO) {
					if(!is_writable(TMP_PATH . "/")) {
						error_log("could not write to export tmp directory!: " . $E);
					} else {
						$tmppath = TMP_PATH . "/" . $this->randomstring(15);
						try {
							if($vCard -> SaveFile('photo', 0, $tmppath)) {
								$properties["x_photo_path"] = $tmppath;								
							} else {
								if($this->DEBUG) {
									error_log("remote imagefetching not implemented");
								}
							}
						} catch (Exception $E) {
							error_log("Image exception: " . $E);
						}
					}
				}
				array_push($carr, $properties);
			}
		} else {
			error_log("csv parsing not implemented");
		}
		
		return $carr;
	}
	
	/**
	 * Generate the whole addressstring
	 *
	 * @param street
	 * @param zip
	 * @param city
	 * @param state
	 * @param country
	 * @return string the concatinated address string
	 * @private
	 */
	private function buildAddressString($street, $zip, $city, $state, $country) {
		$out = "";

		if (isset($country) && $street != "") $out = $country;

		$zcs = "";
		if (isset($zip) && $zip != "") $zcs = $zip;
		if (isset($city) && $city != "") $zcs .= (($zcs)?" ":"") . $city;
		if (isset($state) && $state != "") $zcs .= (($zcs)?" ":"") . $state;
		if ($zcs) $out = $zcs . "\r\n" . $out;

		if (isset($street) && $street != "") $out = $street . (($out)?"\r\n". $out: "") ;

		return $out;
	}
	
	/**
	 * Store the file to a temporary directory, prepare it for oc upload
	 * @param $actionType
	 * @param $actionData
	 * @private
	 */
	private function getAttachmentPath($actionType, $actionData) {
		// Get store id
		$storeid = false;
		if(isset($actionData["store"])) {
			$storeid = $actionData["store"];
		}

		// Get message entryid
		$entryid = false;
		if(isset($actionData["entryid"])) {
			$entryid = $actionData["entryid"];
		}

		// Check which type isset
		$openType = "attachment";

		// Get number of attachment which should be opened.
		$attachNum = false;
		if(isset($actionData["attachNum"])) {
			$attachNum = $actionData["attachNum"];
		}

		// Check if storeid and entryid isset
		if($storeid && $entryid) {
			// Open the store
			$store = $GLOBALS["mapisession"]->openMessageStore(hex2bin($storeid));
			
			if($store) {
				// Open the message
				$message = mapi_msgstore_openentry($store, hex2bin($entryid));
				
				if($message) {
					$attachment = false;

					// Check if attachNum isset
					if($attachNum) {
						// Loop through the attachNums, message in message in message ...
						for($i = 0; $i < (count($attachNum) - 1); $i++)
						{
							// Open the attachment
							$tempattach = mapi_message_openattach($message, (int) $attachNum[$i]);
							if($tempattach) {
								// Open the object in the attachment
								$message = mapi_attach_openobj($tempattach);
							}
						}

						// Open the attachment
						$attachment = mapi_message_openattach($message, (int) $attachNum[(count($attachNum) - 1)]);
					}

					// Check if the attachment is opened
					if($attachment) {
						
						// Get the props of the attachment
						$props = mapi_attach_getprops($attachment, array(PR_ATTACH_LONG_FILENAME, PR_ATTACH_MIME_TAG, PR_DISPLAY_NAME, PR_ATTACH_METHOD));
						// Content Type
						$contentType = "application/octet-stream";
						// Filename
						$filename = "ERROR";

						// Set filename
						if(isset($props[PR_ATTACH_LONG_FILENAME])) {
							$filename = $props[PR_ATTACH_LONG_FILENAME];
						} else if(isset($props[PR_ATTACH_FILENAME])) {
							$filename = $props[PR_ATTACH_FILENAME];
						} else if(isset($props[PR_DISPLAY_NAME])) {
							$filename = $props[PR_DISPLAY_NAME];
						} 
				
						// Set content type
						if(isset($props[PR_ATTACH_MIME_TAG])) {
							$contentType = $props[PR_ATTACH_MIME_TAG];
						} else {
							// Parse the extension of the filename to get the content type
							if(strrpos($filename, ".") !== false) {
								$extension = strtolower(substr($filename, strrpos($filename, ".")));
								$contentType = "application/octet-stream";
								if (is_readable("mimetypes.dat")){
									$fh = fopen("mimetypes.dat","r");
									$ext_found = false;
									while (!feof($fh) && !$ext_found){
										$line = fgets($fh);
										preg_match("/(\.[a-z0-9]+)[ \t]+([^ \t\n\r]*)/i", $line, $result);
										if ($extension == $result[1]){
											$ext_found = true;
											$contentType = $result[2];
										}
									}
									fclose($fh);
								}
							}
						}
						
						
						$tmpname = tempnam(TMP_PATH, stripslashes($filename));

						// Open a stream to get the attachment data
						$stream = mapi_openpropertytostream($attachment, PR_ATTACH_DATA_BIN);
						$stat = mapi_stream_stat($stream);
						// File length =  $stat["cb"]
						
						$fhandle = fopen($tmpname,'w');
						$buffer = null;
						for($i = 0; $i < $stat["cb"]; $i += BLOCK_SIZE) {
							// Write stream
							$buffer = mapi_stream_read($stream, BLOCK_SIZE);
							fwrite($fhandle,$buffer,strlen($buffer));
						}
						fclose($fhandle);
						
						$response = array();
						$response['tmpname'] = $tmpname;
						$response['filename'] = $filename;
						$response['status'] = true;
						$this->addActionData($actionType, $response);
						$GLOBALS["bus"]->addData($this->getResponseData());
					}
				}
			} else {
				$response['status'] = false;
				$response['message'] = "Store could not be opened!";
				$this->addActionData($actionType, $response);
				$GLOBALS["bus"]->addData($this->getResponseData());
			}
		} else {
			$response['status'] = false;
			$response['message'] = "Wrong call, store and entryid have to be set!";
			$this->addActionData($actionType, $response);
			$GLOBALS["bus"]->addData($this->getResponseData());
		}
	}
};

?>
