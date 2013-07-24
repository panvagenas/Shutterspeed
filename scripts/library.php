<?php

/**
 * This is the heart of the Shutterspeed project. Most of what you'll see in here is functions taking
 * the Facebook object apart and constructing arrays out of pieces of usefull information,
 * database handling and quick page tab apps solutions. Have fun!
 */
 
header('p3p: CP="NOI ADM DEV PSAi COM NAV OUR OTR STP IND DEM HONK CAO PSA OUR"');

date_default_timezone_set('Europe/Athens');

include_once "facebook.php";

$conf = array(
	'appId'        => "489630314445262",										// TODO Change to app id
	'secret'       => "09bf5535424b23c9f8fe6a23359c2cb2",						// TODO Change this to app secret
	'redirect_uri' => "https://www.facebook.com/testtpage/app_489630314445262",	// TODO Change to app redirect uri
	'scope'        => "user_likes , publish_stream , photo_upload  , email",
	'cookie'       => true,
	'fileUpload'   => true
);

$fb = new Facebook($conf);

$data = $fb->getSignedRequest();

$username='teamzero';
$password='oGwl1GhVeQVQYqZD';
$host='mysql:host=localhost;port=3307;dbname=facebook;';
try{
	$conn = new PDO($host, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
	echo 'ERROR: '.$e->getMessage();
}

if($fb->getUser() != 0){
	$uid = $fb->getUser();
	
	$user_data = $fb->api('/'.$uid);
	
	$fbuser = array( "id" => $uid,
									 "first_name" => $user_data['first_name'],
									 "last_name" => $user_data['last_name'],
									 "email" => $user_data['email'],
									 "gender" => $user_data['gender'],
									 "profileURL" => $user_data['link']
								 );
}

if(!strstr($_SERVER['HTTP_REFERER'], 'teamzero')){
	$_SESSION['signed_request'] = $fb->getSignedRequest();
}

/**
 * The userID function reads the User ID from Facebook and returns it - plain and simple.
 * 
 * @return int, Facebook User ID / 0 on failure
 * 
 * @since 1.0
 */

function userID(){
  global $fb;
	$uid = false;
	
	if($fb->getUser() != 0){
		$uid = $fb->getUser();
	}
	
	return $uid;
}

/**
 * The like function checks and declares if the user likes the page that hosts the application in its tab.
 * 
 * @return bool, True if user has liked / false if not
 * 
 * @since 1.0
 */

function like() {
	global $fb;
	$like = false;
	
	$like_status = $_SESSION['signed_request']["page"]["liked"];
	 
	if($like_status){
		$like = true;
	}
	
	return $like;
}

/**
 * Get a friends list of the current user
 * 
 * @param string $fields List of fields to be returned, coma seperated. Default 'id,name'.
 * 
 * @return an array of arrays containing the friends of current user with any other field requested
 * 
 * @since 1.0
 * @author panvagenas
 */

function getFriends($fields='id,name'){
	global $fb;
	
	$friends = $fb->api('/' . $fb->getUser() . '/friends?fields='.$fields);
	
	return $friends;
}


/**
 * The auth function checks and declares if the user has authorized the application.
 * 
 * @return bool, True if user has authorized / false if not
 * 
 * @since 1.0
 */

function auth() {
	global $fb;
	$auth = false;
	
	if($fb->getUser() != 0){
		$auth = true;
	}
	
	return $auth;
}

/**
 * The insertDB function is used to store information we need into our database for faster access and to generate
 * less traffic with the facebook API.
 * 
 * WARNING(1): User information stored from Facebook's database should not be used for commercial purposes, since it is illegal.
 * WARNING(2): Images can but SHOULD NOT be stored in databases since they make it too heavy and slow.
 * 
 * @param string $table The table name for the information to be stored
 * @param array $fields The list of fields in your database table
 * @param array $values The corresponding value for each field to be filled
 * 
 * @return bool, True if the operation succeded / false if not
 * 
 * @since 1.0
 */

function insertDB($table, $fields, $values){
	global $conn;
	$inserted = false;
	$fieldsList = '';
	$valueNum = '';
	
	foreach($fields as $field){
		$fieldsList .= $field.', ';
		$valueNum .= '?, ';
	}
	
	$fieldsList = substr($fieldsList, 0, -2);
	$valueNum = substr($valueNum, 0, -2);
	
	try{
		$query = $conn->prepare("INSERT INTO ".$table." (".$fieldsList.") VALUES (".$valueNum.")");
		$query->execute($values);
		$inserted = true;
	}catch(PDOException $e){
		return 'ERROR: '.$e->getMessage();
	}
	
	return $inserted;
}

/**
 * The countParticipations function counts the rows of the participations table where the current user's id is present.
 * It then returns the result.
 * 
 * @param string $table The table name for the information to be stored
 * @param int $user The logged in user's Facebook ID
 * 
 * @return int, the number of rows in the participation table
 * 
 * @since 1.0
 */

function countParticipations($table, $user){
	global $conn;
	
	try{
	  $select = "SELECT * FROM ".$table." WHERE user_fb_id=?";
	  $query = $conn->prepare($select);
	  $query->execute(array($user));
		$count = $query->rowCount();
	}catch(PDOException $e){
	    echo 'ERROR: '.$e->getMessage();
	}
	
	return $count;
}

/**
 * The listParticipations function collects all the data from the current user's participation table.
 * Then returns it in an array.
 * 
 * @param string $table The table name for the information to be stored
 * @param int $user The logged in user's Facebook ID
 * 
 * @return int, The logged in user's Facebook ID
 * 
 * @since 1.0
 */

function listParticipations($table, $user, $params){
	global $conn;
	
	try{
	  $select = "SELECT * FROM ".$table." WHERE user_fb_id=? ".$params;
	  $query = $conn->prepare($select);
	  $query->execute(array($user));
		$recieved=$query->fetchAll();
	}catch(PDOException $e){
	    echo 'ERROR: '.$e->getMessage();
	}
	
	return $recieved;
}

/**
 * The parsePageSignedRequest function collects the information passed by Facebook into the page tab's iframe and
 * returns an array with all the information collected. (This one was leeched from somewhere in the web, kudos to the creator).
 * 
 * @return array, the data from Facebook's signed request / false on failure
 * 
 * @since 1.0
 */

function parsePageSignedRequest() {
	
	 if (isset($_REQUEST['signed_request'])) {
		 $encoded_sig = null;
		 $payload = null;
		 list($encoded_sig, $payload) = explode('.', $_REQUEST['signed_request'], 2);
		 $sig = base64_decode(strtr($encoded_sig, '-_', '+/'));
		 $data = json_decode(base64_decode(strtr($payload, '-_', '+/'), true));
		 return $data;
	 }
	 return false;
}

/**
 * The redirectToPageTab function is used for applications that must, strictly, run under a Facebook page tab.
 * It checks the URL that called the page and if it is under the app.facebook.com domain, it redirects the user
 * to the page tab application using Javascript.
 * 
 * @param string $appUrl The url of the page tab application / Just go in there and copy-paste it
 * 
 * @return NONE (All you get is a redirect to the correct URL)
 * 
 * @since 1.0
 */

function redirectToPageTab($appUrl){
	
	$ref  = $_SERVER["HTTP_REFERER"];
	$link = substr($ref, 0, 12);
	
	if( $link == 'https://apps' OR $link == 'http://apps.' ){
	  echo "<script>window.top.location.href  = '".$appUrl."' </script>";
	  exit;
	}
}

/**
 * The editImgs function is mainly used to create images that will be automatically uploaded to the user's profile.
 * It consists of many parts and it is strongly recommended that it sould be edited, at least for the early days of
 * the framework's development so that it suits your needs. It includes native PHP functions to merge images.
 * WARNING: Before editing, make sure you have a basic understanding of what functions like imagecopymerge() do and how.
 * WARNING: The generated folder MUST have 777 permissions so that the script can create the file.
 * 
 * @param int $user The Facebook User ID of the current user
 * @param string $bg URL of the background picture, as a JPEG type
 * @param string $image URL of the image to be merged with the background
 * @param int $coordX Horizontal coordinate from the left edge of the background, where the target image will be placed
 * @param int $coordY Vertical coordinate from the top edge of the background, where the target image will be placed
 * 
 * @return int, The Facebook User ID of the current user - same as user's input variable
 * @return A file with the user's ID as its name and the suffix .png in the folder img/generated (ex. 287654321.png)
 * 
 * @since 1.0
 */

function editImgs($user, $bg, $image, $coordX, $coordY){
	
	$imgbg = imagecreatefromjpeg($bg);
	
	$imgadd = imagecreatefromjpeg($image);
		
	imagecopymerge($imgbg, $imgadd, $coordX, $coordY, 0, 0, imagesx($imgadd), imagesy($imgadd), 100);
	
	imagepng($imgbg, 'img/generated/'.$user.'.png');
	
	ImageDestroy($imgbg);
	
	return $user;
}

/**
 * The editPngs function is mainly used to create images that will be automatically uploaded to the user's profile.
 * It consists of many parts and it is strongly recommended that it sould be edited, at least for the early days of
 * the framework's development so that it suits your needs. It includes native PHP functions to merge images.
 * WARNING: Before editing, make sure you have a basic understanding of what functions like imagecopymerge() do and how.
 * WARNING: The generated folder MUST have 777 permissions so that the script can create the file.
 * 
 * @param int $user The Facebook User ID of the current user
 * @param string $bg URL of the background picture, as a JPEG type
 * @param string $image URL of the image to be merged with the background
 * @param int $coordX Horizontal coordinate from the left edge of the background, where the target image will be placed
 * @param int $coordY Vertical coordinate from the top edge of the background, where the target image will be placed
 * 
 * @return int, The Facebook User ID of the current user - same as user's input variable
 * @return A file with the user's ID as its name and the suffix .png in the folder img/generated (ex. 287654321.png)
 * 
 * @since 1.0
 */

function editPngs($user, $bg, $image, $coordX, $coordY){
	
	$imgbg = imagecreatefrompng($bg);
	
	$imgadd = imagecreatefromjpeg($image);
		
	imagecopymerge($imgbg, $imgadd, $coordX, $coordY, 0, 0, imagesx($imgadd), imagesy($imgadd), 100);
	
	imagepng($imgbg, 'img/generated/'.$user.'.png');
	
	ImageDestroy($imgbg);
	
	return $user;
}

/**
 * The editImgs function is mainly used to create images that will be automatically uploaded to the user's profile.
 * It consists of many parts and it is strongly recommended that it sould be edited, at least for the early days of
 * the framework's development so that it suits your needs. It includes native PHP functions to merge images with text.
 * WARNING: Before editing, make sure you have a basic understanding of what functions like imagettftext() do and how.
 * WARNING: The generated folder MUST have 777 permissions so that the script can create the file.
 * 
 * @param int $user The Facebook User ID of the current user
 * @param string $image URL of the image to be merged with the background
 * @param string $texts An array of strings to be merged with the image
 * @param array $coords An array of arrays that contain the merge coordinates (x, y) for each text
 * @param array $colors An array of colors (R, G, B) for each text to be merged
 * @param string $font The URL of the font used to create the text
 * @param int $size The size of the text to be merged (in pt)
 * 
 * @return int, The Facebook User ID of the current user - same as user's input variable
 * @return A file with the user's ID as its name and the suffix .png in the folder img/generated (ex. 287654321.png)
 * 
 * @since 1.0
 */

function editImgTxt($user, $image, $texts, $coords, $colors, $font, $size){
	
	//header("Content-Type: image/jpeg");
	
	$imgbg = imagecreatefromjpeg($image);
	
	$i = 0;
	foreach($coords as $where){
		$color = imagecolorallocate($imgbg, $colors[$i][1], $colors[$i][2], $colors[$i][3]);
		imagettftext($imgbg, $size, 0, $where[1], $where[2], $color, $font, $text);
		$i++;
	}
	
	imagepng($imgbg, 'img/generated/'.$user.'.png');
	
	ImageDestroy($imgbg);
	
	echo $userId;
}

/**
 * A simple function to generate a personal code for the user, intended for any use where you need a unique identifier for the user.
 * Since it is MD5 encoded, it cannot be recognized.
 * 
 * @return string, The unique identifier generated
 * 
 * @since 1.0
 */
 
function personalCode(){
	$id=md5(uniqid(time().session_id()));
	return $id;
}

/**
 * bit.ly PHP url shortening function. This should be enough to give you an idea of what it does. Gets your bit.ly credentials and the
 * URL you want to shorten as input and outputs a shortened URL for any use.
 * The URL to get the appKey from: http://bitly.com/a/your_api_key.
 * 
 * @param string $url The url to be shortened
 * @param string $login Login name to bitly.com
 * 
 * @return string, The shortened url
 * 
 * @since 1.0
 */

function get_bitly_short_url($url, $login, $appkey) {
	$ch = curl_init('http://api.bitly.com/v3/shorten?login='.$login.'&apiKey='.$appkey.'&longUrl='.$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	 
	$result = json_decode(curl_exec($ch));
	return $result->data->url;
}

/**
 * The fbShare function creates a link/story on the timeline of the user. It still is a work in progress since this function
 * can also create custom links under the share block of facebook.
 * 
 * @param string $link The URL you want the user to share
 * @param string $title The title/text to appear as link at the top of the share box
 * @param string $picURL The URL of the picture to share (200x200 pixels)
 * @param string $message The message you want to appear over the share box, looks like the user wrote it
 * @param string $caption A short text to give an idea of what the user shared
 * @param string $description A longer explanation about what this link is about
 * 
 * @since 1.0
 */
 
function fbShare($link, $title, $picURL, $message, $caption, $description){
	$parameters = array(
									'link' => $link,
									'name' => $title,
									'picture' => $picURL,
									'message' => $message,
									'caption' => $caption,
									'description' => $description,
									'access_token' => $fb->getAccessToken()
								);
	
	$fb->api(userID().'/feed', 'post', $parameters);
}

?>