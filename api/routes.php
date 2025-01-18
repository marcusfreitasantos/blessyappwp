<?php

//USERS ROUTES
add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/users', array(
      'methods' => 'POST',
      'callback' => 'registerUsersFromApp',
    ) );
  } );



  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)', array(
      'methods' => 'POST',
      'callback' => 'updateUserById',
    ) );
  } );

  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/bookmark', array(
      'methods' => 'GET',
      'callback' => 'getUserBookmarks',
    ) );
  } );

  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/bookmark', array(
      'methods' => 'POST',
      'callback' => 'saveUserBookmarks',
    ) );
  } );

  
  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/bookmark/(?P<church_id>\d+)', array(
      'methods' => 'DELETE',
      'callback' => 'removeUserBookmarks',
    ) );
  } );

  
  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/devices', array(
      'methods' => 'POST',
      'callback' => 'saveUserDeviceToken',
    ) );
  } );

  

add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/users/(?P<id>\d+)/notifications', array(
      'methods' => 'GET',
      'callback' => 'getUserNotifications',
    ) );
  } );

  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/users/reset-password', array(
      'methods' => 'POST',
      'callback' => 'resetUserPassword',
    ) );
  } );

  //CHURCHES ROUTES
  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/church', array(
      'methods' => 'GET',
      'callback' => 'getAllChurches',
    ) );
  } );

  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/church/(?P<id>\d+)', array(
      'methods' => 'GET',
      'callback' => 'getChurchById',
    ) );
  } );

  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/church/(?P<id>\d+)/content/(?P<content>[a-z]+)', array(
      'methods' => 'GET',
      'callback' => 'getChurchContent',
    ) );
  } );

  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/church/(?P<id>\d+)/content/(?P<content>[a-z]+)/(?P<post_id>\d+)', array(
      'methods' => 'GET',
      'callback' => 'getChurchSingleContent',
    ) );
  } );

  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/church/search/(?P<keyword>[a-zA-Z0-9]+)', array(
      'methods' => 'GET',
      'callback' => 'searchChurchByKeyword',
    ) );
  } );



add_action( 'rest_api_init', function () {
  register_rest_route( 'blessyapp/v2', '/church/searchbymeta', array(
    'methods' => 'GET',
    'callback' => 'searchChurchByMetadata',
  ) );
} );

add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/church/(?P<id>\d+)/followers', array(
      'methods' => 'GET',
      'callback' => 'getAllChurchFollowers',
    ) );
  } );

  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/church_ad', array(
      'methods' => 'GET',
      'callback' => 'getAllChurchAds',
    ) );
  } );

  //NEWS ROUTES
  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/news/create', array(
      'methods' => 'POST',
      'callback' => 'createNews',
    ) );
  } );

  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/news/delete', array(
      'methods' => 'POST',
      'callback' => 'deleteNews',
    ) );
  } );

  add_action( 'rest_api_init', function () {
    register_rest_route( 'blessyapp/v2', '/news/update', array(
      'methods' => 'POST',
      'callback' => 'updateNews',
    ) );
  } );
  
  