{
	"name" : "ln",

	"info" : {
		"syntax" : "ln &lt;path or URL&gt; &lt;link name&gt;",
		"brief" : "Link a specified path inside the system or a URL to a new link file",
	},

	"action" : function ( arg )
	{
		var res = null;
		var link = null;

		if ( !arg || arg.length == 0 )
		{
			return "Usage: " + this.info.syntax + "<br/>\n";
		}

		if ( arg.match ( /^\s*('|")([^'|"]+)('|")/ ))
		{
			res = RegExp.$2;
			arg = arg.replace ( new RegExp ( '^\s*' + RegExp.$1 + RegExp.$2 + RegExp.$3 + '\s*' ), '' );
		} else if ( arg.match ( /^\s*([^\s]+)/ )) {
			res = RegExp.$1;
			arg = arg.replace ( new RegExp ( '^\s*' + RegExp.$1 + '\s*' ), '' );
		} else {
			return "Usage: " + this.info.syntax + "<br/>\n";
		}

		if ( !res || arg.length == 0 )
		{
			return "Usage: " + this.info.syntax + "<br/>\n";
		}

		if ( arg.match ( /^\s*('|")([^'|"]+)('|")/ ))
		{
			link = RegExp.$2;
		} else {
			arg.match ( /^\s*(.*)$/ );
			link = RegExp.$1;
		}

		var link_type = null;

		if ( res.match ( /^[a-z0-9]+:\/\// ))
		{
			link_type = 'href';
		} else {
			link_type = 'local';
		}

		var users_php = './modules/users/users.php';
		shell.auto_prompt_refresh = false;
		link = shell.expandPath ( link );
		params = 'action=link&resource=' + escape ( res ) + '&link=' + escape ( link ) + '&type=' + escape ( link_type );

		var http = new XMLHttpRequest();
		http.open ( "POST", users_php, true );
		http.setRequestHeader( "Content-type", "application/x-www-form-urlencoded" );
		http.onreadystatechange = function ()
		{
			if ( http.readyState == 4 && http.status == 200 )
			{
				shell.cmdOut.innerHTML = http.responseText;
				shell.refreshFiles();
				shell.auto_prompt_refresh = true;
				shell.refreshPrompt ( false, false );
			}
		}

		http.send ( params );
		shell.cmdOut.innerHTML = '';
	}
}

