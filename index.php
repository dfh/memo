<?php

#
# super-simple online note-book
#

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
	!empty( $_POST['id'] ) and !empty( $_POST['password'] )
		or render_login_page( 'Du måste ange användarnamn och lösenord', 400 );

	$hash = $_SESSION['user'] = array( 'id' => $_POST['id'], 'hash' => sha1( $_POST['password'] ) );

# logout?
} elseif( is_post() && isset( $_GET['logout'] ) ) {
	$_SESSION = array();
	session_destroy();
	alert('Du är nu utloggad');
	render_login_page();
}	

# authenticate user ..
isset( $_SESSION['user'] )
	and isset( $_SESSION['user']['id'] )
	and isset( $_SESSION['user']['hash'] )
		or render_login_page();

if( !isset( $users[$_SESSION['user']['id']] ) ) {
	create_user( $_SESSION['user']['id'], $_SESSION['user']['hash'] );
} elseif( $users[$_SESSION['user']['id']] != $_SESSION['user']['hash'] ) {
	alert('Felaktigt lösenord');
	render_login_page();
}
	
user( $_SESSION['user']['id'] );

#
# -- auth END

$file = option('data_dir') . user() . '.txt';

file_exists( $file )
	or touch( $file )
		or error( 500, 'Unable to read data file.' );

# -- save START

# save?
if( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_GET['save'] ) ) {
	isset( $_POST['data'] )	or error( 400, 'Need data' );
	@file_put_contents( $file, $_POST['data'] )	!== false or error( 500, 'Error saving data' );
	updated( $_SERVER['REQUEST_TIME'], true );
	title('Anteckningar');
}

# -- save END

# -- read START

( $data = @file_get_contents( $file ) ) !== false
	or error( 500, 'Error reading data file' );

updated() or updated( filectime( $file ) );

render_read( $data );

# -- read END

#
# functions
#

function create_user( $user_id, $hash )
{
	title('Anteckningar - konto skapat');
	alert('Ett konto har skapats åt dig – kom ihåg ditt användarnamn och lösenord!');
	return @file_put_contents( option('passwd_file'), sprintf( "%s = %s\n", $user_id, $hash ), FILE_APPEND );
}


function updated( $timestamp = null, $alert = false )
{
	$args = array();
	if( func_num_args() == 1 || func_num_args() == 2 ) {
		$tpl = '<div class="ctime%s"><p>Senast sparad %s</p></div>';
		if( $alert )
			$args[] = sprintf( $tpl, ' hl', render_date( $timestamp ) );
		else
			$args[] = sprintf( $tpl, '', render_date( $timestamp ) );
	}

	return call_user_func_array( 'kv', array_merge( array( 'updated', 0 ), $args ) );
}

function alert( $message = '' )
{
	return call_user_func_array( 'kv', array_merge( array( 'message', 0 ), func_get_args() ) );
}

function title( $title = '' )
{
	return call_user_func_array( 'kv', array_merge( array( 'title', 0 ), func_get_args() ) );
}

function css( $css = '' )
{
	return call_user_func_array( 'kv', array_merge( array( 'css', 0 ), func_get_args() ) );
}

function user( $user = '' )
{
	return call_user_func_array( 'kv', array_merge( array( 'user', 0 ), func_get_args() ) );
}

#
# renderers
#

function render_date( $date )
{
	return strftime( '%A %e %B, %Y kl. %H:%M:%S', $date );
}

function render_login_page( $error = '' )
{
	http_send_status( 401 );
	!$error or alert($error);
	title() or title('Logga in');
	_render_content('
<h1>Logga in</h1>

<form method="post" action="?login">
	<div>
		<label>Användarnamn</label>
		<input type="text" name="id" />
	</div>

	<div>
		<label>Lösenord</label>
		<input type="password" name="password" />
	</div>
	
	<div class="submit">
		<input type="submit" value="Logga in" />
	</div>

</form>
	
<p class="help">
	Om du inte har ett konto, fyll bara i önskat användarnamn och lösenord. Om du får ett meddelande om att lösenordet är felaktigt, är användarnamnet upptaget.
</p>' );
	die();
}

function render_read( $data )
{
	title() or title('Anteckningar');
	_render_content( '
		<h1>Dina anteckningar</h1>' . 
updated() . '
<form action="?save" method="POST">
<div class="submit">
<input type="submit" value="Spara" />
</div>
<div>
<textarea name="data">' .
$data . '
</textarea>
</div>
<div class="submit">
<input type="submit" value="Spara" />
</div>' . 
updated() . '
</form>' );
}

function _render_content( $c )
{
	$title = title();
	$u = user();
	$m = alert();
	if( $m ) {
		$m = '<div class="alert"><p>' . $m . '</p></div>';
	}

	$l = $class = '';
	if( user() ) {
		$l = '<form method="post" action="?logout" class="logout"><input type="submit" value="Logga ut" /><p>Inloggad som <strong>' . $u . '</strong></p></form>';
	} else {
		$class = ' class="login"';
	}

	$css = css() . '
body {
	font: 12px/1.7 "Helvetica Neue", Helvetica, Arial, sans-serif;
	width: 80%;
	margin: 0 auto;
	position: relative;
	color: #333;
}
h1 {
	font-size: 14px;
	text-transform: uppercase;
	color: #111;
}
.logout {
	position: absolute;
	right: 0;
	top: 0;
	text-align: right;
}
.logout p {
	margin: 5px 0;
	color: #666;
}
.alert {
	text-align: center;
	margin: 15px 0;
}
.alert p {
	padding: 10px 15px;
	border: 1px solid #ccc;
	background: yellow;
	display: inline-block;
}
.help {
	color: #444;
}

.login {
	padding-top: 50px;
	width: 400px;
}
.login form {
	width: 100%;
	overflow: hidden;
	margin: 30px 0;
}
.login form p {
	clear: both;
	margin: 15px 0;	
}
.login form div {
	float: left;
	margin: 0 10px 15px 0;
}
.login div.submit {
	margin-bottom: 0;
}
.login label {
	display: block;
	margin: 0;
}

textarea {
	width: 100%;
	height: 600px;
}
form div {
	margin: 10px 0;
}
div.submit {
	text-align: center;
}
.ctime {
	text-align: center;
	color: #999;
	margin: 15px 0;
}
.ctime p {
	display: inline;
	padding: 6px 10px;
}
.hl p {
	background: yellow;
}
';

	echo "
<!DOCTYPE HTML>
<html>
<head>
	<meta charset=\"utf-8\" />
	<title>$title</title>
</head>
<style>
$css
</style>
<body$class>
$l
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

function kv( $ns, $k, $v = null )
{
	static $map;
	if( func_num_args() == 3 ) {
		isset( $map[$ns] ) or $map[$ns] = array();
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
