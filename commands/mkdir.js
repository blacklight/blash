{
	"name" : "mkdir",

	"info" :  {
		"syntax" : "mkdir &lt;directory name&gt;",
		"brief" : "Create a new directory",
	},

	"action" : function ( arg )
	{
		if ( !arg || arg.length == 0 )
		{
			return "mkdir: Parameter expected<br/>\n";
		}

		shell.auto_prompt_focus = false;
		shell.auto_prompt_refresh = false;
		arg = shell.expandPath ( arg );

		var users_php = './modules/users/users.php';
		params = 'action=mkdir&dir=' + escape ( arg );

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
				shell.auto_prompt_focus = true;
				shell.auto_prompt_refresh = true;
			}
		}

		http.send ( params );
		shell.cmdOut.innerHTML = '';
	}
}

