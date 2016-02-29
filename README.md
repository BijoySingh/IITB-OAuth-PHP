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

* Send an email to a user with an access token
```
send_mail($access_token, $subject, $message, $reply_to=array())
```

# Complete Use Case
## Basic Setup
- Create your Application from http://gymkhana.iitb.ac.in/sso/
- Decide what permission scopes you need like ```$permissions = 'basic profile ldap';```
- Decide which one of these are necessary for your applications ```$required_scopes=array('basic');```
- Also decide what variables you need for sure like ```$required_fields = array('username', 'email', 'roll_number');```
- Also decide your redirect url, we will call this is ```$REDIRECT_URI = 'YOUR_DOMAIN/sso.php';```
- Decide the state you want to get with the login, like ```$state = 'user_login';```
- The response type is mostly always code so ```$response_type = 'code';```

## Setting up the SSO Handler  
Get the client ID and the client secret from the console from the http://gymkhana.iitb.ac.in/sso/ section of your app
```
require_once 'sso_handler.php';

$sso_handler = new SSOHandler($CLIENT_ID, $CLIENT_SECRET);
```

## Making a request for login
Lets see how to make a login request. We will generate the login link as follows
``` 
$url = $sso_handler->gen_auth_url($response_type, $state, $permissions);
echo '<a href="'.$url.'">Sign In</a>';
```
This allows for you to generate the login button in the UI you like. 
Another way to setup a login link is using the standard button. (See the SSO reference). You might still use the ```gen_auth_url``` function.

## Handling a response
Now that you set a redirect link in your application details in the SSO console to ```YOUR_DOMAIN/sso.php```, when the login process is completed the details will be redirected to this link. Lets see what you can do here

```
require_once 'sso_handler.php';

$sso_handler = new SSOHandler(CLIENT_ID, CLIENT_SECRET);
if ($sso_handler->validate_code($_GET) && $sso_handler->validate_state($_GET)) {
  $response = $sso_handler->default_execution($_GET, $REDIRECT_URI, $required_scopes, $required_fields);
}
```

If you dont have a necessary state, the validate_state can be skipped, if you do, you can use it ass follows
```$sso_handler->validate_state($_GET, true, $options)```

So, thanks to the default execution function, all the details happen without you needing to do anything. Now, lets see what you get when the execution finishes.

If an error occurs this will happen
```
{'error' => 'error_message'} 
```

If no error occurs, the response will contain these 2 arrays.
```
{
  'access_information' => array returned on getting access token,
  'user_information' => array returned on getting the user information
}
```
You will mostly need will be in the array ```$response['user_information']```.
