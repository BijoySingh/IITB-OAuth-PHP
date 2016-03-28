<?php
/**
 * DO NOT REMOVE THESE COMMENTS
 * @author Bijoy Singh Kochar
 * @license
 * The MIT License (MIT)
 *
 * Copyright (c) [year] [fullname]
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * This class handles all SSO activity
 * @example
 *     $sso_handler = new SSOHandler(CLIENT_ID, CLIENT_SECRET);
 *     if ($sso_handler->validate_code($_GET) && $sso_handler->validate_state($_GET)) {
 *        $response = $sso_handler->default_execution($_GET, REDIRECT_URI);
 *     }
 *
 */
class SSOHandler
{
  private $client_id = 0;
  private $client_secret = 0;
  private $base_url = 'https://gymkhana.iitb.ac.in/sso';

  public function __construct($client_id, $client_secret, $base_url=null) {
    $this->client_id = $client_id;
    $this->client_secret = $client_secret;
    if ($base_url !== null) {
      $this->base_url = $base_url;
    }
  }

  // generates a url which you can use to set as the link for the button
  public function gen_auth_url($response_type='code', $state=null, $scope=null, $redirect_uri=null) {
    $url = $this->base_url . '/oauth/authorize/' .
      '?client_id=' . $this->client_id .
      '&response_type=' . $response_type;
    if ($state !== null) {
      $url .= '&state=' . $state;
    }
    if ($scope !== null) {
      $url .= '&scope=' . $scope;
    }
    if ($redirect_uri !== null) {
      $url .= '&redirect_uri=' . $redirect_uri;
    }
    return $url;
  }

  /**
   * checks the state of the application
   * @example
   *     $sso_handler->validate_state($_GET, true, array('user_login', 'admin_login'));
   */
  public function validate_state($get, $is_necessary=false, $valid_states=array()) {
    if ($is_necessary === false && !isset($get['state'])) {
      return true;
    } elseif (!isset($get['state'])) {
      return false;
    } else {
      if (array_search($get['state'], $valid_states) !== false) {
        return true;
      }
      return false;
    }
  }

  /**
   * checks that the code paramter is set
   * @example
   *     $sso_handler->validate_code($_GET);
   */
  public function validate_code($get) {
    return isset($get['code']);
  }

  /**
   * get access information response
   */
  public function request_access_information($code, $redirect_uri) {
    $credentials = $this->client_id . ":" . $this->client_secret;

    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_URL, $this->base_url . "/oauth/token/");
    curl_setopt($curl_request, CURLOPT_USERPWD, $credentials);
    curl_setopt($curl_request, CURLOPT_POST, 1);
    curl_setopt($curl_request, CURLOPT_POSTFIELDS, http_build_query(
    array(
      'code' => $code,
      'grant_type' => 'authorization_code',
      'redirect_uri' => $redirect_uri,
    )));
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl_request);
    curl_close ($curl_request);
    return $this->decode_json_response($response);
  }

  /**
   * Check error response
   */
  public function is_error_response($response, $index='error') {
    return isset($response[$index]);
  }

  /**
   * Gets the user information using the access token
   */
  public function request_user_information($access_token, $fields) {
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_URL, $this->base_url . "/user/api/user/".$fields);
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , 'Authorization: Bearer '.$access_token));
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl_request);
    curl_close($curl_request);
    return $this->decode_json_response($response);
  }

  /**
   * Does the basic steps and returns either of the responses :
   * {'error' => 'error_message'} or
   * {
   *   'access_information' => array returned on getting access token,
   *   'user_information' => array returned on getting the user information
   * }
   */
  public function default_execution($get, $redirect_uri, $required_scopes=array('basic'), $fields=null) {
    $access_information_response = $this->request_access_information($get['code'], $redirect_uri);
    if (!$this->is_error_response($access_information_response) && isset($access_information_response['access_token'])) {
      // We have an access token
      $access_token = $access_information_response['access_token'];
      $refresh_token = $access_information_response['refresh_token'];
      $scope = $access_information_response['scope'];
      $provided_scopes = explode(' ', $scope);

      foreach ($required_scopes as $key => $value) {
        if (array_search($value, $provided_scopes) === false) {
          return array('error' => 'Not all permissions provided');
        }
      }

      $url_fields = '';
      if ($fields !== null) {
        $url_fields = '?fields='.implode(',', $fields);
      }

      $user_information_response = $this->request_user_information($access_token, $url_fields);
      if (!$this->is_error_response($user_information_response) && isset($user_information_response['id'])) {
        return array('access_information' => $access_information_response, 'user_information' => $user_information_response);
      } else {
        // The user information access led to some error
        return array('error' => $user_information_response['detail']);
      }
    } else {
      // The access token response was an error
      return array('error' => $access_information_response['error']);
    }
  }

  private function decode_json_response($response) {
    $array = json_decode($response, true);
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            return $array;
        default:
            return array('error' => 'Could Not Parse Json in Response<br/><pre>'. htmlentities($response) .'</pre>');
    }
  }

  /**
   * Sends a mail to the user for an access token
   */
  public function send_mail($access_token, $subject, $message, $reply_to=array()) {
    $curl_request = curl_init();
    curl_setopt($curl_request, CURLOPT_URL, $this->base_url . "/user/api/user/send_mail/");
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , 'Authorization: Bearer '.$access_token));
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);

    $email_data = array('subject' => $subject, 'message' => $message, 'reply_to' => $reply_to);
    curl_setopt($curl_request, CURLOPT_POSTFIELDS, json_encode($email_data));
    $response = curl_exec($curl_request);
    curl_close ($curl_request);
    return $this->decode_json_response($response);
  }
}
?>
