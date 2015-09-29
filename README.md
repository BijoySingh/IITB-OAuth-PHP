# IITB-OAuth-PHP
The OAuth for IITB on PHP

# Usage

```
require_once 'sso_handler.php';

$sso_handler = new SSOHandler(CLIENT_ID, CLIENT_SECRET);
if ($sso_handler->validate_code($_GET) && $sso_handler->validate_state($_GET)) {
  $response = $sso_handler->default_execution($_GET, $REDIRECT_URI, array('basic', 'program'), array('roll_number'));
}
```

# Common Functions

* Default Execution, most of what you would need to do will be done by this, you wouldn't need to dig into the API
Generates a response or an error. You can optionally provide the scope of the and fields you need
```
/**
 * Does the basic steps and returns either of the responses :
 * {'error' => 'error_message'} or
 * {
 *   'access_information' => array returned on getting access token,
 *   'user_information' => array returned on getting the user information
 * }
 */

default_execution($get, $redirect_uri, $required_scopes=array('basic'), $fields=null)
```


* Generates a URL for the Login with SSO button based on your requirements of state, scope and uri
```
gen_auth_url($response_type='code', $state=null, $scope=null, $redirect_uri=null)
```

* Requests for access information i.e. access_token, refresh_token, etc.
```
request_access_information($code, $redirect_uri)
```

* Requests for user information based on the fields that you send
```
request_user_information($access_token, $fields)
```
