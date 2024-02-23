<?php

function getAllChurches(){
    $allChurches = get_users( array( 'role__in' => array( 'church') ) );
    $allChurchesWithCustomFields = [];

    if($allChurches){
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
      return new WP_Error( 'not_found', 'No churches found.', array( 'status' => 404 ) );
    }

}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/church', array(
    'methods' => 'GET',
    'callback' => 'getAllChurches',
  ) );
} );


function getChurchById($reqData){
	$churchId = $reqData["id"];
    $currentChurch = get_user_by("id", $churchId);

	if($currentChurch){
		$churchDescription = get_user_meta($churchId, "church_description", true);
		$churchAddress = get_user_meta($churchId, "church_address", true);
		
		$churchLogoID = get_user_meta($churchId, "church_logo", true);
		$churchLogoUrl = wp_get_attachment_image_url( $churchLogoID, "large" );
	
			
		$churchCoverImgID = get_user_meta($churchId, "church_cover_img", true);
		$churchCoverImg = wp_get_attachment_image_url( $churchCoverImgID, "large" );
	
		$churchData = [
			"id" => $churchId,
			"name" => $currentChurch->first_name,
			"address" => $churchAddress,
			"description" => $churchDescription,
			"logo" => $churchLogoUrl,
			"coverImg" => $churchCoverImg
		];
	
		return rest_ensure_response($churchData);
	}else{
    return new WP_Error( 'not_found', 'No church found with this ID.', array( 'status' => 404 ) );
	}
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/church/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'getChurchById',
  ) );
} );



function getChurchContent($reqData){
  $postType = $reqData['content'];

  $posts = get_posts(array(
    'post_type' => $postType,
    'author' => $reqData['id'],
    'post_status' => 'publish',
    'numberposts'      => 50
  ));

  $formatedPosts = [];

  if($posts){
    foreach($posts as $post){
      $formatedPosts[] = [
        "id" => $post->ID,
        "churchId" => $post->post_author,
        "postDate" => $post->post_date,
        "postTitle" => $post->post_title,
        "postContent" => sanitize_text_field($post->post_content),
      ];
    }

    return rest_ensure_response($formatedPosts);
  }else{
    return new WP_Error( 'not_found', "No $postType found", array( 'status' => 404 ) );
  }
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/church/(?P<id>\d+)/(?P<content>[a-z]+)', array(
    'methods' => 'GET',
    'callback' => 'getChurchContent',
  ) );
} );