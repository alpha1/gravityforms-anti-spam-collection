<?php
/*
Plugin Name: WP Hacks Gravity Forms Anti-Spam Collection
Description: 
Author: WP Hacks
Version: 0.1.0
Author URI: https://wphacks.org
*/

/*
lowercase all email addresses
*/
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

add_filter( 'gform_entry_is_spam', 'mark_webhosts_as_spam_using_ipdata_co', 10, 3 );
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
				return true;
			}
		}
		
		//known attackers
		if( true === apply_filters( 'wphacks_gravityforms_mark_known_attackers_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_known_attacker'] ){
				return true;
			}
		}
		
		//datacenters
		if( true === apply_filters( 'wphacks_gravityforms_mark_webhosts_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_datacenter'] ){
				return true;
			}
		}

		//bogons
		if( true === apply_filters( 'wphacks_gravityforms_mark_bogon_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_bogon'] ){
				return true;
			}
		}

		//threats
		if( true === apply_filters( 'wphacks_gravityforms_mark_threats_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_threat'] ){
				return true;
			}
		}
		
		//is_anonymous
		if( true === apply_filters( 'wphacks_gravityforms_mark_anonymous_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_anonymous'] ){
				return true;
			}
		}	

		//is_proxy
		if( true === apply_filters( 'wphacks_gravityforms_mark_proxy_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_proxy'] ){
				return true;
			}
		}
		
		//is_proxy
		if( true === apply_filters( 'wphacks_gravityforms_mark_tor_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_tor'] ){
				return true;
			}
		}

		//icloud_relays
		if( true === apply_filters( 'wphacks_gravityforms_mark_icloud_relay_as_spam_using_ipdata_co', true ) ){
			if( true === $ip_lookup_results['threat']['is_icloud_relay'] ){
				return true;
			}
		}
		
		if( is_array( $bad_asns) && !empty( $bad_asns ) ){
			if(isset( $ip_lookup_results['asn']['asn'] ) ){
				if( in_array( $ip_lookup_results['asn']['asn'], $bad_asns ) ){
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
