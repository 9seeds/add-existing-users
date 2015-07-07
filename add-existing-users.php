<?php
/*
Plugin Name: Add Existing Users
Description: This plugin allows you to add user accounts from one sub-site to another
Version: 2.0.0
Author: 9seeds
Author URI: http://9seeds.com
*/

function aeu_menu() {
	if ( is_multisite() ) {
		add_submenu_page( 'addmultiple',
						  __('AMU Add from Site','amulang'),
						  __('Add from Site','amulang'),
						  'manage_options',
						  'amuaddfromsite',
						  'aeu_addfromsite');
	}

}
add_action( 'admin_menu', 'aeu_menu', 11 );

include('functions/networkoptions.php');
