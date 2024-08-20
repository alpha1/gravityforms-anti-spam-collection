<?php
/*
Plugin Name: WP Hacks Gravity Forms Anti-Spam Collection
Description: 
Author: WP Hacks
Version: 0.1.0
Author URI: https://wphacks.org
*/

function wphacks_anti_spam_collection_log_spam_reason( $entry_obj, $reason, $form ){
	//$res = wp_cache_set( "entry_". rgar( $entry_obj, "id") ."_spam_reason", $reason, "wphacks_gforms_anti_spam", 300 );
	GFCommon::set_spam_filter( rgar( $form, 'id' ), "WP Hacks Anti-Spam", $reason );
}


add_filter( 'gform_save_field_value', 'wphacks_gravity_forms_lowercase_all_emails', 10, 5 );
function wphacks_gravity_forms_lowercase_all_emails( $value, $entry, $field, $form, $input_id ){
	if($field->type == "email" ){
		$value = trim( strtolower( $value ) );
	}
	return $value;
}

add_filter( 'gform_entry_is_spam', 'wphacks_mark_as_spam_banned_domains_on_website_fields', 10, 3 );
function wphacks_mark_as_spam_banned_domains_on_website_fields( $is_spam, $form, $entry ) {
	if( $is_spam ){
		return $is_spam;
	}
	
	$banned_domains = array(
		'tinyurl.com',
		'thetranny',
		'pornhub.com',
		'yandex.ru'
	);
	if( isset( $form['fields'] ) ){
		foreach( $form['fields'] as $field ){
			if( "website" == $field->type ){
				$website = rgar( $entry, $field->id);
				$parsed_url = parse_url( $website );
				print_r( $parsed_url );
				echo $parsed_url['host'];
				if(in_array( $parsed_url['host'], apply_filters('wphacks_gravityforms_anti_spam_collections_banned_domains_on_website_fields', $banned_domains ) ) ){
					$spam_reason = 'Field '. $field->id .' has a banned domain in the website.';
					wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
					return true;
				}
			} 
		}
	} 
	return $is_spam;
}


add_filter( 'gform_entry_is_spam', 'wphacks_mark_as_spam_banned_tlds_on_website_fields', 10, 3 );
function wphacks_mark_as_spam_banned_tlds_on_website_fields( $is_spam, $form, $entry ) {
	if( $is_spam ){
		return $is_spam;
	}
	
	$banned_tlds = array(
		'ru',
		'ch'
	);
	
	$banned_tlds = apply_filters('wphacks_gravityforms_anti_spam_collections_banned_tlds_on_website_fields', $banned_tlds );
	
	if( isset( $form['fields'] ) ){
		foreach( $form['fields'] as $field ){
			if( $field->type == "website"){
				$website = rgar( $entry, $field->id);
				if( !empty( $website ) ){
					$parsed_url = parse_url( $website );

					foreach( $banned_tlds as $banned_tld){
						$length = strlen( $banned_tld );			
						if( !$length ) {
							return $is_spam;
						}
						
						if(  substr( $parsed_url['host'], -$length ) === $banned_tld  ){
							$spam_reason = 'Field '. $field->id .' has a banned TLD in the website.';
							wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
							return true;
						}
					}
				}
			}
		}
	}
    return $is_spam;
}

add_filter( 'gform_entry_is_spam', 'wphacks_mark_as_spam_banned_tlds_on_email_fields', 10, 3 );
function wphacks_mark_as_spam_banned_tlds_on_email_fields( $is_spam, $form, $entry ) {
	if( $is_spam ){
		return $is_spam;
	}
	
	$banned_tlds = array(
		'ru',
		'ch'
	);
	
	$banned_tlds = apply_filters('wphacks_gravityforms_anti_spam_collections_banned_tlds_on_email_fields', $banned_tlds );
	
	if( isset( $form['fields'] ) ){
		foreach( $form['fields'] as $field ){
			if( $field->type == "email"){
				$email = rgar( $entry, $field->id);
				if( !empty( $email ) ){
					$email_explode = explode( '@', $email );

					if( is_array( $email_explode ) ){
						$email_domain = end( $email_explode );
						foreach( $banned_tlds as $banned_tld){
							$length = strlen( $banned_tld );			
							if( !$length ) {
								return $is_spam;
							}
							
							if(  substr( $email_domain, -$length ) === $banned_tld  ){
								$spam_reason = 'Field '. $field->id .' has a banned TLD in the email address.';
								wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
								return true;
							}
						}
					}
				}
			}
		}
	}
    return $is_spam;
}

add_filter( 'gform_entry_is_spam', 'wphacks_mark_as_spam_banned_usernames_on_email_fields', 1, 3 );
function wphacks_mark_as_spam_banned_usernames_on_email_fields( $is_spam, $form, $entry ) {
	if( $is_spam ){
		return $is_spam;
	}
	
	$banned_usernames= array(
		'nobody',
		'noreply',
		'no-reply',
		'postmaster',
		'abuse'
	);
	
	$banned_usernames = apply_filters('wphacks_gravityforms_anti_spam_collections_banned_usernames_on_email_fields', $banned_usernames );
	
	if( isset( $form['fields'] ) ){
		foreach( $form['fields'] as $field ){
			if( $field->type == "email"){
				$email = rgar( $entry, $field->id);
				if( !empty( $email ) ){
					$email_explode = explode( '@', $email );

					if( is_array( $email_explode ) ){
						$email_username = $email_explode[0];
						if( in_array( strtolower( trim( $email_username ) ), $banned_usernames ) ){
							
							$spam_reason = 'Field '. $field->id .' has a banned username in the email address.';
							wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
							return true;
						}
					}
				}
			}
		}
	}
    return $is_spam;
}

add_filter( 'gform_entry_is_spam', 'wphacks_mark_as_spam_banned_domains_on_email_fields', 10, 3 );
function wphacks_mark_as_spam_banned_domains_on_email_fields( $is_spam, $form, $entry ) {
	if( $is_spam ){
		return $is_spam;
	}
	
	$banned_domains = array(
		'example.org',
	);
	
	$banned_domains = apply_filters('wphacks_gravityforms_anti_spam_collections_banned_domains_on_email_fields', $banned_domains );
	
	
	if(!empty( $banned_domains ) ){
		if( isset( $form['fields'] ) ){
			foreach( $form['fields'] as $field ){
				if( $field->type == "email"){
					$email = rgar( $entry, $field->id);
					$email_explode = explode( '@', $email );
					if( is_array( $email_explode ) ){
						$email_domain = end( $email_explode );
						
						if( in_array( strtolower( trim( $email_domain ) ), $banned_domains ) ){
							
							$spam_reason = 'Field '. $field->id .' has a banned domain in the email address.';
							wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
							return true;
						}
					}
				}
			}
		}
	}
	return $is_spam;
}


function wphacks_get_ipdata_co_access_token(){
	$token = false;
	if(defined('IPDATA_CO_ACCESS_TOKEN')){
		return apply_filters('whpacks_get_ipdata_co_access_token',IPDATA_CO_ACCESS_TOKEN);
	}
	
	return apply_filters('whpacks_get_ipdata_co_access_token', $token);
}

add_filter( 'gform_entry_is_spam', 'mark_webhosts_as_spam_using_ipdata_co', 1000, 3 );
function mark_webhosts_as_spam_using_ipdata_co( $is_spam, $form, $entry ) {
	if( $is_spam ){
		return $is_spam;
	}
	
	$ip_address = empty( $entry['ip'] ) ? GFFormsModel::get_ip() : $entry['ip'];
	$token = wphacks_get_ipdata_co_access_token();
	
	if( !$token ){
		//TODO: Add logging
		return $is_spam;
	}
	
	$bad_asns = array();
	$bad_asns = apply_filters( 'wphacks_gravityforms_anti_spam_collections_allowlist_domains_on_email_field', $bad_asns );
	
	$ip_lookup_full_results = wp_remote_get( add_query_arg( array( "api-key" => $token ), "https://api.ipdata.co/". $ip_address ) );

	if( in_array( wp_remote_retrieve_response_code( $ip_lookup_full_results ), array('200') ) ){
	
	$ip_lookup_results = wp_remote_retrieve_body( $ip_lookup_full_results );
	if( !empty( $ip_lookup_results ) ){
		$ip_lookup_results = json_decode( $ip_lookup_results, true );
	}
		//known abusers
		if( true === apply_filters( 'wphacks_gravityforms_mark_known_abusers_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_known_abuser'] ){
				$spam_reason = "IP Address ({$ip_address}) is a known abuser.";
				wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
				return true;
			}
		}
		
		//known attackers
		if( true === apply_filters( 'wphacks_gravityforms_mark_known_attackers_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_known_attacker'] ){
				$spam_reason = "IP Address ({$ip_address}) is a known attacker.";
				wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
				return true;
			}
		}
		
		//datacenters
		if( true === apply_filters( 'wphacks_gravityforms_mark_webhosts_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_datacenter'] ){
				$spam_reason = "IP Address ({$ip_address}) is datacenter.";
				wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
				return true;
			}
		}

		//bogons
		if( true === apply_filters( 'wphacks_gravityforms_mark_bogon_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_bogon'] ){
				$spam_reason = "IP Address ({$ip_address}) is a bogon.";
				wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
				return true;
			}
		}

		//threats
		if( true === apply_filters( 'wphacks_gravityforms_mark_threats_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_threat'] ){
				$spam_reason = "IP Address ({$ip_address}) is from a known threat.";
				wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
				return true;
			}
		}
		
		//is_anonymous
		if( true === apply_filters( 'wphacks_gravityforms_mark_anonymous_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_anonymous'] ){
				$spam_reason = "IP Address ({$ip_address}) is an anonymous service.";
				wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
				return true;
			}
		}	

		//is_proxy
		if( true === apply_filters( 'wphacks_gravityforms_mark_proxy_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_proxy'] ){
				$spam_reason = "IP Address ({$ip_address}) is a proxy.";
				wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
				return true;
			}
		}
		
		//is_proxy
		if( true === apply_filters( 'wphacks_gravityforms_mark_tor_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_tor'] ){
				$spam_reason = "IP Address ({$ip_address}) is an Tor exit node.";
				wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
				return true;
			}
		}

		//icloud_relays
		if( true === apply_filters( 'wphacks_gravityforms_mark_icloud_relay_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_icloud_relay'] ){
				$spam_reason = "IP Address ({$ip_address}) is an icloud relay.";
				wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
				return true;
			}
		}
		
		if( is_array( $bad_asns) && !empty( $bad_asns ) ){
			if(isset( $ip_lookup_results['asn']['asn'] ) ){
				if( in_array( $ip_lookup_results['asn']['asn'], $bad_asns ) ){
				$spam_reason = "IP Address ({$ip_address}) belongs to a banned ASN: ". $ip_lookup_results['asn']['asn'];
					wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
					return true;
				}
			}
		}
	}
	
	return $is_spam;
}


add_filter( 'gform_entry_is_spam', 'wphacks_unmark_as_spam_allowlist_domains_on_email_fields', 10, 3 );
function wphacks_unmark_as_spam_allowlist_domains_on_email_fields( $is_spam, $form, $entry ) {
	if( $is_spam === false){
		return $is_spam;
	}
	
	$allowlist_domains = array(
		'example.org'
	);
	
	$allowlist_domains = apply_filters('wphacks_gravityforms_anti_spam_collections_allowlist_domains_on_email_field', $allowlist_domains );
	
	if(!empty( $allowlist_domains ) ){
		if( isset( $form['fields'] ) ){
			foreach( $form['fields'] as $field ){
				if( $field->type == "email"){
					$email = rgar( $entry, $field->id);
					$email_explode = explode( '@', $email );
						if( is_array( $email_explode ) ){
							$email_domain = end( $email_explode );
						
						if( in_array( strtolower( trim( $email_domain ) ), $allowlist_domains ) ){
							return false;
						}
					}
				}
			}
		}
	}
	return $is_spam;
}

add_filter( 'gform_entry_is_spam', 'wphacks_mark_as_spam_phony_us_phone_number_fields', 10, 3 );
function wphacks_mark_as_spam_phony_us_phone_number_fields( $is_spam, $form, $entry ) {
	if( $is_spam ){
		return $is_spam;
	}
	
	$bad_phone_numbers = array();
	
	$bad_phone_numbers = apply_filters('wphacks_gravityforms_anti_spam_collections_allowlist_domains_on_email_field', $bad_phone_numbers );
	
	
	if( isset( $form['fields'] ) ){
		foreach( $form['fields'] as $field ){
			if ( $field->type == 'phone' || ( $field->type == 'text' && $field->inputMask == 1 && $field->inputMaskValue == '(999) 999-9999? x99999') ){
				
				$phone = rgar( $entry, $field->id );
				
				$phone_number_only = implode( "", array_filter( str_split( $phone ), function( $array ){ return is_numeric( $array ); } ) );
				
				if(count( array_unique( array_filter( str_split( $phone_number_only ),function( $array ){ return is_numeric( $array ); } ) ) ) === 1){
					$spam_reason = 'Field '. $field->id .' uses a phone number consisting entirely of the same digit.';
					wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
					return true;
				}
				
				if( substr( $phone_number_only, 3, 7) == 8675309 ){
					$spam_reason = 'Unless the form was submitted by Jenny, Field '. $field->id .' has a fake phone number.';
					wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
					return true;
				}
				

				//for 10 digit phone numbers
				echo $phone_number_only;
				if( strlen( $phone_number_only ) === 10 ) {
					echo substr( $phone_number_only, 7, 3 );
					if( in_array( substr( $phone_number_only, 3, 4 ), array( 5550 )  ) && in_array( substr( $phone_number_only, 7, 3 ), range('100','199') ) ){
						$spam_reason = 'Field '. $field->id .' uses an fictional number.';
						wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
						return true;
					}
					
					$unassigned_us_area_codes = array(
						221,
						230,
						232,
						237,
						238,
						241,
						243,
						247,
						258,
						259,
						261,
						265,
						271,
						273,
						275,
						278,
						280,
						282,
						285,
						286,
						287,
						328,
						335,
						338,
						342,
						348,
						349,
						356,
						358,
						359,
						381,
						383,
						384,
						389,
						420,
						421,
						426,
						427,
						429,
						439,
						446,
						449,
						451,
						452,
						453,
						454,
						456,
						459,
						461,
						462,
						465,
						467,
						471,
						476,
						481,
						482,
						485,
						486,
						487,
						489,
						536,
						560,
						565,
						575,
						583,
						625,
						627,
						632,
						634,
						635,
						637,
						642,
						643,
						648,
						652,
						653,
						654,
						663,
						665,
						668,
						673,
						674,
						675,
						676,
						685,
						687,
						723,
						735,
						736,
						739,
						741,
						745,
						746,
						749,
						750,
						751,
						752,
						756,
						759,
						761,
						764,
						768,
						783,
						789,
						823,
						824,
						827,
						834,
						836,
						841,
						842,
						846,
						852,
						853,
						871,
						874,
						875,
						921,
						923,
						926,
						927,
						932,
						935,
						953,
						957,
						958,
						974,
						976,
						981,
						982,
						987
					);
					if( in_array(substr( $phone_number_only, 0, 3), $unassigned_us_area_codes ) ){
						$spam_reason = 'Field '. $field->id .' uses an unassigned area code.';
						wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
						return true;
					}
					
					$bad_area_codes = array_merge(range(000,199), range(990,999) );
					$bad_area_codes = array_map( function( $item ){ return str_pad( $item, 3,0, STR_PAD_LEFT ); }, $bad_area_codes);
					
					
					if( in_array(substr( $phone_number_only, 0, 3), $bad_area_codes ) ){
						
						$spam_reason = 'Field '. $field->id .' has a bad area code.';
						wphacks_anti_spam_collection_log_spam_reason( $entry, $spam_reason, $form );
						return true;
					}
				} else {
					
				}
			}
		}
	}
	return $is_spam;
}
