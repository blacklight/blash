{
	"name" : "mv",

	"info" :  {
		"syntax" : "mv &lt;source file&gt; &lt;destination file&gt;",
		"brief" : "Move a file to another",
	},

	"action" : function ( arg )
	{
		var src = null;
		var dest = null;

		if ( !arg || arg.length == 0 )
		{
			return "Usage: " + this.info.syntax + "<br/>\n";
		}

		if ( arg.match ( /^\s*('|")([^'|"]+)('|")/ ))
		{
			src = RegExp.$2;
			arg = arg.replace ( new RegExp ( '^\s*' + RegExp.$1 + RegExp.$2 + RegExp.$3 + '\s*' ), '' );
		} else if ( arg.match ( /^\s*([^\s]+)/ )) {
			src = RegExp.$1;
			arg = arg.replace ( new RegExp ( '^\s*' + RegExp.$1 + '\s*' ), '' );
		} else {
			return "Usage: " + this.info.syntax + "<br/>\n";
		}

		if ( !src || arg.length == 0 )
		{
			return "Usage: " + this.info.syntax + "<br/>\n";
		}

		if ( arg.match ( /^\s*('|")([^'|"]+)('|")/ ))
		{
			dest = RegExp.$2;
		} else {
			arg.match ( /^\s*(.*)$/ );
			dest = RegExp.$1;
		}

		src = shell.expandPath ( src );
		dest = shell.expandPath ( dest );

		shell.auto_prompt_refresh = false;

		var users_php = './modules/users/users.php';
		params = 'action=mv&src=' + escape ( src ) + '&dest=' + escape ( dest );

		var http = new XMLHttpRequest();
		http.open ( "POST", users_php, true );
		http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http.onreadystatechange = function ()
		{
			if ( http.readyState == 4 && http.status == 200 )
			{
				shell.cmdOut.innerHTML = http.responseText;
				shell.refreshFiles();
				shell.refreshPrompt ( false, false );
				shell.auto_prompt_refresh = true;
			}
		}

		http.send ( params );
		shell.cmdOut.innerHTML = '';
	}
}

