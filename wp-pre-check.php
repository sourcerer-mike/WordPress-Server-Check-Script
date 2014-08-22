<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Check if your WordPress is able to run on this server.
 *
 * Alls tests are made as of 2014-07-29.
 */

define( 'RESULT_ERROR', 'error' );
define( 'RESULT_WARNING', 'warning' );
// acceptable
define( 'RESULT_OK', 'ok' );
define( 'RESULT_PERFECT', 'perfect' );

function return_bytes( $val ) {
	$val  = trim( $val );
	$last = strtolower( $val[ strlen( $val ) - 1 ] );
	switch ( $last ) {
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}

	return $val;
}

class Result {
	const ERROR   = 'ERROR';
	const WARNING = 'warning';
	const OK      = 'ok';
	const PERFECT = 'yeah';

	public $message = '';

	public $result = 'warning';

	public function __construct( $message, $result ) {
		$this->message = $message;
		$this->result  = $result;
	}
}

// PHP Version
if ( version_compare( PHP_VERSION, '5.5.0', '>=' ) ) {
	$result = new Result( PHP_VERSION, Result::PERFECT );
} elseif ( version_compare( PHP_VERSION, '5.3.0', '>' ) ) {
	$result = new Result( PHP_VERSION . ' is still supported.', Result::OK );
} elseif ( version_compare( PHP_VERSION, '5.3', '=' ) ) {
	$result = new Result( PHP_VERSION . ' is at the end of life but works.', Result::WARNING );
} elseif ( version_compare( PHP_VERSION, '5.2.0', '>=' ) ) {
	$result = new Result( PHP_VERSION . ' works but is not supported by PHP.', Result::WARNING );
} elseif ( version_compare( PHP_VERSION, '5.2.0', '<' ) ) {
	$result = new Result( PHP_VERSION . ' is too old!', Result::ERROR );
} else {
	$result = new Result( 'Could not determine PHP-Version!', Result::ERROR );
}
$messages['PHP Version'] = $result;

// mod_rewrite
if ( ! function_exists( 'apache_get_modules' ) ) {
	$result = new Result( 'Could not ask apache for some informations!', Result::ERROR );
} else {
	$apache_modules = apache_get_modules();
	if ( array_search( 'mod_rewrite', $apache_modules ) !== false ) {
		$result = new Result( 'mod_rewrite is enabled', Result::OK );
	} else {
		$result = new Result( 'Your Apache has no mod_rewrite!', Result::ERROR );
	}
}
$messages['Rewrite rules'] = $result;

// GDLib
if ( function_exists( 'gd_info' ) ) {
	$result = new Result( 'GDLib is enabled', Result::OK );
} else {
	$result = new Result( 'GDLib is not available', Result::ERROR );
}
$messages['Handling images'] = $result;

// MySQL
if ( function_exists( 'mysql_connect' ) ) {
	$result = new Result( 'MySQL is enabled', Result::OK );
} else {
	$result = new Result( 'MySQL extension is not available', Result::ERROR );
}
$messages['Database usage'] = $result;

// Memory
$memory_limit       = ini_get( 'memory_limit' );
$memory_limit_bytes = return_bytes( $memory_limit );
if ( $memory_limit_bytes >= 256 * 1024 * 1024 ) {
	$result = new Result( $memory_limit, Result::ERROR );
} elseif ( $memory_limit_bytes >= 128 * 1024 * 1024 ) {
	$result = new Result( $memory_limit, Result::OK );
} elseif ( $memory_limit_bytes >= 64 * 1024 * 1024 ) {
	$result = new Result( $memory_limit . ' might be enough but should be more', Result::WARNING );
} elseif ( $memory_limit_bytes < 64 * 1024 * 1024 ) {
	$result = new Result( $memory_limit . ' is a joke', Result::ERROR );
} else {
	$result = new Result( 'could not determine the memory_limit', Result::ERROR );
}
$messages['Memory limit'] = $result;

// file uploads
if ( ini_get( 'file_uploads' ) ) {
	$result = new Result( 'uploads are allowed', Result::OK );
} else {
	$result = new Result( 'file_uploads not allowed', Result::ERROR );
}
$messages['File uploads'] = $result;

// safe_mode
if ( ini_get( 'safe_mode' ) ) {
	$result = new Result( 'you are safe - a bit', Result::OK );
} else {
	$result = new Result( 'safe_mode is not enabled', Result::ERROR );
}
$messages['Safe-Mode'] = $result;

// short open tag
if ( ini_get( 'short_open_tag' ) ) {
	$result = new Result( 'you are allowed to mess it up', Result::OK );
} else {
	$result = new Result( 'do not use short_open_tags anywhere', Result::WARNING );
}
$messages['Lazy coding'] = $result;

// display errors
if ( ! ini_get( 'display_errors' ) ) {
	$result = new Result( 'does not show problems', Result::OK );
} else {
	$result = new Result( 'display_errors is enabled - hopefully this is a developer server', Result::WARNING );
}
$messages['Does not display errors'] = $result;

// zip
$zip = shell_exec( 'zip -v' );
if ( $zip ) {
	$result = new Result( 'your shell has zip', Result::OK );
} else {
	$result = new Result( 'no zip - no plugins, themes or updates', Result::WARNING );
}
$messages['Packing ZIP-Archives'] = $result;

// unzip
$zip = shell_exec( 'unzip -v' );
if ( $zip ) {
	$result = new Result( 'your shell has unzip', Result::OK );
} else {
	$result = new Result( 'no unzip - no plugins, themes or updates', Result::WARNING );
}
$messages['Unpacking ZIP-Archives'] = $result;

$userInfo = posix_getpwuid( posix_geteuid() );

// running user
$uid = $userInfo['uid'];
if (fileowner(__FILE__) != $uid) {
	$result = new Result(
		sprintf(
			'You uploaded this file as a different user (%s) than Apache runs with (%s).',
			fileowner(__FILE__),
			$uid
		),
		Result::WARNING
	);
} else {
	$result = new Result( 'File-owner and Apache User match.', Result::OK );
}
$messages['Apache User'] = $result;


// running group
$gid = $userInfo['gid'];
if (filegroup(__FILE__) != $gid) {
	$result = new Result(
		sprintf(
			'You uploaded this file as a different user-group (%s) than Apache runs with (%s).',
			filegroup(__FILE__),
			$gid
		),
		Result::WARNING
	);
} else {
	$result = new Result( 'File-owner and Apache Group match.', Result::OK );
}
$messages['Apache User-Group'] = $result;


$format = " [%s] %s (%s)\n";

if ( PHP_SAPI != 'cli' ) {
	$format = nl2br( $format );
	echo '<pre>';
}

foreach ( $messages as $title => $result ) {
	printf(
		$format,
		str_pad( $result->result, 7, ' ', STR_PAD_BOTH ),
		$title,
		$result->message
	);
}

if ( PHP_SAPI == 'cli' ) {
	echo "\n";
	echo "Warning! Results are not reliable.\n";
	echo "You tested from shell - do it again using Apache.\n";
	echo "\n";
}
