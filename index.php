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

switch ( sizeof($request) ) {
case 1:
	$api_key = array_pop($request);
	
	if ( preg_match("/^[0-9a-f]{32}$/i", $api_key) )
		break;
	
default:
	status_header(400);
	die;
}

$credit = 0;

header('Content-Type: text/plain; Charset: UTF-8');

db::connect('pgsql');

$dbs = db::query("
	SELECT	profile_name,
			profile_key,
			membership_expires
	FROM	memberships
	JOIN	users
	ON		users.user_id = memberships.user_id
	WHERE	user_key = check_banned(:user_key)
	", array('user_key' => $api_key));

$memberships = array();

while ( $row = $dbs->get_row() ) {
	$name = $row->profile_name;
	$key = str_replace('_', '-', $row->profile_key);
	$expires = $row->membership_expires;

	if ( !$expires ) {
		$expires = false;
	} else {
		$expires = date('Y-m-d', strtotime($expires));
	}
	
	$memberships[$key] = array(
		'name' => $name,
		'expires' => $expires,
		);
	
	if ( $key == 'sem-pro' && $expires && time() <= strtotime($expires) ) {
		$credits = 25;
	}
}

db::disconnect();

foreach ( $memberships as $key => $membership ) {
	echo $key . ':' . $membership['expires'] . "\n";
}

if ( $credits ) {
	echo "hub:" . intval($credits) . "\n";
}

die;
?>