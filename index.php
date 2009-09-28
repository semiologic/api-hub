<?php
define('path', dirname(__FILE__));

if ( function_exists('date_default_timezone_set') )
	date_default_timezone_set('UTC');

include path . '/config.php';
include path . '/inc/utils.php';
include path . '/inc/db.php';

# parse request
$request = str_replace(base_uri, '', $_SERVER['REQUEST_URI']);

if ( !$request ) {
	status_header(400);
	die;
}

$request = preg_replace("/\?.*/", '', $request);
$request = rtrim($request, '/');
$request = explode('/', $request);

$vars = array('api_key', 'site_url', 'site_ip', 'php_version', 'mysql_version');

switch ( sizeof($request) ) {
case 1:
	$api_key = array_pop($request);
	
	if ( preg_match("/^[0-9a-f]{32}$/i", $api_key) )
		break;
	
default:
	status_header(400);
	die;
}

$_REQUEST = array_merge($_GET, $_POST);

foreach ( $vars as $var ) {
	if ( !isset($$var) )
		$$var = isset($_REQUEST[$var]) ? $_REQUEST[$var] : '';
}

$site_ip = $_SERVER['REMOTE_ADDR'];

if ( isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/^WordPress\/(.*); (.*)$/", $_SERVER['HTTP_USER_AGENT'], $match) ) {
	$wp_version = $match[1];
	$site_url = trim($match[2]);
} else {
	$wp_version = '';
	$site_url = '';
}

$site_ip = filter_var($site_ip, FILTER_VALIDATE_IP);
$site_url = filter_var($site_url, FILTER_VALIDATE_URL);

foreach ( array('wp_version', 'php_version', 'mysql_version') as $var ) {
	if ( !preg_match("/^\d*\.\d+(?:\.\d+)(?: [a-z0-9]+)?$/i", $$var) ) {
		$$var = '';
	}
}


header('Content-Type: text/plain; Charset: UTF-8');

db::connect('pgsql');

$dbs = db::query("
	SELECT	users.user_name,
			users.user_email,
			profile_name,
			profile_key,
			membership_expires
	FROM	memberships
	JOIN	users
	ON		users.user_id = memberships.user_id
	WHERE	user_key = :user_key
	", array('user_key' => $api_key));

$details = array();

while ( $row = $dbs->get_row() ) {
	$user_name = $row->user_name;
	$user_email = $row->user_email;
	$profile_name = $row->profile_name;
	$profile_key = str_replace('_', '-', $row->profile_key);
	$profile_expires = $row->membership_expires;

	if ( !$profile_expires ) {
		$profile_expires = false;
	} else {
		$profile_expires = date('Y-m-d', strtotime($profile_expires));
	}
	
	$details[$profile_key] = array(
		'user_name' => $user_name,
		'user_email' => $user_email,
		'profile_name' => $profile_name,
		'profile_expires' => $profile_expires,
		);
}

db::disconnect();

if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	echo serialize($details);
} else {
	foreach ( $details as $profile_key => $detail ) {
		echo 'user_name:' . $detail['user_name'] . "\n";
		echo 'user_email:' . $detail['user_email'] . "\n";
		echo $profile_key . ':' . $detail['profile_expires'] . "\n";
	}
}

die;
?>