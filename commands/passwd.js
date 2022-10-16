{
	"name" : "passwd",

	"info" : {
		"syntax" : "passwd",
		"brief" : "Change the user password",
	},

	"keyOldPassword" : function ( e )
	{
		var evt = ( window.event ) ? window.event : e;
		var key = ( evt.charCode ) ? evt.charCode : evt.keyCode;
		var oldpassword = document.getElementsByName ( "oldpassword" )[0];
		var password = document.getElementsByName ( "password" )[0];
		var passwordText = document.getElementById ( "passwordText" );

		if ( key == 13 && oldpassword.value.length > 0 )
		{
			password.style.visibility = 'visible';
			passwordText.style.visibility = 'visible';
			password.focus();
		}
	},

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
		var oldpassword = document.getElementsByName ( "oldpassword" )[0];
		var password = document.getElementsByName ( "password" )[0];
		var repeatPassword = document.getElementsByName ( "repeatPassword" )[0];
		var repeatPasswordText = document.getElementById ( "repeatPasswordText" );

		if ( key == 13 && password.value.length > 0 )
		{
			if ( password.value != repeatPassword.value )
			{
				shell.cmdOut.innerHTML = 'The passwords do not match';
			} else {
        var users_php = './modules/users/users.php';
				params = 'action=changepwd&user=' + escape ( shell.newuser ) + '&newpass=' + md5 ( password.value );

				if ( shell.curUser != 'root' )
				{
					params += '&oldpass=' + md5 ( oldpassword.value );
				}

				var http = new XMLHttpRequest();
				http.open ( "POST", users_php, true );
				http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				http.onreadystatechange = function ()
				{
					if ( http.readyState == 4 && http.status == 200 )
					{
						shell.cmdOut.innerHTML = http.responseText;
						shell.refreshPrompt ( false, false );
					}
				}

				http.send ( params );
				shell.cmdOut.innerHTML = '';
			}

			shell.auto_prompt_focus = true;
			shell.auto_prompt_refresh = true;
			shell.refreshPrompt ( false, false );
		}
	},

	"action" : function ( arg )
	{
		var out = '';

		if ( !shell.has_users )
		{
			return "Users module not enabled<br/>\n";
		}


		shell.auto_prompt_focus = false;
		shell.auto_prompt_refresh = false;
		shell.newuser = arg;
		shell.keyOldPassword = this.keyOldPassword;
		shell.keyPassword = this.keyPassword;
		shell.keyRepeatPassword = this.keyRepeatPassword;

		var users_php = window.location.href;
		users_php = users_php.replace ( /\/([a-zA-Z\.]+)$/, '/modules/users/users.php' );
		params = 'action=getuser';

		var http = new XMLHttpRequest();
		http.open ( "POST", users_php, true );
		http.setRequestHeader( "Content-type", "application/x-www-form-urlencoded" );
		http.onreadystatechange = function ()
		{
			if ( http.readyState == 4 && http.status == 200 )
			{
				if ( shell.__first_cmd )
				{
					shell.cmdOut.innerHTML = '<br/>';
					shell.__first_cmd = false;
				} else {
					shell.cmdOut.innerHTML = '';
				}

				shell.curUser = http.responseText;

				if ( !arg || arg.length == 0 )
				{
					shell.newuser = http.responseText;
				}

				if ( http.responseText == 'root' )
				{
					shell.cmdOut.innerHTML += 'New password: <input type="password" ' +
						'name="password" class="password" ' +
						'onkeyup="shell.keyPassword ( event )"><br/>' +
						'<span id="repeatPasswordText" style="visibility: hidden">' +
						'Repeat new password: </span><input type="password" ' +
						'name="repeatPassword" class="password" style="visibility: hidden" ' +
						'onkeyup="shell.keyRepeatPassword ( event )"><br/>';

					document.getElementsByName ( 'password' )[0].focus();
				} else {
					if ( shell.newuser.length > 0 && shell.newuser != http.responseText )
					{
						shell.cmdOut.innerHTML = "You cannot change the password for user '" +
							shell.newuser + "'";

						shell.refreshPrompt ( false, false );
						return 1;
					} else if ( http.responseText == shell.json.user ) {
						shell.cmdOut.innerHTML = "You cannot change the password for the " +
							"guest user";

						shell.refreshPrompt ( false, false );
						return 1;
					}

					shell.cmdOut.innerHTML += 'Old password: <input type="password" ' +
						'name="oldpassword" class="password" ' +
						'onkeyup="shell.keyOldPassword ( event )"><br/>' +
						'<span id="passwordText" style="visibility: hidden">' +
						'New password: </span><input type="password" ' +
						'name="password" class="password" ' +
						'onkeyup="shell.keyPassword ( event )"><br/>' +
						'<span id="repeatPasswordText" style="visibility: hidden">' +
						'Repeat new password: </span><input type="password" ' +
						'name="repeatPassword" class="password" style="visibility: hidden" ' +
						'onkeyup="shell.keyRepeatPassword ( event )"><br/>';

					document.getElementsByName ( 'oldpassword' )[0].focus();
				}
			}
		}

		http.send ( params );
		shell.cmdOut.innerHTML = '';
		return out;
	},
}

