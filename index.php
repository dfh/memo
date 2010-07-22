<?php

#
# super-simple online note-book
#

# TODO add button/link to log out
# TODO fix css style
# TODO fixa inloggningsfel -> render_login_page med felmeddelande

option( 'debug', true );
option( 'root', dirname( __FILE__ ) . '/' );
option( 'passwd_file', option('root') . 'users.ini' );
option( 'data_dir', option('root') . 'data/' );

# -- init START

is_readable( option('passwd_file') )
	or touch( option('passwd_file') )
		or error( 500, 'Password file not readable.' );

( is_dir( option('data_dir') )
	and is_writable( option('data_dir') )
	and is_readable( option('data_dir') ) )
		or mkdir( option('data_dir') )
			or error( 500, 'Data dir not read/writable.' );

session_start();

# -- init END

# -- auth START

# users stored in ini-format file <username> = <hash>
( $users = parse_ini_file( option('passwd_file' ) ) ) !== false
	or error( 500, 'Password file read error' );

# possible login attempt?
if( is_post() && isset( $_GET['login'] ) ) {
	isset( $_POST['id'] ) and isset( $_POST['hash'] )
		or error( 400, 'Need id and password' );

	$hash = sha1( $_POST['password'] );

	# authenticate user ..
	if( isset( $users[$_POST['id']] ) ) {
		if( !$users[$_POST['id']] == $hash ) {
			message('Ogiltigt id/lösenord');
			render_login_page();
		}
	# .. or create new user
	} else {
		file_put_contents( option('passwd_file'), sprintf( "%s = %s\n", $_POST['id'], $hash ), FILE_APPEND ) 
			or error( 500, 'Password file write fail' );

		title('Konto skapat');
		message('Konto skapat - kom ihåg ditt id och lösenord!');
	}

	$_SESSION['user'] = array( 'id' => $_POST['id'], 'hash' => $hash );

# logout?
} elseif( is_post() && !empty( $_POST['logout'] ) ) {
	$_SESSION = array();
	session_destroy();
	message('Du är nu utloggad');
	render_login_page();
}	

# does user exist? should do by now if was POST request!
@( $user = $users[$_SESSION['user']['id']] )
	or render_login_page();

# -- auth END

$file = option('data_dir') . $user['id'] . '.txt';

file_exists( $file )
	or touch( $file )
		or error( 500, 'Unable to read data file.' );

# -- save START

# save?
if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	isset( $_POST['data'] )	or error( 400, 'Need data' );
	@file_put_contents( $file, $_POST['data'] )	or error( 500, 'Error saving data' );
	message('Anteckningar sparade');
	title('Anteckningar sparade');
}

# -- save END

# -- read START

( $data = @file_get_contents( $file ) ) !== false
	or error( 500, 'Error reading data' );

render_read();

# -- read END

#
# functions
#

function message( $message = '' )
{
	return call_user_func_array( 'kv', array_merge( array( 'message' ), func_get_args() ) );
}

function title( $title = '' )
{
	return call_user_func_array( 'kv', array_merge( array( 'title' ), func_get_args() ) );
}

function css( $css = '' )
{
	return call_user_func_array( 'kv', array_merge( array( 'css' ), func_get_args() ) );
}

#
# renderers
#

function render_login_page( $error = '' )
{
	http_send_status( 401 );
	title() or title('Logga in');
	_render_content(
$error . '
<h1>Login</h1>

<form method="post" action="?login">
	<label>ID</label>
	<input type="text" name="id" />

	<label>Password</label>
	<input type="password" name="password" />

	<input type="submit" value="Login" />
</form>', array( 'title' => 'Login' ) );
	die();
}

function render_read( $data )
{
	title() or title('Anteckningar');
	_render_content( '
<form action="" method="POST">
<textarea name="data">' .
$data . '
</textarea>
</form>' );
}

function _render_content( $c )
{
	$title = title();
	$m = message();
	$css = css() . '
body {
	
}
textarea {
	width: 800px;
	height: 600px;
}
';

	echo "
<!DOCTYPE HTML>
<html>
<head>
	<title>$title</title>
</head>
<style>
$css
</style>
<body>
$m
$c
</body>
</html>";
}

function error( $http_status_code, $msg = '' )
{
	http_send_status( $http_status_code );
	title() or title('Error');
	option('debug') and $msg .= sdump( $_SERVER );
	_render_content("<h1>Error</h1>\n\n" . $msg);
	die();
}

#
# helper functions
#

function is_post()
{
	return $_SERVER['REQUEST_METHOD'] == 'POST';
}

function option( $name, $value = '' )
{
	return call_user_func_array( 'kv', array_merge( array( 'option' ), func_get_args() ) );
}

function kv( $ns, $k = 0, $v = null )
{
	static $map;
	if( func_num_args() == 3 ) {
		is_array( $map[$ns] )	or $map[$ns] = array();
		$map[$ns][$k] = $v;
	}	
	return isset( $map[$ns][$k] ) ? $map[$ns][$k] : null;
}

function sdump( $v )
{
	ob_start();
	var_dump( $v );
	$s = ob_get_contents();
	ob_end_clean();
	return $s;
}
