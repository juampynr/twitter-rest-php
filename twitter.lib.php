<?php
/**
 * @file
 * Integration layer to communicate with the Twitter REST API 1.1.
 * https://dev.twitter.com/docs/api/1.1
 *
 * Original work my James Walker (@walkah).
 * Upgrade to 1.1 by Juampy (@juampy72).
 */

/**
 * Exception handling class.
 */
class TwitterException extends Exception {}

/**
 * Primary Twitter API implementation class
 */
class Twitter {

  /**
   * @var $source the twitter api 'source'
   */
  protected $source = 'drupal';

  /**
   * Constructor for the Twitter class
   */
  public function __construct() {}

  /**
   * Get an array of TwitterStatus objects from an API endpoint
   */
  protected function get_statuses($path, $params = array(), $use_auth = FALSE) {
    $values = $this->call($path, $params, 'GET', $use_auth);
    // Check on successfull call
    if ($values) {
      $statuses = array();
      foreach ($values as $status) {
        $statuses[] = new TwitterStatus($status);
      }
      return $statuses;
    }
    // Call might return FALSE , e.g. on failed authentication
    else {
      // As call allready throws an exception, we can return an empty array to
      // break no code.
      return array();
    }
  }

  /********************************************//**
   * Timelines
   ***********************************************/
  /**
   * Returns the 20 most recent mentions (tweets containing a users's @screen_name).
   *
   * @param array $params
   *   an array of parameters.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/statuses/mentions_timeline
   */
  public function mentions_timeline($params = array()) {
    return $this->get_statuses('statuses/mentions_timeline', $params, TRUE);
  }

  /**
   * Fetch a user's timeline
   *
   * Returns a collection of the most recent Tweets posted by the user indicated
   * by the screen_name or user_id parameters.
   *
   * @param mixed $id
   *   either a Twitter user_id or a Twitter screen_name.
   *
   * @param array $params
   *   an array of parameters.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline
   */
  public function user_timeline($id, $params = array()) {
    if (is_numeric($id)) {
      $params['user_id'] = $id;
    }
    else {
      $params['screen_name'] = $id;
    }
    return $this->get_statuses('statuses/user_timeline', $params);
  }

  /**
   * Returns a collection of the most recent Tweets and retweets posted by
   * the authenticating user and the users they follow.
   *
   * @param array $params
   *   an array of parameters.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/statuses/home_timeline
   */
  public function home_timeline($params = array()) {
    return $this->get_statuses('statuses/home_timeline', $params);
  }

  /**
   * Returns the most recent tweets authored by the authenticating user
   * that have recently been retweeted by others.
   *
   * @param array $params
   *   an array of parameters.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/statuses/retweets_of_me
   */
  public function retweets_of_me($params = array()) {
    return $this->get_statuses('statuses/retweets_of_me', $params);
  }

  /********************************************//**
   * Tweets
   ***********************************************/
  /**
   * Returns up to 100 of the first retweets of a given tweet.
   *
   * @param int $id
   *   The numerical ID of the desired status.
   * @param array $params
   *   an array of parameters.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/statuses/retweets
   */
  public function statuses_retweets($id, $params = array()) {
    return $this->get_statuses('statuses/retweets/' . $id, $params);
  }

  /**
   * Destroys the status specified by the required ID parameter.
   *
   * @param array $params
   *   an array of parameters.
   *
   * @return
   *   TwitterStatus object if successful or FALSE.
   * @see https://dev.twitter.com/docs/api/1.1/get/statuses/destroy
   */
  public function statuses_destroy($id, $params = array()) {
    $values = $this->call('statuses/update', $params, 'POST', TRUE);
    if ($values) {
      return new TwitterStatus($values);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Updates the authenticating user's current status, also known as tweeting.
   *
   * @param string $status
   *   The text of the status update (the tweet).
   * @param array $params
   *   an array of parameters.
   *
   * @see https://dev.twitter.com/docs/api/1.1/post/statuses/update
   */
  public function statuses_update($status, $params = array()) {
    $params['status'] = $status;
    $values = $this->call('statuses/update', $params, 'POST', TRUE);
    return new TwitterStatus($values);
  }

  /**
   * Retweets a tweet. Returns the original tweet with retweet details embedded.
   *
   * @param int $id
   *   The numerical ID of the desired status.
   * @param array $params
   *   an array of parameters.
   *
   * @see https://dev.twitter.com/docs/api/1.1/post/statuses/retweet/%3Aid
   */
  public function statuses_retweet($id, $params = array()) {
    $values = $this->call('statuses/retweet/' . $id, $params, 'POST', TRUE);
    return new TwitterStatus($values);
  }

  /**
   * Creates a Tweet with a picture attached.
   *
   * @param string $status
   *   The text of the status update (the tweet).
   * @param array $media
   *   An array of physical paths of images.
   * @param array $params
   *   an array of parameters.
   *
   * @see https://dev.twitter.com/docs/api/1.1/post/statuses/update_with_media
   */
  public function statuses_update_with_media($status, $media, $params = array()) {
    $params['status'] = $status;
    $params['media[]'] = '@{' . implode(',', $media) . '}';
    $values = $this->call('statuses/statuses/update_with_media', $params, 'POST', TRUE);
    // @TODO support media at TwitterStatus class.
    return new TwitterStatus($values);
  }

  /**
   * Returns information allowing the creation of an embedded representation of
   * a Tweet on third party sites.
   *
   * @param mixed $id
   *   The Tweet/status ID or the URL of the Tweet/status to be embedded.
   * @param array $params
   *   an array of parameters.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/statuses/oembed
   */
  public function statuses_oembed($id, $params = array()) {
    if (is_numeric($id)) {
      $params['id'] = $id;
    }
    else {
      $params['url'] = $id;
    }
    return $this->get_statuses('statuses/oembed', $params);
  }

  /********************************************//**
   * Search
   ***********************************************/
  /**
   * Returns a collection of relevant Tweets matching a specified query.
   *
   * @param string $query
   *   A UTF-8, URL-encoded search query of 1,000 characters maximum,
   *   including operators.
   * @param array $params
   *   an array of parameters.
   * @return
   *   array of Twitter statuses.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/search/tweets
   */
  public function search_tweets($query, $params = array()) {
    $params['q'] = $query;
    return $this->get_statuses('statuses/oembed', $params);
  }

  /********************************************//**
   * Streaming
   ***********************************************/
  /**
   * Returns public statuses that match one or more filter predicates.
   *
   * At least one predicate parameter (follow, locations, or track) must be specified.
   *
   * @param string $follow
   *   A comma separated list of user IDs.
   * @param string $track
   *   Keywords to track.
   * @param string $locations
   *   Specifies a set of bounding boxes to track.
   * @param array $params
   *   an array of parameters.
   * @return
   *   array of Twitter statuses.
   *
   * @see https://dev.twitter.com/docs/api/1.1/post/statuses/filter
   */
  public function statuses_filter($follow = '', $track = '', $locations = '', $params = array()) {
    if (!empty($follow)) {
      $params['follow'] = $follow;
    }
    if (!empty($track)) {
      $params['track'] = $track;
    }
    if (!empty($locations)) {
      $params['locations'] = $locations;
    }
    return $this->call('statuses/filter', $params, 'POST', TRUE);
  }

  /**
   * Returns a small random sample of all public statuses.
   *
   * @param array $params
   *   an array of parameters.
   * @return
   *   array of Twitter statuses.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/statuses/sample
   */
  public function statuses_sample($params = array()) {
    return $this->get_statuses('statuses/sample', $params);
  }

  /**
   * Returns all public statuses. Few applications require this level of access.
   *
   * @param array $params
   *   an array of parameters.
   * @return
   *   array of Twitter statuses.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/statuses/firehose
   */
  public function statuses_sample($params = array()) {
    return $this->get_statuses('statuses/firehose', $params);
  }

  /**
   * Streams messages for a single user.
   *
   * @param array $params
   *   an array of parameters.
   * @return
   *   array of Twitter statuses.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/user
   */
  public function user($params = array()) {
    return $this->get_statuses('user', $params);
  }

  /**
   * Streams messages for a set of users.
   *
   * @param string $follow
   *   A comma separated list of user IDs
   * @param array $params
   *   an array of parameters.
   * @return
   *   array of Twitter statuses.
   *
   * @see https://dev.twitter.com/docs/api/1.1/get/site
   */
  public function site($follow, $params = array()) {
    $params['follow'] = $follow;
    return $this->get_statuses('site', $params);
  }

  /********************************************//**
   * Direct Messages
   ***********************************************/
  /**
   * Returns the 20 most recent direct messages sent to the authenticating user.
   *
   * This method requires an access token with RWD (read, write & direct message)
   * permissions
   *
   * @param array $params
   *   an array of parameters.
   * @return
   *   array of Twitter statuses.
   * @see https://dev.twitter.com/docs/api/1.1/get/direct_messages
   */
  public function direct_messages($params = array()) {
    return $this->get_statuses('direct_messages', $params);
  }

  /**
   * Returns the 20 most recent direct messages sent by the authenticating user.
   *
   * This method requires an access token with RWD (read, write & direct message)
   * permissions
   *
   * @param array $params
   *   An array of parameters.
   * @return
   *   Array of Twitter statuses.
   * @see https://dev.twitter.com/docs/api/1.1/get/direct_messages/sent
   */
  public function direct_messages_sent($params = array()) {
    return $this->get_statuses('direct_messages/sent', $params);
  }

  /**
   * Returns a single direct message, specified by an id parameter.
   *
   * This method requires an access token with RWD (read, write & direct message)
   * permissions
   *
   * @param int $id
   *   The ID of the direct message.
   * @return
   *   array of Twitter statuses.
   * @see https://dev.twitter.com/docs/api/1.1/get/direct_messages/show
   */
  public function direct_messages_show($id) {
    $params = array('id' => $id);
    return $this->get_statuses('direct_messages/show', $params);
  }

  /**
   * Destroys the direct message specified in the required ID parameter.
   *
   * This method requires an access token with RWD (read, write & direct message)
   * permissions
   *
   * @param int $id
   *   The ID of the direct message.
   * @param array $params
   *   An array of parameters.
   * @return
   *   The deleted direct message
   * @see https://dev.twitter.com/docs/api/1.1/post/direct_messages/destroy
   */
  public function direct_messages_destroy($id, $params = array()) {
    $params['id'] = $id;
    return $this->get_statuses('direct_messages/destroy', $params);
  }

  /**
   * Sends a new direct message to the specified user from the authenticating user.
   *
   * One of user_id or screen_name are required.
   *
   * @param mixed $id
   *   The user ID or the screen name.
   * @param string $text
   *   The URL encoded text of the message.
   * @return
   *   array of Twitter statuses.
   *
   * @see https://dev.twitter.com/docs/api/1.1/post/direct_messages/new
   */
  public function direct_messages_new($id, $params = array()) {
    if (is_numeric($id)) {
      $params['user_id'] = $id;
    }
    else {
      $params['screen_name'] = $id;
    }
    return $this->call('direct_messages/new', $params, 'POST', TRUE);
  }

  // The below methods and classes have not been reviewed yet for V 1.1

  /**
   *
   * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-users%C2%A0show
   */
  public function users_show($id, $use_auth = TRUE) {
    $params = array();
    if (is_numeric($id)) {
      $params['user_id'] = $id;
    }
    else {
      $params['screen_name'] = $id;
    }

    $values = $this->call('users/show', $params, 'GET', $use_auth);
    return new TwitterUser($values);
  }

  /**
   *
   * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-account%C2%A0verify_credentials
   */
  public function verify_credentials() {
    $values = $this->call('account/verify_credentials', array(), 'GET', TRUE);
    if (!$values) {
      return FALSE;
    }
    return new TwitterUser($values);
  }


  /**
   * Method for calling any twitter api resource
   */
  public function call($path, $params = array(), $method = 'GET', $use_auth = FALSE) {
    $url = $this->create_url($path);

    try {
      if ($use_auth) {
        $response = $this->auth_request($url, $params, $method);
      }
      else {
        $response = $this->request($url, $params, $method);
      }
    }
    catch (TwitterException $e) {
      watchdog('twitter', '!message', array('!message' => $e->__toString()), WATCHDOG_ERROR);
      return FALSE;
    }

    if (!$response) {
      return FALSE;
    }

    return $this->parse_response($response);
  }

  /**
   * Perform an authentication required request.
   */
  protected function auth_request($path, $params = array(), $method = 'GET') {
    if (empty($this->username) || empty($this->password)) {
      return false;
    }

    return $this->request($path, $params, $method, TRUE);
  }

  /**
   * Perform a request
   *
   * @throws TwitterException
   */
  protected function request($url, $params = array(), $method = 'GET', $use_auth = FALSE) {
    $data = '';
    if (count($params) > 0) {
      if ($method == 'GET') {
        $url .= '?'. http_build_query($params, '', '&');
      }
      else {
        $data = http_build_query($params, '', '&');
      }
    }

    $headers = array();

    if ($use_auth) {
      $headers['Authorization'] = 'Basic '. base64_encode($this->username .':'. $this->password);
      $headers['Content-type'] = 'application/x-www-form-urlencoded';
    }

    $response = drupal_http_request($url, array('headers' => $headers, 'method' => $method, 'data' => $data));
    if (!isset($response->error)) {
      return $response->data;
    }
    else {
      $error = $response->error;
      $data = $this->parse_response($response->data);
      if (isset($data['error'])) {
        $error = $data['error'];
      }
      throw new TwitterException($error);
    }
  }

  protected function parse_response($response) {
    // http://drupal.org/node/985544 - json_decode large integer issue
    $length = strlen(PHP_INT_MAX);
    $response = preg_replace('/"(id|in_reply_to_status_id)":(\d{' . $length . ',})/', '"\1":"\2"', $response);
    return json_decode($response, TRUE);
  }

  protected function create_url($path) {
    $url =  variable_get('twitter_api', TWITTER_API) .'/1.1/'. $path . '.json';
    return $url;
  }
}

/**
 * A class to provide OAuth enabled access to the twitter API
 */
class TwitterOAuth extends Twitter {

  protected $signature_method;

  protected $consumer;

  protected $token;

  public function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->signature_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    }
  }

  public function get_request_token() {
    $url = $this->create_url('oauth/request_token', '');
    try {
      $response = $this->auth_request($url);
    }
    catch (TwitterException $e) {
    }
    parse_str($response, $token);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  public function get_authorize_url($token) {
    $url = $this->create_url('oauth/authorize', '');
    $url.= '?oauth_token=' . $token['oauth_token'];

    return $url;
  }

  public function get_authenticate_url($token) {
    $url = $this->create_url('oauth/authenticate', '');
    $url.= '?oauth_token=' . $token['oauth_token'];

    return $url;
  }

  public function get_access_token() {
    $url = $this->create_url('oauth/access_token', '');
    try {
      $response = $this->auth_request($url);
    }
    catch (TwitterException $e) {
    }
    parse_str($response, $token);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  public function auth_request($url, $params = array(), $method = 'GET') {
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $params);
    $request->sign_request($this->signature_method, $this->consumer, $this->token);
    switch ($method) {
      case 'GET':
        return $this->request($request->to_url());
      case 'POST':
        return $this->request($request->get_normalized_http_url(), $request->get_parameters(), 'POST');
    }
  }

}

/**
 * Class for containing an individual twitter status.
 */
class TwitterStatus {
  /**
   * @var created_at
   */
  public $created_at;

  public $id;

  public $text;

  public $source;

  public $truncated;

  public $favorited;

  public $in_reply_to_status_id;

  public $in_reply_to_user_id;

  public $in_reply_to_screen_name;

  public $user;

  /**
   * Constructor for TwitterStatus
   */
  public function __construct($values = array()) {
    $this->created_at = $values['created_at'];
    $this->id = $values['id'];
    $this->text = $values['text'];
    $this->source = $values['source'];
    $this->truncated = $values['truncated'];
    $this->favorited = $values['favorited'];
    $this->in_reply_to_status_id = $values['in_reply_to_status_id'];
    $this->in_reply_to_user_id = $values['in_reply_to_user_id'];
    $this->in_reply_to_screen_name = $values['in_reply_to_screen_name'];
    if (isset($values['user'])) {
      $this->user = new TwitterUser($values['user']);
    }
  }
}

class TwitterUser {

  public $id;

  public $screen_name;

  public $name;

  public $location;

  public $description;

  public $followers_count;

  public $friends_count;

  public $statuses_count;

  public $favourites_count;

  public $url;

  public $protected;

  public $profile_image_url;

  public $profile_background_color;

  public $profile_text_color;

  public $profile_link_color;

  public $profile_sidebar_fill_color;

  public $profile_sidebar_border_color;

  public $profile_background_image_url;

  public $profile_background_tile;

  public $verified;

  public $created_at;

  public $created_time;

  public $utc_offset;

  public $status;

  protected $password;

  protected $oauth_token;

  protected $oauth_token_secret;

  public function __construct($values = array()) {
    $this->id = $values['id'];
    $this->screen_name = $values['screen_name'];
    $this->name = $values['name'];
    $this->location = $values['location'];
    $this->description = $values['description'];
    $this->url = $values['url'];
    $this->followers_count = $values['followers_count'];
    $this->friends_count = $values['friends_count'];
    $this->statuses_count = $values['statuses_count'];
    $this->favourites_count = $values['favourites_count'];
    $this->protected = $values['protected'];
    $this->profile_image_url = $values['profile_image_url'];
    $this->profile_background_color = $values['profile_background_color'];
    $this->profile_text_color = $values['profile_text_color'];
    $this->profile_link_color = $values['profile_link_color'];
    $this->profile_sidebar_fill_color = $values['profile_sidebar_fill_color'];
    $this->profile_sidebar_border_color = $values['profile_sidebar_border_color'];
    $this->profile_background_image_url = $values['profile_background_image_url'];
    $this->profile_background_tile = $values['profile_background_tile'];
    $this->verified = $values['verified'];
    $this->created_at = $values['created_at'];
    if ($values['created_at'] && $created_time = strtotime($values['created_at'])) {
      $this->created_time = $created_time;
    }
    $this->utc_offset = $values['utc_offset']?$values['utc_offset']:0;

    if (isset($values['status'])) {
      $this->status = new TwitterStatus($values['status']);
    }
  }

  public function get_auth() {
    return array('password' => $this->password, 'oauth_token' => $this->oauth_token, 'oauth_token_secret' => $this->oauth_token_secret);
  }

  public function set_auth($values) {
    $this->oauth_token = isset($values['oauth_token'])?$values['oauth_token']:NULL;
    $this->oauth_token_secret = isset($values['oauth_token_secret'])?$values['oauth_token_secret']:NULL;
  }
}
