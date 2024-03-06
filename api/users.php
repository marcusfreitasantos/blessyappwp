<?php
function registerUsersFromApp($req){
	$userFullName = $req['userFullName'];
	$username = generateUniqueUsername($req['username']);
	$userPassword = $req['userPass'];
	$userEmail = $req['userEmail'];

	if($username){;
		$newUserId = wp_create_user($username, $userPassword, $userEmail);
	
		if(is_wp_error( $newUserId )){
			$errorMsg = $newUserId->get_error_message();
			return new WP_Error( 'not_found', $errorMsg, array( 'status' => 401 ) );
		
		}else{
			wp_update_user( array( 'ID' => $newUserId, 'first_name' => $userFullName ) );
			return "Success: User registered";
		}
	}

}



add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users', array(
    'methods' => 'POST',
    'callback' => 'registerUsersFromApp',
  ) );
} );



function generateUniqueUsername($username) {

	$username = sanitize_title( $username );

	static $i;
	if ( null === $i ) {
		$i = 1;
	} else {
		$i ++;
	}
	if ( ! username_exists( $username ) ) {
		return $username;
	}
	$new_username = sprintf( '%s-%s', $username, $i );
	if ( ! username_exists( $new_username ) ) {
		return $new_username;
	} else {
		return call_user_func( __FUNCTION__, $username );
	}
}


function getUserByEmail($req){
	$user = get_user_by('email', $req['email']);
	$userObj = [];

	if($user){
		$userObj = [
			"ID" => $user->ID,
			"firstName" => $user->first_name,
			"lastName" => $user->last_name,
			"email" => $user->user_email,
		];
		return rest_ensure_response($userObj);
	}else{
		return new WP_Error( 'not_found', "User not found.", array( 'status' => 404 ) );
	}
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<email>\S+)', array(
    'methods' => 'GET',
    'callback' => 'getUserByEmail',
  ) );
} );