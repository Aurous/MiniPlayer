<?php
require '../src/SpotifyWebAPI.php';
require '../src/Request.php';
require '../src/Session.php';
require '../src/SpotifyWebAPIException.php';
require '../src/SpotifyWebAPIAuthException.php';
session_start();
$session = new SpotifyWebAPI\Session(
		'CLIENT_ID',
		'CLIENT_SECRET',
		htmlspecialchars("https://{$_SERVER['HTTP_HOST']}/callback/", ENT_QUOTES, 'UTF-8')
	);
if(isset($_GET['code'])){
	// Request a access token using the code from Spotify
	$session->requestAccessToken($_GET['code']);
	$_SESSION['expire'] = $session->getTokenExpiration();
	$_SESSION['access'] = $session->getAccessToken();
	$_SESSION['refresh'] = $session->getRefreshToken();
	
	// Store the access and refresh tokens somewhere. In a database for example.

	// Send the user along and fetch some data!
	header('Location: '.htmlspecialchars("https://{$_SERVER['HTTP_HOST']}/", ENT_QUOTES, 'UTF-8'));
}elseif(isset($_GET['refresh']) and !empty($_GET['refresh'])){
	if($session->refreshAccessToken($_GET['refresh'])){
		$_SESSION['expire'] = $session->getTokenExpiration();
		$_SESSION['access'] = $session->getAccessToken();
		$_SESSION['refresh'] = $session->getRefreshToken();
		header('Location: '.htmlspecialchars("https://{$_SERVER['HTTP_HOST']}/", ENT_QUOTES, 'UTF-8'));
	}else{
		$_SESSION = [];
		session_destroy();
		header('Location: '.htmlspecialchars("https://{$_SERVER['HTTP_HOST']}/", ENT_QUOTES, 'UTF-8'));
	}
}else{
	$_SESSION = [];
	session_destroy();
	header('Location: '.htmlspecialchars("https://{$_SERVER['HTTP_HOST']}/", ENT_QUOTES, 'UTF-8'));
}
die();

?>