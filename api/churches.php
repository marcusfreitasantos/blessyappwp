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
      $formatDate = date_i18n("d \d\\e F, Y", strtotime($post->post_date));
      $formatedPosts[] = [
        "id" => $post->ID,
        "churchId" => $post->post_author,
        "postDate" => $formatDate,
        "postTitle" => $post->post_title,
        "postExcerpt" => sanitize_text_field(get_field('post_excerpt', $post->ID)),
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



function getChurchSingleContent($reqData){
  $postTypeRequested = $reqData['content'];
  $currentPostType = get_post_type($reqData['post_id']);
  $contentPost = get_post($reqData['post_id']);  

  if($contentPost && ($currentPostType === $postTypeRequested)){
      $paragraphsGroup = get_field('paragraph_group', $contentPost->ID);
      $formatedPost = [];
      $paragraphs = [];
      $formatDate = date_i18n("d \d\\e F, Y", strtotime($contentPost->post_date));

      if($paragraphsGroup){
        foreach($paragraphsGroup as $paragraphGroup){
          $paragraphs[] = $paragraphGroup;
        }
      }

      $formatedPost = [
        "id" => $contentPost->ID,
        "churchId" => $contentPost->post_author,
        "postDate" => $formatDate,
        "postTitle" => $contentPost->post_title,
        "postExcerpt" => sanitize_text_field(get_field('post_excerpt', $contentPost->ID)),
        "postContent" => $paragraphs,
      ];

    return rest_ensure_response($formatedPost);
  }else{
    return new WP_Error( 'not_found', "No $postTypeRequested found", array( 'status' => 404 ) );
  }
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/church/(?P<id>\d+)/(?P<content>[a-z]+)/(?P<post_id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'getChurchSingleContent',
  ) );
} );