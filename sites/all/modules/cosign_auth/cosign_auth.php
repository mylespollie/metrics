<?php
// $Id: cosign_auth.module,v 1.9.2.3 2011/04/13 17:24:41 bdr Exp $
// bdr:  I modified the cosign.module to work with Drupal 7 
//       Note: this module does not use/depend on webserver_auth module
//             if a user can authenticate using Cosign, and the user is
//             found in the "users" table, then the user is permitted
//             authorized access using the Drupal roles/permissions
//       all the "// dpm(..." are debugging stmts that can 
//       be uncommented if necessary

/**
 * @file
 * The Cosign Module
 *
 * This module manages automatic user login and supplies cosign logout bar.
 * It depends on the webserver auth module.
 */

function cosign_auth_init() {
  global $user;
  //  dpm($_SERVER, 'bdr: cosign_auth_init');
  if ($_GET['q'] == 'user/logout') {
    //  dpm($_SERVER, 'bdr: cosign_auth_init **LOGOUT**');
    cosign_auth_logout();
    return TRUE;
  }

  // bdr: D7 changes to simplify things
  if ($user->uid) {

  // Make sure we get the remote user whichever way it is available.
  if (isset($_SERVER['REDIRECT_REMOTE_USER'])) {
    $cosign_auth_name = $_SERVER['REDIRECT_REMOTE_USER'];
  }
  elseif (isset($_SERVER['REMOTE_USER'])) {
    $cosign_auth_name = $_SERVER['REMOTE_USER'];
  }

     
//ldap check from Schleif
//cosign_auth_assign_ldap($username, $ldapGroup, $drupalRole);
if ((strpos($_SERVER['SERVER_NAME'],'developer.it') !== false)) {
cosign_auth_assign_ldap($cosign_auth_name, 'ITS.AllStaff', 'ITS AllStaff');
cosign_auth_assign_ldap($cosign_auth_name, 'Campus Web Services', 'ITS AllStaff');
}	 	 
	 
	 // Do nothing: user already logged in Drupal with session data matching
     //  dpm($user, 'bdr: cosign_auth_init ***ALREADY LOGGED IN USER DO NOTHING***');
     return TRUE;
	 
	 
  }

  //  dpm($_SERVER, 'bdr: cosign_auth_init ** PERFORM LOGIN **');
  $cosign_auth_name = '';

  // Make sure we get the remote user whichever way it is available.
  if (isset($_SERVER['REDIRECT_REMOTE_USER'])) {
    $cosign_auth_name = $_SERVER['REDIRECT_REMOTE_USER'];
  }
  elseif (isset($_SERVER['REMOTE_USER'])) {
    $cosign_auth_name = $_SERVER['REMOTE_USER'];
  }

  // Perform some cleanup so plaintext passwords aren't available 
  unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

  if (!empty($cosign_auth_name)) {
         //  dpm($cosign_auth_name, 'bdr: cosign_auth_init ***COSIGN-NAME***');
      // Retrieve user credentials
      $result = db_select('users', 'u')
                  ->fields('u', array('uid', 'name'))
                  ->condition('name', $cosign_auth_name, '=')
                  ->execute();
      $user_info = $result->fetchObject();

      /***********************************************************
      * bdr - at this point, we have a valid cosign user who
      *       is "authorized" to use Drupal if the user
      *       is in the "users" table. If not, show the Drupal node
      *       chosen in the Cosign Admin Menu
      **********************************************************/
      if (empty($user_info)) {
          // dpm($user, 'bdr: cosign_auth_init ***USER-INFO-NO-USER-FOUND***');
		  
		  //Schleif edit - we don't care if they're in the users table, so
		  //if you pass Cosign, you just get added to it, so we can pass
		  //through authentication.
          
		  //Commented out next three lines (Schleif)
		  //$default_invalid_logout = 'user/logout';
          //$invalid_login = variable_get('cosign_auth_invalid_login', $default_invalid_logout);
          //drupal_goto($invalid_login);
		  
 
		//Schleif code
    	//generate a password (nobody will ever actually use it, though)
		$chars = "abcdefghijkmnopqrstuvwxyz023456789";
    	srand((double)microtime()*1000000);
    	$i = 0;
    	$pass = '' ;

	    while ($i <= 7) {
    	    $num = rand() % 33;
       	 $tmp = substr($chars, $num, 1);
       	 $pass = $pass . $tmp;
       	 $i++;
    	}

		$new_user = array(
			'name' => $cosign_auth_name,
			'pass' => $pass,
			'mail' => $cosign_auth_name."@umich.edu",
			'init' => $cosign_auth_name."@umich.edu",
			'field_firstname' => array('und' => array('0' => array('value' => 'test'))),
			'field_lastname' => array('und' => array('0' => array('value' => 'test'))),
			//'field_city' => array('und' => array('0' => array('value' => 'Ann Arbor'))),
			//'field_country' => array('und' => array('0' => array('value' => 'USA'))),
			'status' => 1,
			'roles' => array(DRUPAL_AUTHENTICATED_RID => TRUE),
			'access' => time(),
		);
		//add user
		$account = user_save(NULL, $new_user);		  
		
		//add/generate session info
		$user = user_load_by_name($cosign_auth_name);
		drupal_session_regenerate();
		//end Schleif code

//ldap check from Schleif
//cosign_auth_assign_ldap($username, $ldapGroup, $drupalRole);
if ((strpos($_SERVER['SERVER_NAME'],'developer.it') !== false)) {
cosign_auth_assign_ldap($cosign_auth_name, 'ITS.AllStaff', 'ITS AllStaff');
cosign_auth_assign_ldap($cosign_auth_name, 'Campus Web Services', 'ITS AllStaff');
}



       } 
      else {
           //  dpm($user, 'bdr: cosign_auth_init ***USER_EXTERNAL_LOAD***');
           $user = user_load_by_name($cosign_auth_name);

           /***************************************************************
            * bdr - added this line so only 1 web page "refresh" was needed
            *    to format the "admin_menu" correctly after cosign login
            ****************************************************************/
            drupal_session_regenerate();
			//ldap check from Schleif
			//cosign_auth_assign_ldap($username, $ldapGroup, $drupalRole);
			if ((strpos($_SERVER['SERVER_NAME'],'developer.it') !== false)) {
			cosign_auth_assign_ldap($cosign_auth_name, 'ITS.AllStaff', 'ITS AllStaff');
			cosign_auth_assign_ldap($cosign_auth_name, 'Campus Web Services', 'ITS AllStaff');
			}
      }
  }
  return TRUE;
}

// this is not a hook!  Defines the admin form which is referenced in the _menu hook.
function cosign_auth_admin() {
  // bdr: I replaced this line out because at UMich, we always use cosign server
  $logout_machine = 'https://weblogin.umich.edu/cgi-bin/logout';
  // $logout_machine = 'https://' . $_SERVER['SERVER_NAME'];
  $script_path = '/cgi-bin/logout';
  $logout_path = $logout_machine . $script_path;
  $form['cosign_auth_logout_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Logout Path'),
    '#default_value' => variable_get('cosign_auth_logout_path', $logout_path),
    '#size' => 80,
    '#maxlength' => 200,
    '#description' => t("The address (including http(s)) of the machine and script path for logging out."),
  );

  $logout_to =  'http://' . $_SERVER['SERVER_NAME'] . base_path();
  $form['cosign_auth_logout_to'] = array(
    '#type' => 'textfield',
    '#title' => t('Logout to'),
    '#default_value' => variable_get('cosign_auth_logout_to', $logout_to),
    '#size' => 80,
    '#maxlength' => 200,
    '#description' => t("The address to redirect users to after they have logged out."),
  );

  $invalid_login = 'http://' . $_SERVER['SERVER_NAME'] . base_path() . "invalidlogin";
  $form['cosign_auth_invalid_login'] = array(
    '#type' => 'textfield',
    '#title' => t('Page displayed for unAuthorized Cosign User'),
    '#default_value' => variable_get('cosign_auth_invalid_login', $invalid_login),
    '#size' => 80,
    '#maxlength' => 200,
    '#description' => t("The address of the server and page name displayed for unauthorized Cosign user. <b>***NOTE*** you MUST create this page!</b>"),
  );

  return system_settings_form($form);
}

function cosign_auth_menu() {
  $items['admin/config/system/cosign'] = array(
  // $items['admin/cosign'] = array(
    'title' => 'Cosign_Auth',
    'description' => 'Control the Cosign_auth module behavior',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('cosign_auth_admin'),
    'access arguments' => array('access administration pages'),
    'type' => MENU_NORMAL_ITEM,
  );
  return $items;
}

/***********************************************************
 * bdr - comment out some code here to that the Drupal
 *       app can "add a user".  This does not add an user
 *       to the Kerberos database, just to the Drupal app
 *       for authorization purposes.
 **********************************************************/
function cosign_auth_form_alter(&$form, &$form_state, $form_id) {
  if ($form_id == "user_admin_settings") {
    $form['registration']['#title'] = t( $form['registration']['#title'] ) . " (disabled by the cosign module) ";
    $form['registration']['#collapsible'] = TRUE;
    $form['registration']['#collapsed'] = TRUE;
    $form['registration']['user_register']['#default_value'] = 1;
    $form['email']['#title'] = t( $form['email']['#title'] ) . " (disabled by the cosign module) ";
    $form['email']['#collapsible'] = TRUE;
    $form['email']['#collapsed'] = TRUE;
    foreach ($form['registration'] as $k => $item) {
      if (is_array($item)) {
        $form['registration'][$k]['#disabled'] = TRUE;
      }
    }
    foreach ($form['email'] as $k => $item) {
      if (is_array($item)) {
        $form['email'][$k]['#disabled'] = TRUE;
      }
    }
  }

  if ($form_id == "user_login_block") {
    $form['#action'] = 'user/login';
    unset($form['name']);
    unset($form['pass']);
    unset($form['submit']);
    $form['links']['#value'] = '<a href="https://' . $_SERVER['HTTP_HOST'] . request_uri() . '">Login</a>';
  }

   if ($form_id == "user_login") {
     drupal_goto('https://' . $_SERVER['SERVER_NAME'] . base_path());
   }

  if ($form_id == "user_profile_form") {
    if (isset($form['account']['name'])) {
      $form['account']['name']['#type'] = 'hidden';
    }
    unset($form['account']['pass']);
  }
}

function cosign_auth_help($path, $arg) {
  switch ($path) {
    case 'admin/modules#description':
      return t("Allows users to authenticate via Cosign.");

    case "admin/help#cosign":
      return '<p>' . t("Allows users to authenticate via Cosign") . '</p>';
  }
}

function cosign_auth_logout() {
  global $user;
  /***********************************************
   * bdr - since it is possible to have valid    *
   *    cosign login but not be in the "users"   *
   *    table, one might need to do a cosign     *
   *    logout but not possess a Drupal "session *
   *    so check before doing a session_destroy  *
   **********************************************/
  // Destroy the current session:
  if (isset($user)) {
    session_destroy();
    $_SESSION = array();
  }

  module_invoke_all('user', 'user/logout', NULL, $user);

  // Load the anonymous user
  $user = drupal_anonymous_user();

  $default_logout_path = 'https://' . $_SERVER['SERVER_NAME'] . '/cgi-bin/logout';
  $default_logout_to =   'http://' . $_SERVER['SERVER_NAME'] . base_path();

  $logout_path = variable_get('cosign_auth_logout_path', $default_logout_path);
  $logout_to = variable_get('cosign_auth_logout_to', $default_logout_to);

  drupal_goto($logout_path . '?' . $logout_to);

  return TRUE;
}

function cosign_auth_block_info() {
  global $user;
  $blocks['cosign'] = array(
      'info' => t('Cosign status and logout'),
  ); 
  return $blocks; 
}

function cosign_auth_disable() {
  //  dpm($_SERVER, 'bdr: cosign_auth_disable');
  $module = 'cosign';

  db_update('system')
      ->fields(array(
            'status' => 0,
      ))
      ->condition('type', 'module')
      ->condition('name', $module)
      ->execute();
  drupal_set_message(t('Cosign module has been disabled'));
}

function cosign_auth_enable() {
  //  dpm($_SERVER, 'bdr: cosign_auth_enable');
  $errmsg = '';
  $realm = '';

  if (isset($_SERVER['REMOTE_REALM'])) {
    $realm = '@' . $_SERVER['REMOTE_REALM'];
  }

  // This is a fresh install, don't bother copying users
  if (basename($_SERVER['SCRIPT_NAME']) == "install.php") {
    drupal_set_message(t('Cosign module has been enabled'));
  }
  return TRUE;
}


//also not a hook
//added by Schleif
//the D7 LDAP module wasn't working and we needed an immediate solution
//so, this directly looks up users and assigns roles based on very
//specific conditions.  Hopefully, the LDAP module will get fixed eventually.
function cosign_auth_assign_ldap($user, $role_name, $ldapgroup) {
	
	$userinfo = user_load_by_name($user);
	$userid = $userinfo->uid;
	
$ldaphost = "ldap.umich.edu";  // your ldap servers
$ldapport = 389;                 // your ldap server's port number

// Connecting to LDAP
$ldapconn = ldap_connect($ldaphost, $ldapport)
          or die("Could not connect to $ldaphost");
		  
if ($ldapconn) {
	
	$ldapbind = ldap_bind($ldapconn);
	
		if ($ldapbind) {
			
			$dn = "ou=User Groups,ou=Groups,dc=umich,dc=edu";
			//ITS AllStaff
			$query = "(&(cn=".$ldapgroup.")(member=uid=".$user.",ou=People,dc=umich,dc=edu))";
			
			$result = ldap_search($ldapconn, $dn, $query);
			
			$ldapcount = ldap_count_entries($ldapconn,$result);
			if ($ldapcount > 0) {
				//http://www.computerminds.co.uk/articles/quick-tips-adding-role-user-drupal-7
					
				if ($role = user_role_load_by_name($role_name)) {
					
					// Get the rid from the roles table.
					$rid = $role->rid;

					$key = array_search($role_name, $userinfo->roles);
  						if ($key == FALSE) {
    						if ($rid != FALSE) {
     							$new_role[$rid] = $role_name;
      							$all_roles = $userinfo->roles + $new_role; // Add new role to existing roles.
      							user_save($userinfo, array('roles' => $all_roles));
    						}
  						}
					
					//watchdog log
					watchdog('LDAP-Role Log', 'Assigned '.$role->rid.' to userid: '.$userid, array(), WATCHDOG_DEBUG);	
				}	
			}
			else {
				watchdog('LDAP-Role Log', 'No role assigned to user '.$user, array(), WATCHDOG_DEBUG);	
			}
		}
	}		  	
}

