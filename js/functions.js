$(document).ready(function(){
  
	$('#loading').fadeOut(300);
	
});

$(document).ajaxStart(function(){
	$('#loading').fadeIn(100);
});

$(document).ajaxStop(function(){
	$('#loading').fadeOut(300);
});

/**
 * Basic authorization action to allow access to the user's profile data, based on the scope we choose.
 * 
 *@param string perms The text to be displayed in the error message
 *
 *@since 1.0
 */

function login(perms){
	
	FB.login(function(response) {
	 if (response.authResponse) {
			//Do something
			window.location = 'index.php?page=game';
		} else {
		 console.log('User cancelled login or did not fully authorize.');
		}
	}, {scope: 'email,user_likes,publish_stream,photo_upload'});
	
}

/**
 * The error function displays a warning window at the top right of the body for the user to see.
 * 
 * @param string text The text to be displayed in the error message
 * 
 * @since 1.0
 */

function error(text){
	$('#error').remove();
	$('body').prepend('<div id="error"><p><img src="img/buttons/warning.png" alt="Προσοχή!" /><br /><span>'+text+'</span></p></div>');
}

/**
 * The global_redir function redirects the user to some URL outside the Facebook iframe.
 * 
 * @param string redirUrl The URL to redirect to
 * 
 * @since 1.0
 */

function global_redir(redirUrl){
	window.top.location = redirUrl;
}

/**
 * The iframe_redir function redirects the user to some URL inside the Facebook iframe.
 * 
 * @param string redirUrl The URL to redirect to
 * 
 * @since 1.0
 */

function iframe_redir(redirUrl){
	window.location = redirUrl;
}

/**
 * The wallpost function automatically uploads a picture to the user's profile. It is placed
 * in an album named after the application.
 * 
 * @param string photoUrl The URL of the picture to be uploaded
 * 
 * @since 1.0
 */

function wallpost(photoUrl){
	FB.api('/me/photos', 'post', {
		message: '',
		url: photoUrl
	}, postCallback);
}

function postCallback(){
	return;
}

/**
 * The timelineShare function displays the pop-up window of Facebook, in order for the user to share a link to display
 * in their timeline.
 * 
 * @param string url The URL to be shared
 * @param string name The title of the shared link
 * @param string picture The of the picture that will be displayed next to the text
 * @param string caption Some text as subtitle
 * @param string description A short description of the link shared
 * 
 * @since 1.0
 */

function timelineShare(url, name, picture, caption, description){
	var obj = {
		method: 'feed',
		link: url,
		name: name,
		picture: picture,
		caption: caption,
		description: description
	};
	
	FB.ui(obj, shareCallback);
}

/**
 * TODO Some description
 * 
 * @since 1.0
 */
function shareCallback(){
	$.get('scripts/participation.php');
}

/**
 * The appRequest function displays the pop-up window of Facebook, in order for the user to choose friends who will recieve a
 * notification/invitation to try out the application.
 * 
 * @param string text The text that will be displayed in the App Center (255 Characters Max
 * @param int friends The number of the maximun recipients for each request (20 Max)
 * 
 * @since 1.0
 */

function appRequest(text, friends){
	FB.ui({
		method: 'apprequests', 
		message: text,
		max_recipients: friends
	});
}

/**
 * The preload function downloads a number of images from the server to the user's RAM to be shown instantly on mouseover
 * or onclick events.
 * 
 * @param string images The file paths seperated by commas
 * 
 * @since 1.0
 */

function preload(images) {
	if(document.images) {
		var i = 0;
		var imageArray = new Array();
		imageArray = images.split(',');
		var imageObj = new Image();
		for(i=0; i<=imageArray.length-1; i++) {
			imageObj.src=imageArray[i];
		}
	}
}

/**
 * The appRequestReturn function displays the pop-up window of Facebook, in order for the user to choose friends who will recieve a
 * notification/invitation to try out the application. It is STRICTLY connected to its callback function, returnID.
 * appRequestReturn
 * 
 * @param string text The text that will be displayed in the App Center (255 Characters Max)
 * @param int friends The number of the maximun recipients for each request (20 Max)
 * 
 * @since 1.0
 */

function appRequestReturn(text, friends){
	FB.ui({
		method: 'apprequests', 
		message: text,
		max_recipients: friends
	}, returnID);
}

/**
 * TODO Some description
 * 
 * @param data
 * 
 * @since 1.0
 */
function returnID(data){
	friendID = data.to;
	$('#returnedData').html('<img src="http://graph.facebook.com/'+friendID+'/picture" class="'+friendID+'" />');
}
