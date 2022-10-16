{
	"name" : "useradd",

	"info" : {
		"syntax" : "useradd &lt;username&gt;",
		"brief" : "Create a new user on the system",
	},

	"password" : '',
	"repeatPassword" : '',

	"keyPassword" : function ( e )
	{
		var evt = ( window.event ) ? window.event : e;
		var key = ( evt.charCode ) ? evt.charCode : evt.keyCode;
		var password = document.getElementsByName ( "password" )[0];
		var repeatPassword = document.getElementsByName ( "repeatPassword" )[0];
		var repeatPasswordText = document.getElementById ( "repeatPasswordText" );

		if ( key == 13 && password.value.length > 0 )
		{
			repeatPassword.style.visibility = 'visible';
			repeatPasswordText.style.visibility = 'visible';
			repeatPassword.focus();
		}
	},

	"keyRepeatPassword" : function ( e )
	{
		var evt = ( window.event ) ? window.event : e;
		var key = ( evt.charCode ) ? evt.charCode : evt.keyCode;
		var password = document.getElementsByName ( "password" )[0];
		var repeatPassword = document.getElementsByName ( "repeatPassword" )[0];

		if ( key == 13 && password.value.length > 0 )
		{
			if ( password.value != repeatPassword.value )
			{
				shell.cmdOut.innerHTML = 'The passwords do not match';
			} else {
				if ( shell.newuser.match ( /[^0-9a-zA-Z_]/ ))
				{
					shell.cmdOut.innerHTML = 'The username contains invalid characters, out of the charset [0-9a-zA-Z_]';
					return false;
				}

        var users_php = './modules/users/users.php';
				params = 'action=add&user=' + escape ( shell.newuser ) + '&pass=' + md5 ( password.value );

				var http = new XMLHttpRequest();
				http.open ( "POST", users_php, true );
				http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				http.onreadystatechange = function ()
				{
					if ( http.readyState == 4 && http.status == 200 )
					{
						shell.cmdOut.innerHTML = http.responseText;
						shell.auto_prompt_focus = true;
						shell.auto_prompt_refresh = true;
						shell.refreshPrompt ( false, false );
					}
				}

				http.send ( params );
				shell.cmdOut.innerHTML = '';
			}
		}
	},

	"action" : function ( arg )
	{
		var out = '';

		if ( !shell.has_users )
		{
			return "Users module not enabled<br/>\n";
		}


		if ( !arg || arg.length == 0 )
		{
			return "Usage: " + this.name + " &lt;username&gt;<br/>\n";
		}

		if ( arg.match ( /[^0-9a-zA-Z_]/ ))
		{
			return "Invalid character(s) in the username, range [0-9a-zA-Z_] allowed<br/>\n";
		}

		shell.keyPassword = this.keyPassword;
		shell.keyRepeatPassword = this.keyRepeatPassword;
		shell.newuser = arg;

		if ( shell.__first_cmd )
		{
			shell.cmdOut.innerHTML = '<br/>Password: <input type="password" ' +
				'name="password" class="password" ' +
				'onkeyup="shell.keyPassword ( event )">' +
				'<br/><span id="repeatPasswordText" style="visibility: hidden">' +
				'Repeat password: </span>' +
				'<input type="password" name="repeatPassword" class="password" ' +
				'style="visibility: hidden" onkeyup="shell.keyRepeatPassword ( event )"><br/>';

			shell.__first_cmd = false;
		} else {
			shell.cmdOut.innerHTML = 'Password: <input type="password" ' +
				'name="password" class="password" ' +
				'onkeyup="shell.keyPassword ( event )">' +
				'<br/><span id="repeatPasswordText" style="visibility: hidden">' +
				'Repeat password: </span>' +
				'<input type="password" name="repeatPassword" class="password" ' +
				'style="visibility: hidden" onkeyup="shell.keyRepeatPassword ( event )"><br/>';
		}

		shell.auto_prompt_focus = false;
		shell.auto_prompt_refresh = false;

		this.password = document.getElementsByName ( "password" )[0];
		this.password.focus();

		return out;
	},
}

