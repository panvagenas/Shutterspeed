window.fbAsyncInit = function() {

  FB._https = true;

  FB.init({
  	appId      : 'XXXXXXXXXXXXXXX', // TODO Change to App ID
    channelURL : '//WWW.YOUR_DOMAIN.COM/channel.html', // TODO Change to Channel File
    status     : true, // check login status
    cookie     : true, // enable cookies to allow the server to access the session
    oauth      : true, // enable OAuth 2.0
    xfbml      : true  // parse XFBML
  });
    
  FB.Canvas.setAutoGrow();
    
};