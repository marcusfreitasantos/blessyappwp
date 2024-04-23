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


function sendUserDataAfterJWTAuth($jwt, $user){
    $userAvatarUrl = wp_get_attachment_image_url( get_user_meta($user->ID, "avatar", true), "medium" );
	$userBookmarks = get_user_meta($user->ID, 'user_bookmarked_churches', true);

	if(!in_array('church', $user->roles)){
		$data = array(
			'token' => $jwt['token'],
			'userID' => $user->ID,
			'email' => $user->user_email,
			'firstName' => $user->first_name,
			'lastName' => $user->last_name,
			'avatar' => $userAvatarUrl,
			'bookmarks' => $userBookmarks,
		);
		return $data;
	}else{
		return new WP_Error( 'forbidden', "User not allowed.", array( 'status' => 403 ) );
	}
}
add_filter('jwt_auth_token_before_dispatch', 'sendUserDataAfterJWTAuth', 10, 2);


function updateUserById($req){
	$reqBody = json_decode(file_get_contents('php://input'));
	$userData = [
		'ID' => $req['id'],
		'user_email' => $reqBody->email,
		'first_name' => $reqBody->firstName,
		'last_name' => $reqBody->lastName,
		'user_pass' => $reqBody->userPass,
	];

	if ( is_wp_error( wp_update_user($userData) ) ) {
    	return new WP_Error( 'not_found', "User couldn't be updated.", array( 'status' => 404 ) );
	} else {
		$userNewData = [
			'userID' => $req['id'],
			'email' => $reqBody->email,
			'firstName' => $reqBody->firstName,
			'lastName' => $reqBody->lastName,
		];
		return rest_ensure_response($userNewData);
	}
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)', array(
    'methods' => 'POST',
    'callback' => 'updateUserById',
  ) );
} );


function getUserBookmarks($req){
	$userId = $req['id'];
	$userBookmarkedChurches = get_user_meta($userId, 'user_bookmarked_churches', true);
	$allChurches = [];
	$allChurchesWithCustomFields = [];

	foreach($userBookmarkedChurches as $churchId){
		$currentUserBookmarkedChurch = get_user_by('id', $churchId);
		if($currentUserBookmarkedChurch){
			$allChurches[] = get_user_by('id', $churchId);
		}
	}	

    if(get_user_by('id', $req['id'])){
      foreach($allChurches as $church){
          $churchAddress = get_user_meta($church->id, "church_address", true);
          $churchLogoID = get_user_meta($church->id, "church_logo", true);
          $churchLogoUrl = wp_get_attachment_image_url( $churchLogoID, "large" );
  
          $allChurchesWithCustomFields[] = [
              "id" => $church->id,
              "name" => $church->first_name,
              "address" => $churchAddress,
              "logo" => $churchLogoUrl
          ];
  
      }
  
      return rest_ensure_response($allChurchesWithCustomFields);
    }else{
      return new WP_Error( 'not_found', 'User not found.', array( 'status' => 404 ) );
    }
}


add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/bookmark', array(
    'methods' => 'GET',
    'callback' => 'getUserBookmarks',
  ) );
} );


function saveUserBookmarks($req){
	$userId = $req['id'];
	$reqBody = json_decode(file_get_contents('php://input'));
	
	if(get_user_by('id', $userId)){
		$userBookmarkedChurches = get_user_meta($userId, 'user_bookmarked_churches', true);
		if($userBookmarkedChurches){
			if(!in_array($reqBody->churchId, $userBookmarkedChurches)){
				$userBookmarkedChurches[] =  $reqBody->churchId;
			}
				
		}else{
			$userBookmarkedChurches = [$reqBody->churchId];
		}

		update_user_meta($userId, 'user_bookmarked_churches', $userBookmarkedChurches);
		saveChurchFollowers($userId, intval($reqBody->churchId));
		return rest_ensure_response($userBookmarkedChurches);
	}else{
		return new WP_Error( 'not_found', "User not found.", array( 'status' => 404 ) );
	}
}


add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/bookmark', array(
    'methods' => 'POST',
    'callback' => 'saveUserBookmarks',
  ) );
} );


function removeUserBookmarks($req){
	$userId = $req['id'];
	
	if(get_user_by('id', $userId)){
		$userBookmarkedChurches = get_user_meta($userId, 'user_bookmarked_churches', true);
		$newUserBookmarkedChurches = [];

		if($userBookmarkedChurches){
			foreach ($userBookmarkedChurches as $churchId) {
				if($churchId !== intval($req['church_id'])){
					$newUserBookmarkedChurches[] = $churchId;
				}
			}
			
			update_user_meta($userId, 'user_bookmarked_churches', $newUserBookmarkedChurches);
			removeChurchFollowers($userId, $req['church_id']);			
			return rest_ensure_response($newUserBookmarkedChurches);
		}
	}else{
		return new WP_Error( 'not_found', "User not found.", array( 'status' => 404 ) );
	}
}


add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/bookmark/(?P<church_id>\d+)', array(
    'methods' => 'DELETE',
    'callback' => 'removeUserBookmarks',
  ) );
} );


function saveUserDeviceToken($req) {
	$userId = $req['id'];
	$currentUser = get_user_by('id', $userId);
	$userDeviceToken = json_decode(file_get_contents('php://input'))->userDeviceToken;

	if($currentUser && !in_array("church", $currentUser->roles)){
		update_user_meta($userId, 'user_device_token', $userDeviceToken);

		$response = [
			"id" => $currentUser->id,
			"firstName" => $currentUser->first_name,
			"email" => $currentUser->user_email,
			"userDeviceToken" => get_user_meta($currentUser->id, 'user_device_token', true)
		];

		return $response;
	}else{
		return new WP_Error( 'not_found', "User not found.", array( 'status' => 404 ) );
	}

}


add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/devices', array(
    'methods' => 'POST',
    'callback' => 'saveUserDeviceToken',
  ) );
} );


function getUserNotifications($req){
	$currentUser = get_user_by('id', $req['id']);
	if($currentUser && !in_array('church', $currentUser->roles)){
		$userNotifications = get_user_meta($req['id'], 'blessy_user_notifications', true);
		return array_reverse($userNotifications);

	}else{
		return new WP_Error( 'not_found', "User not found.", array( 'status' => 404 ) );
	}
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/notifications', array(
    'methods' => 'GET',
    'callback' => 'getUserNotifications',
  ) );
} );


function resetUserPassword(){
	global $headers;
	$reqBody = json_decode(file_get_contents('php://input'));
	$user = get_user_by('email', $reqBody->userEmail);
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$passwordCharactersLength = 8;
	$newUserRandomPassword = substr(str_shuffle($characters), 0, $passwordCharactersLength);
	$subject = '[Blessy] Recuperação de senha';

	$message = "
		Olá $user->first_name,<br><br>
		recebemos uma solicitação para alterar sua senha. Se não foi você, entre em contato com <a href='mailto:suporte@blessyapp.com'>nosso suporte.</a> <br><br>

		Suas credenciais de acesso são: <br>
		<strong>Login:</strong> $user->user_email<br>
		<strong>Senha:</strong> $newUserRandomPassword<br><br>

		Aconselhamos alterar a sua senha na próxima vez que entrar no app.

		<p style='font-family: Helvetica, Arial, sans-serif; font-size: 13px;line-height: 1.5em;'>Atenciosamente,<br>
		equipe Blessy.</p>
	";
	
	if($user){
		wp_set_password($newUserRandomPassword, $user->id);
		wp_mail($reqBody->userEmail, $subject, $message, $headers);

		return "New password is: $newUserRandomPassword";
	}else{
		return new WP_Error( 'not_found', "User not found.", array( 'status' => 404 ) );
	}
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/users/reset-password', array(
    'methods' => 'POST',
    'callback' => 'resetUserPassword',
  ) );
} );