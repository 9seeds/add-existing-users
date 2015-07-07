<?php

function aeu_addfromsite() {
	//test again for admin priviledges
	if (!current_user_can('manage_options') )  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	//set network options if they don't exist
	amu_set_default_network_options();
	
	//test if disabled by superadmin
	if ( is_multisite() ) {
		if (get_site_option('amu_subadminaccess')) {
			if (get_site_option('amu_subadminaccess') == 'no') {
				if (!current_user_can('manage_network') )  {
					wp_die( __('Access to AMU functions have been disabled by the Network Administrator.') );
				}
			}
		}
	}
	
	//test if disabled by superadmin
	if ( is_multisite() ) {
		if (get_site_option('amu_addexistingaccess')) {
			if (get_site_option('amu_addexistingaccess') == 'no') {
				if (!current_user_can('manage_network') )  {
					wp_die( __('Access to this function has been disabled by the Network Administrator.') );
				}
			}
		}
	}
	
	//when accessing amu, set options if they don't exist
	amu_set_defaultoptions();
	
	//begin wrap class
	echo '<div class="wrap">';
		echo '<div id="amu">';
		
			echo '<h2>'.__('Add Multiple Users - Add from Site','amulang').'</h2>';

			if ( ! isset( $_GET['site_id'] ) ) {
				//select site first to get user list
				aeuShowSites();
				
			} else {			
				aeuShowSites( $_GET['site_id'] );
				//if no post made, show interface and helpers
				if (empty($_POST) ) {
				
					$userListError = '';
					amuGetUserListHead($userListError);
				
					aeuShowSiteUsers( $_GET['site_id'] );
				
					$infotype = 'addexistusers';
					showPluginInfo($infotype);
				
					//otherwise, run add existing function
				} else if ( isset($_POST['addexistingusers'] ) ) {
				
					amuAddNetworkUsers();
				
					//else throw error
				} else {
					echo '<p>'.__('Unknown request. Please select the Add from Network option to try again.','amulang').'<p>';
					echo '<pre>'.print_r($_POST,true).'</pre>';
				}
			}
			
		echo '</div>';
	echo '</div>';
	
}

function aeuShowSiteUsers( $site_id ) {
	
	echo '<form method="post" enctype="multipart/form-data" class="amuform">';
			
		//get this blogs id
		global $wpdb, $blog_id, $wp_roles;
		$mainsite = BLOG_ID_CURRENT_SITE;
		$from_capabilities = 'wp_'.$site_id.'_capabilities';
		$to_capabilities = 'wp_'.$blog_id.'_capabilities';
		$lastuser = '';
		$usertotal = 0;
		
		echo '<h3>'.__('Add Network Users Options').'</h3>';

		$limit = 400;
		$offset = 0;
		if( isset( $_GET['paged'] ) && ! empty( $_GET['paged'] ) ) {
			$offset =  ($_GET['paged']-1) * $limit; // (page 2 - 1)*10 = offset of 10
		}
			
		//show users list
		$allusers = $wpdb->get_results(
			"SELECT 
				ID, user_login, user_email 
			 FROM {$wpdb->users}
			 ORDER BY ID ASC
			 LIMIT {$limit} OFFSET {$offset}"
		);
		if ($wpdb->num_rows == 0) {
			echo '<p>'.__('You have no available users to add.','amulang').'</p>';
		} else {
			//show multisite options wrapped in genoption
			echo '<div class="genoptionwrap">';
			
			if (get_option('amu_setallroles')) {
				$rolesel = get_option('amu_setallroles');
			} else {
				$rolesel = 'notset';
			}
			$roles = $wp_roles->get_names();
			//set all users to this role?
			echo '<div class="optionbox">';
			echo '	<label for="existingToRole">'.__('Ignore individual roles and set all selected users to this role','amulang').': </label>';
			echo '	<select name="existingToRole" id="existingToRole">';
			echo '		<option value="notset"'; if($rolesel=='notset'){echo ' selected="selected" ';} echo '>'.__('no, set individually...','amulang').'</option>';

				foreach($roles as $role) {
					$thisrole = $role;
					echo '<option value="'.strtolower($thisrole).'"'; if(strtolower($rolesel)==strtolower($thisrole)){echo ' selected="selected" ';} echo '>'.$thisrole.'</option>';
				}
			echo '	</select>';
			echo '</div>';
			
			//username strict validation option...
			echo '<div class="optionbox lastoption">';
			echo '	<label for="notifyExistingUser">'.__('Send each user a confirmation email?','amulang').' <span class="important">('.__('if selected, sends user standard WordPress confirmation email','amulang').')</span></label>';
			echo '	<input name="notifyExistingUser" id="notifyExistingUser" type="checkbox" value="sendnotification" />';
			echo '</div>';
			
			//end multisite options wrap
			echo '</div>';
			
			echo '	<h3><strong>'.__('Select network users to add to this site','amulang').':</strong></h3>';
			
			
			
			//start fieldset wrap
			echo '<div class="fieldsetwrap">';
			
			//show check all option
			echo '<div class="userline wrapwhite checkallex">';
				echo '<input name="checkallexisting" id="checkallexisting" type="checkbox" value="goforall" />';
				echo '<label for="checkallexisting">'.__('Select All','amulang').'</label>';
			echo '</div>';
							
			//show user rows
			foreach ( $allusers as $user ) {
				//if on main site
				if($blog_id == $mainsite) {
					if(!get_user_meta($user->ID, 'wp_capabilities') && get_user_meta($user->ID, $from_capabilities)) {
						
						//start print
						if ($usertotal & 1) {
							echo '<div class="userline wrapwhite">';
						} else {
							echo '<div class="userline wrapgrey">';
						}
						
						echo '	<input name="adduser_'.$user->ID.'" id="adduser_'.$user->ID.'" class="userbox" type="checkbox" value="userchecked" />';
						echo '	<label for="adduser_'.$user->ID.'"><span class="eu_userid"><strong>'.__('User ID','amulang').':</strong> '.$user->ID.'</span><span class="eu_userlogin"><strong>'.__('User Login','amulang').':</strong> '.$user->user_login.'</span><span class="eu_useremail"><strong>'.__('User Email','amulang').':</strong> '.$user->user_email.'</span></label>';
						echo '	<select name="setrole_'.$user->ID.'" id="setrole_'.$user->ID.'">';
						foreach($roles as $role) {
							$thisrole = $role;
							echo '<option value="'.strtolower($thisrole).'">'.$thisrole.'</option>';
						}
						echo '	</select>';
						echo '</div>';
						$lastuser = $user->ID;
						$usertotal++;
					}
				} else {
					//if on subsite
					if(!get_user_meta($user->ID, $to_capabilities) && get_user_meta($user->ID, $from_capabilities) ) {
						
						//start print
						if ($usertotal & 1) {
							echo '<div class="userline wrapwhite">';
						} else {
							echo '<div class="userline wrapgrey">';
						}
						
						echo '	<input name="adduser_'.$user->ID.'" id="adduser_'.$user->ID.'" class="userbox" type="checkbox" value="userchecked" />';
						echo '	<label for="adduser_'.$user->ID.'"><span class="eu_userid"><strong>'.__('User ID','amulang').':</strong> '.$user->ID.'</span><span class="eu_userlogin"><strong>'.__('User Login','amulang').':</strong> '.$user->user_login.'</span><span class="eu_useremail"><strong>'.__('User Email','amulang').':</strong> '.$user->user_email.'</span></label>';
						echo '	<select name="setrole_'.$user->ID.'" id="setrole_'.$user->ID.'">';
						foreach($roles as $role) {
							$thisrole = $role;
							echo '<option value="'.strtolower($thisrole).'">'.$thisrole.'</option>';
						}
						echo '	</select>';
						echo '</div>';
						$lastuser = $user->ID;
						$usertotal++;
					}
				}
				
			}
			if($usertotal == 0) {
				echo '</div>';
				echo '<div class="toolintro">';
				echo '<p class="amu_error">'.__('All users on your Network are already assigned a role on this site.','amulang').'</p>';
				echo '</div>';
			} else {
				echo '<input type="hidden" readonly="readonly" name="existprocs" id="existprocs" value="'.$lastuser.'" />';
				echo '</div>';
				//show add button
				echo '<div class="buttonline">';
					echo '	<input type="submit" name="addexistingusers" class="button-primary" value="'.__('Add Selected Users','amulang').'" />';
				echo '</div>';
			}
		}
		
	echo '</form>';
			
}

function aeuShowSites( $site_id = NULL ) {
	global $wpdb;


	$blogs = $wpdb->get_results(
		"SELECT * 
		 FROM {$wpdb->blogs}
		 WHERE public = 1
		 AND archived = 0
		 AND spam = 0
		 AND deleted = 0
		",
		ARRAY_A );

	
	echo '<form method="get" action="" class="amuform">';
	echo '<div class="genoptionwrap">';
			
	//set all users to this role?
	echo '<div class="optionbox">';
	echo '	<label for="existingToSite">'.__('Select site to add users from','amulang').': </label>';
	echo '	<select name="site_id" id="existingToSite">';

	foreach($blogs as $blog) {
		
		echo '<option value="'.strtolower($blog['blog_id']).'"';
		selected( $site_id, $blog['blog_id'] );
		echo '>'.$blog['domain'].$blog['path'].'</option>';
	}
	echo '	</select>';
	echo '</div>';
	//show select button
	echo '<div class="buttonline">';
	echo '	<input type="submit" class="button-primary" value="'.__('Select Site','amulang').'" />';
	echo '	<input type="hidden" name="page" value="'.$_GET['page'].'" />';
	echo '</div>';
	echo '</div>';
	echo '</form>';

}