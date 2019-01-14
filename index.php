<?php
require 'src/SpotifyWebAPI.php';
require 'src/Request.php';
require 'src/Session.php';
require 'src/SpotifyWebAPIException.php';
require 'src/SpotifyWebAPIAuthException.php';
session_start();
$api = new SpotifyWebAPI\SpotifyWebAPI();
$session = new SpotifyWebAPI\Session('CLIENT_ID','CLIENT_SECRET',htmlspecialchars("https://{$_SERVER['HTTP_HOST']}/callback/", ENT_QUOTES, 'UTF-8'));
if(!isset($_SESSION['access'])){
	$options = ['scope' => ["streaming","user-read-birthdate","user-read-email","user-read-private",],];
	header("Location:" . $session->getAuthorizeUrl($options));
    die();
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="shortcut icon" type="image/png" href="favicon.png"/>
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/jquery-ui.css">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
	<script src="js/jquery-3.3.1.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/jquery-ui.js"></script>
	<title>MiniPlayer</title>
</head>
<body style="background-color:#333333;">
<br />
	
	<div class="container">
		<div id="details" >
			<h2 style="color:white;">Inside your spotify client, please change your listening device to MiniPlayer</h2>
		</div>
		<div class="card text-center" id="player" style="display: none;">
			<div class="card-body" style="background-color:#333333;">
				<div class="media">
					<img  name="albumArt" id="albumArt" class="align-self-start mr-3 img-fluid" src="" width="120" height="120" />
					<div class="media-body">
						<p name="songTitle" id="songTitle" style="color:white;"></p>
						<p name="artistNames" id="artistNames" style="color:white;"></p>
						<div class="progress" style="height: 3px;">
							<div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" id="progress"></div>
						</div>
						
						<div class="row">
							<div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-6">
								<span style="font-size: 2rem;">
									<i class="fas fa-step-backward" id="previous" style="color:white;"></i>
									<i class="fas fa-step-forward" id="next" style="color:white;"></i>
									<i class="fas fa-play" id="play" style="color:white;"></i>
									<i class="fas fa-pause" id="pause" style="color:white;"></i>
								</span>
							</div>
							<div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-6">
								<div  class="d-none d-md-block d-lg-block d-xl-block">
								<br />
								</div>
								<div id="slider-range-max" style="height: 6px;"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script src="https://sdk.scdn.co/spotify-player.js"></script>
		<script>
			window.onSpotifyWebPlaybackSDKReady = () => {
				var id;
				const token = '<?php echo $_SESSION['access']; ?>';
				const player = new Spotify.Player({
					name: 'MiniPlayer',
					getOAuthToken: cb => { cb(token); }
				});
				player.addListener('initialization_error', ({ message }) => { console.error(message); });
				player.addListener('authentication_error', ({ message }) => {
					if(message == "Authentication failed"){
						window.location.replace("<?php echo htmlspecialchars("https://{$_SERVER['HTTP_HOST']}/callback/?refresh={$_SESSION['refresh']}", ENT_QUOTES, 'UTF-8'); ?>");
					}
					console.error(message); 
				});
				player.addListener('account_error', ({ message }) => { console.error(message); });
				player.addListener('playback_error', ({ message }) => { console.error(message); });
				player.addListener('player_state_changed', state => { 
					if(state !== null){ 
						$("#details").hide();
						$("#player").show();
						if(state.paused === true){
							$("#pause").hide();
							$("#play").show();
							clearInterval(id);
							id = false;						
						}else{
							$("#play").hide();
							$("#pause").show();
						}
						$("#albumArt").attr("src", state.track_window.current_track.album.images[0].url);
						$("#songTitle").html(state.track_window.current_track.name);
						var data = [];
						state.track_window.current_track.artists.forEach(function(item, index){
							data.push(item.name);
						});
						$("#artistNames").html(data.join(', '));
						var width = state.position * 100 / state.duration;
						var speed = 100/((state.duration)/500);
						$("#progress").attr("style", "width:"+width+"%");
						if(Math.floor(width) == 0){
							clearTimer();
						}
						if(!id && !state.paused){
							id = setInterval(frame, 500);
						}
						function frame() {
							width = width + speed; 
							$("#progress").attr("style", "width:"+width+"%"); 
							if(Math.floor(width)>100){
								clearTimer();
							}
						}
					}else{
						$("#details").show();
						$("#player").hide();
					}
				});
				player.addListener('ready', ({ device_id }) => {
						var now = new Date();
						var then = new Date(<?php echo $_SESSION['expire'] ?> * 1000);
						var timeout = (then.getTime() - now.getTime());
						setTimeout(function() { window.location.reload(true); }, timeout);
				});
				player.connect();
				$(document).on("click","i",function(){
					if(this.id == "next"){
						player.nextTrack().then(() => {
							clearTimer();
						});
					}else if(this.id == "previous"){
						player.previousTrack().then(() => {
							clearTimer();
						});
					}else{
						player.togglePlay().then(() => {
							clearInterval(id);
							id = false;
						});
					}
				});
				$(function(){
					$("#slider-range-max").slider({
						range: "max",
						min: 0,
						max: 1,
						value: 1,
						step: 0.00000000000000001,
						slide: function( event, ui ) {
							player.setVolume(ui.value).then(() => {
						});}
					});
				});
				function clearTimer() {
					clearInterval(id);
					id = false;
					$("#progress").attr("style", "width:0%");
				}	
			};
		</script>
	</div>
</body>
</html>