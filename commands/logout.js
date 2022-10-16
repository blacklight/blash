{
	"name" : "logout",

	"info" : {
		"syntax" : "logout",
		"brief" : "End the current user session",
	},

	"action" : function ( arg )
	{
		var out = '';

		if ( !shell.has_users )
		{
			return "Users module not enabled<br/>\n";
		}


		if ( shell.user == shell.json.user )
		{
			return out;
		}

		shell.user = shell.json.user;
		document.cookie = '';

		var users_php = './modules/users/users.php';
		params = 'action=logout';

		var http = new XMLHttpRequest();
		http.open ( "POST", users_php, true );
		http.setRequestHeader( "Content-type", "application/x-www-form-urlencoded" );

		http.onreadystatechange = function ()
		{
			if ( http.readyState == 4 && http.status == 200 )
			{
				shell.refreshFiles();
			}
		}

		http.send ( params );
		shell.path = shell.json.basepath;

		var json_config = './system/config.js';
		var http2 = new XMLHttpRequest();
		http2.open ( "GET", json_config, true );

		http2.onreadystatechange = function ()
		{
			if ( http2.readyState == 4 && http2.status == 200 )
			{
				shell.json = eval ( '(' + http2.responseText + ')' );
			}
		}

		http2.send ( null );
		return out;
	},
}

