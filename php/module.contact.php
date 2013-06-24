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
require_once('mapi/mapitags.php' );
 
class ContactModule extends Module {

	private $DEBUG = false; 	// enable error_log debugging

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
						case "load":							
							$result = $this->loadContacts($actionType, $actionData);
							break;
						case "import":
							$result = $this->importContacts($actionType, $actionData);
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
	 * Add an attachment to the give contact
	 * @param $actionType
	 * @param $actionData
	 */
	private function importContacts($actionType, $actionData) {
	
		// Get uploaded vcf path
		$vcffile = false;
		if(isset($actionData["vcf_filepath"])) {
			$vcffile = $actionData["vcf_filepath"];
		}
	
		// Get store id
		$storeid = false;
		if(isset($actionData["storeid"])) {
			$storeid = $actionData["storeid"];
		}
		
		// Get folder entryid
		$folderid = false;
		if(isset($actionData["folderid"])) {
			$folderid = $actionData["folderid"];
		}
		
		// Get uids
		$uids = array();
		if(isset($actionData["uids"])) {
			$uids = $actionData["uids"];
		}
		
		$response = array();
		$error = false;
		$error_msg = "";
		
		// parse the vcf file a last time...
		try {
			$vcard = new vCard($vcffile, false, array('Collapse' => false)); // Parse it!
		} catch (Exception $e) {
			$error = true;
			$error_msg = $e->getMessage();
		}
		
		$contacts = array();
		
		if(!$error && count($vcard) > 0) {
			$vCard = $vcard;
			if (count($vCard) == 1) {
				$vCard = array($vcard);
			}
				
			$contacts = $this->parseContactsToArray($vCard);
			$store = $GLOBALS["mapisession"]->openMessageStore(hex2bin($storeid));
			$folder = mapi_msgstore_openentry($store, hex2bin($folderid));
			
			$importall = false;
			if(count($uids) == count($contacts)) {
				$importall = true;
			}
			
			$propValuesMAPI = array();
			$properties = $this->getProperties();
			$properties = $this->replaceStringPropertyTags($store, $properties);
			$count = 0;
			
			// iterate through all contacts and import them :)
			foreach($contacts as $contact) {							
				if (isset($contact["display_name"]) && ($importall || in_array($contact["internal_fields"]["contact_uid"], $uids))) {
					// parse the arraykeys
					// TODO: this is very slow... 
					foreach($contact as $key => $value) {
						if($key !== "internal_fields") {
							$propValuesMAPI[$properties[$key]] = $value;
						}
					}
			
					$propValuesMAPI[$properties["message_class"]] = "IPM.Contact";
					$propValuesMAPI[$properties["icon_index"]] = "512";
					$message = mapi_folder_createmessage($folder);
					
					
					if(isset($contact["internal_fields"]["x_photo_path"])) {
						$propValuesMAPI[$properties["picture"]] = 1; // contact has an image

						// import the photo
						$contactPicture = file_get_contents($contact["internal_fields"]["x_photo_path"]);
						$attach = mapi_message_createattach($message);
		
						// Set properties of the attachment		
						$propValuesIMG = array(
							PR_ATTACH_SIZE => strlen($contactPicture),
							PR_ATTACH_LONG_FILENAME => 'ContactPicture.jpg',
							PR_ATTACHMENT_HIDDEN => false,
							PR_DISPLAY_NAME => 'ContactPicture.jpg',
							PR_ATTACH_METHOD => ATTACH_BY_VALUE,
							PR_ATTACH_MIME_TAG => 'image/jpeg',
							PR_ATTACHMENT_CONTACTPHOTO =>  true,
							PR_ATTACH_DATA_BIN => $contactPicture,
							PR_ATTACHMENT_FLAGS => 1,
							PR_ATTACH_EXTENSION_A => '.jpg',
							PR_ATTACH_NUM => 1
						);
						
						mapi_setprops($attach, $propValuesIMG);
						mapi_savechanges($attach);
						if($this->DEBUG) {
							error_log("Contactpicture imported!");
						}
						
						if (mapi_last_hresult() > 0) {
							error_log("Error saving attach to contact: " . get_mapi_error_name());
						}
					}
					
					mapi_setprops($message, $propValuesMAPI);
					mapi_savechanges($message);
					if($this->DEBUG) {
						error_log("New contact added: \"" . $propValuesMAPI[$properties["display_name"]] . "\".\n");
					}					
					$count++;
				}
			}

			$response['status'] = true;
			$response['count'] = $count;
			$response['message'] = "";
			
		} else {
			$response['status'] = false;
			$response['count'] = 0;
			$response['message'] = $error ? $error_msg : "VCF file empty!";
		}
		
		$this->addActionData($actionType, $response);
		$GLOBALS["bus"]->addData($this->getResponseData());
	}
	
	private function replaceStringPropertyTags($store, $properties) {
		$newProperties = array();

		$ids = array("name"=>array(), "id"=>array(), "guid"=>array(), "type"=>array()); // this array stores all the information needed to retrieve a named property
		$num = 0;

		// caching
		$guids = array();

		foreach($properties as $name => $val) {
			if(is_string($val)) {
				$split = explode(":", $val);

				if(count($split) != 3) { // invalid string, ignore
					trigger_error(sprintf("Invalid property: %s \"%s\"",$name,$val), E_USER_NOTICE);
					continue;
				}

				if(substr($split[2], 0, 2) == "0x") {
					$id = hexdec(substr($split[2], 2));
				} else {
					$id = $split[2];
				}

				// have we used this guid before?
				if (!defined($split[1])) {
					if (!array_key_exists($split[1], $guids)) {
						$guids[$split[1]] = makeguid($split[1]);
					}
					$guid = $guids[$split[1]];
				} else {
					$guid = constant($split[1]);
				}

				// temp store info about named prop, so we have to call mapi_getidsfromnames just one time
				$ids["name"][$num] = $name;
				$ids["id"][$num] = $id;
				$ids["guid"][$num] = $guid;
				$ids["type"][$num] = $split[0];
				$num++;
			} else {
				// not a named property
				$newProperties[$name] = $val;
			}
		}

		if (count($ids["id"]) == 0) {
			return $newProperties;
		}

		// get the ids
		$named = mapi_getidsfromnames($store, $ids["id"], $ids["guid"]);
		foreach($named as $num => $prop) {
			$newProperties[$ids["name"][$num]] = mapi_prop_tag(constant($ids["type"][$num]), mapi_prop_id($prop));
		}

		return $newProperties;
	}
	
	/**
	 * A simple Property map initialization
	 *
	 * @return [array] the propertyarray
	 */
	private function getProperties() {
		$properties = array();

		$properties["subject"] = PR_SUBJECT;
		$properties["icon_index"] = PR_ICON_INDEX;
		$properties["message_class"] = PR_MESSAGE_CLASS;
		$properties["display_name"] = PR_DISPLAY_NAME;
		$properties["given_name"] = PR_GIVEN_NAME;
		$properties["middle_name"] = PR_MIDDLE_NAME;
		$properties["surname"] = PR_SURNAME;
		$properties["home_telephone_number"] = PR_HOME_TELEPHONE_NUMBER;
		$properties["cellular_telephone_number"] = PR_CELLULAR_TELEPHONE_NUMBER;
		$properties["office_telephone_number"] = PR_OFFICE_TELEPHONE_NUMBER;
		$properties["business_fax_number"] = PR_BUSINESS_FAX_NUMBER;
		$properties["company_name"] = PR_COMPANY_NAME;
		$properties["title"] = PR_TITLE;
		$properties["department_name"] = PR_DEPARTMENT_NAME;
		$properties["office_location"] = PR_OFFICE_LOCATION;
		$properties["profession"] = PR_PROFESSION;
		$properties["manager_name"] = PR_MANAGER_NAME;
		$properties["assistant"] = PR_ASSISTANT;
		$properties["nickname"] = PR_NICKNAME;
		$properties["display_name_prefix"] = PR_DISPLAY_NAME_PREFIX;
		$properties["spouse_name"] = PR_SPOUSE_NAME;
		$properties["generation"] = PR_GENERATION;
		$properties["birthday"] = PR_BIRTHDAY;
		$properties["wedding_anniversary"] = PR_WEDDING_ANNIVERSARY;
		$properties["sensitivity"] = PR_SENSITIVITY;
		$properties["fileas"] = "PT_STRING8:PSETID_Address:0x8005";
		$properties["fileas_selection"] = "PT_LONG:PSETID_Address:0x8006";
		$properties["email_address_1"] = "PT_STRING8:PSETID_Address:0x8083";
		$properties["email_address_display_name_1"] = "PT_STRING8:PSETID_Address:0x8080";
		$properties["email_address_display_name_email_1"] = "PT_STRING8:PSETID_Address:0x8084";
		$properties["email_address_type_1"] = "PT_STRING8:PSETID_Address:0x8082";
		$properties["email_address_2"] = "PT_STRING8:PSETID_Address:0x8093";
		$properties["email_address_display_name_2"] = "PT_STRING8:PSETID_Address:0x8090";
		$properties["email_address_display_name_email_2"] = "PT_STRING8:PSETID_Address:0x8094";
		$properties["email_address_type_2"] = "PT_STRING8:PSETID_Address:0x8092";
		$properties["email_address_3"] = "PT_STRING8:PSETID_Address:0x80a3";
		$properties["email_address_display_name_3"] = "PT_STRING8:PSETID_Address:0x80a0";
		$properties["email_address_display_name_email_3"] = "PT_STRING8:PSETID_Address:0x80a4";
		$properties["email_address_type_3"] = "PT_STRING8:PSETID_Address:0x80a2";
		$properties["home_address"] = "PT_STRING8:PSETID_Address:0x801a";
		$properties["business_address"] = "PT_STRING8:PSETID_Address:0x801b";
		$properties["other_address"] = "PT_STRING8:PSETID_Address:0x801c";
		$properties["mailing_address"] = "PT_LONG:PSETID_Address:0x8022";
		$properties["im"] = "PT_STRING8:PSETID_Address:0x8062";
		$properties["webpage"] = "PT_STRING8:PSETID_Address:0x802b";
		$properties["business_home_page"] = PR_BUSINESS_HOME_PAGE;
		$properties["email_address_entryid_1"] = "PT_BINARY:PSETID_Address:0x8085";
		$properties["email_address_entryid_2"] = "PT_BINARY:PSETID_Address:0x8095";
		$properties["email_address_entryid_3"] = "PT_BINARY:PSETID_Address:0x80a5";
		$properties["address_book_mv"] = "PT_MV_LONG:PSETID_Address:0x8028";
		$properties["address_book_long"] = "PT_LONG:PSETID_Address:0x8029";
		$properties["oneoff_members"] = "PT_MV_BINARY:PSETID_Address:0x8054";
		$properties["members"] = "PT_MV_BINARY:PSETID_Address:0x8055";
		$properties["private"] = "PT_BOOLEAN:PSETID_Common:0x8506";
		$properties["contacts"] = "PT_MV_STRING8:PSETID_Common:0x853a";
		$properties["contacts_string"] = "PT_STRING8:PSETID_Common:0x8586";
		$properties["categories"] = "PT_MV_STRING8:PS_PUBLIC_STRINGS:Keywords";
		$properties["last_modification_time"] = PR_LAST_MODIFICATION_TIME;

		// Detailed contacts properties
		// Properties for phone numbers
		$properties["assistant_telephone_number"] = PR_ASSISTANT_TELEPHONE_NUMBER;
		$properties["business2_telephone_number"] = PR_BUSINESS2_TELEPHONE_NUMBER;
		$properties["callback_telephone_number"] = PR_CALLBACK_TELEPHONE_NUMBER;
		$properties["car_telephone_number"] = PR_CAR_TELEPHONE_NUMBER;
		$properties["company_telephone_number"] = PR_COMPANY_MAIN_PHONE_NUMBER;
		$properties["home2_telephone_number"] = PR_HOME2_TELEPHONE_NUMBER;
		$properties["home_fax_number"] = PR_HOME_FAX_NUMBER;
		$properties["isdn_number"] = PR_ISDN_NUMBER;
		$properties["other_telephone_number"] = PR_OTHER_TELEPHONE_NUMBER;
		$properties["pager_telephone_number"] = PR_PAGER_TELEPHONE_NUMBER;
		$properties["primary_fax_number"] = PR_PRIMARY_FAX_NUMBER;
		$properties["primary_telephone_number"] = PR_PRIMARY_TELEPHONE_NUMBER;
		$properties["radio_telephone_number"] = PR_RADIO_TELEPHONE_NUMBER;
		$properties["telex_telephone_number"] = PR_TELEX_NUMBER;
		$properties["ttytdd_telephone_number"] = PR_TTYTDD_PHONE_NUMBER;
		$properties["business_telephone_number"] =PR_BUSINESS_TELEPHONE_NUMBER;
		
		// Additional fax properties
		$properties["fax_1_address_type"] = "PT_STRING8:PSETID_Address:0x80B2";
		$properties["fax_1_email_address"] = "PT_STRING8:PSETID_Address:0x80B3";
		$properties["fax_1_original_display_name"] = "PT_STRING8:PSETID_Address:0x80B4";
		$properties["fax_1_original_entryid"] = "PT_BINARY:PSETID_Address:0x80B5";
		$properties["fax_2_address_type"] = "PT_STRING8:PSETID_Address:0x80C2";
		$properties["fax_2_email_address"] = "PT_STRING8:PSETID_Address:0x80C3";
		$properties["fax_2_original_display_name"] = "PT_STRING8:PSETID_Address:0x80C4";
		$properties["fax_2_original_entryid"] = "PT_BINARY:PSETID_Address:0x80C5";
		$properties["fax_3_address_type"] = "PT_STRING8:PSETID_Address:0x80D2";
		$properties["fax_3_email_address"] = "PT_STRING8:PSETID_Address:0x80D3";
		$properties["fax_3_original_display_name"] = "PT_STRING8:PSETID_Address:0x80D4";
		$properties["fax_3_original_entryid"] = "PT_BINARY:PSETID_Address:0x80D5";

		// Properties for addresses
		// Home address
		$properties["home_address_street"] = PR_HOME_ADDRESS_STREET;
		$properties["home_address_city"] = PR_HOME_ADDRESS_CITY;
		$properties["home_address_state"] = PR_HOME_ADDRESS_STATE_OR_PROVINCE;
		$properties["home_address_postal_code"] = PR_HOME_ADDRESS_POSTAL_CODE;
		$properties["home_address_country"] = PR_HOME_ADDRESS_COUNTRY;
		// Other address
		$properties["other_address_street"] = PR_OTHER_ADDRESS_STREET;
		$properties["other_address_city"] = PR_OTHER_ADDRESS_CITY;
		$properties["other_address_state"] = PR_OTHER_ADDRESS_STATE_OR_PROVINCE;
		$properties["other_address_postal_code"] = PR_OTHER_ADDRESS_POSTAL_CODE;
		$properties["other_address_country"] = PR_OTHER_ADDRESS_COUNTRY;
		// Business address
		$properties["business_address_street"] = "PT_STRING8:PSETID_Address:0x8045";
		$properties["business_address_city"] = "PT_STRING8:PSETID_Address:0x8046";
		$properties["business_address_state"] = "PT_STRING8:PSETID_Address:0x8047";
		$properties["business_address_postal_code"] = "PT_STRING8:PSETID_Address:0x8048";
		$properties["business_address_country"] = "PT_STRING8:PSETID_Address:0x8049";
		// Mailing address
		$properties["country"] = PR_COUNTRY;
		$properties["city"] = PR_LOCALITY;
		$properties["postal_address"] = PR_POSTAL_ADDRESS;
		$properties["postal_code"] = PR_POSTAL_CODE;
		$properties["state"] = PR_STATE_OR_PROVINCE;
		$properties["street"] = PR_STREET_ADDRESS;
		// Special Date such as birthday n anniversary appoitment's entryid is store
		$properties["birthday_eventid"] = "PT_BINARY:PSETID_Address:0x804D";
		$properties["anniversary_eventid"] = "PT_BINARY:PSETID_Address:0x804E";

		$properties["notes"] = PR_BODY;
		
		// hasimage
		$properties["picture"] = "PT_BOOLEAN:{00062004-0000-0000-C000-000000000046}:0x8015";
		
		return $properties;
	}

	/**
	 * Function that parses the uploaded vcf file and posts it via json
	 * @param $actionType
	 * @param $actionData
	 */
	private function loadContacts($actionType, $actionData) {
		$error = false;
		$error_msg = "";
		
		if(is_readable ($actionData["vcf_filepath"])) {
			try {
				$vcard = new vCard($actionData["vcf_filepath"], false, array('Collapse' => false)); // Parse it!
			} catch (Exception $e) {
				$error = true;
				$error_msg = $e->getMessage();
			}
			if($error) {
				$response['status']	= false;
				$response['message']= $error_msg;
			} else {
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
				
				//uid - used for front/backend communication
				$properties["internal_fields"] = array();
				$properties["internal_fields"]["contact_uid"] = base64_encode($Index . $properties["fileas"]);
				
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
					$properties["notes"] = $vCard -> NOTE[0];
				}
				if ($vCard -> PHOTO) {
					if(!is_writable(TMP_PATH . "/")) {
						error_log("could not write to export tmp directory!: " . $E);
					} else {
						$tmppath = TMP_PATH . "/" . $this->randomstring(15);
						try {
							if($vCard -> SaveFile('photo', 0, $tmppath)) {
								$properties["internal_fields"]["x_photo_path"] = $tmppath;								
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
};

?>
