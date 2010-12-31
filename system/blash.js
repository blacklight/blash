/*****************************************************************
 *                                                               *
 * blash - An AJAX CMS for browsing your web site like a shell   *
 *                                                               *
 * by BlackLight <blacklight@autistici.org>, (C) 2010            *
 * Web: http://0x00.ath.cx                                       *
 * Released under GPL licence v.3                                *
 *                                                               *
 *****************************************************************/

var shell = null;

function blash ()
{
	/************ ATTRIBUTES **************/
	/** Current user */
	this.user = '';

	/** Home directory */
	this.home = '/';

	/** Object containing the parsed JSON configuration object */
	this.json = {};

	/** Object containing the files in the shell */
	this.files = {};

	/** Shell window object */
	this.window = document.getElementById ( "blashWindow" );

	/** Array containing the codes of the shell commands */
	this.commands = new Array();

	/** Escape sequences to be parsed in the prompt text */
	this.promptSequences = new Array();

	/** Array containing the history of given commands */
	this.history = new Array();

	/** Index to the current history element */
	this.history_index = -1;

	/** Current path */
	this.path = '/';

	/** Element containing the output of the command */
	this.cmdOut = document.getElementById ( "blashCmdOut" );

	/** Element containing the text of the prompt */
	this.promptText = document.getElementById ( "promptText" );

	/** Input field used as prompt */
	this.prompt = document.getElementsByName ( "blashPrompt" )[0];

	/** Counter of the open <span> tags when replacing the colours in the command prompt */
	this.__open_spans = 0;

	/** Check if this is the first command given in this session (for fixing <br/> stuff) */
	this.__first_cmd = true;

	/** Variable set if the prompt is re-generated automatically after a command was given */
	this.auto_prompt_refresh = true;

	/** Variable set if the focus should be automatically set to the prompt line after a command */
	this.auto_prompt_focus = true;

	/** Variable set if the current implementation of blash uses the user module */
	this.has_users = false;

	/** Path to the file containing the files directory */
	this.files_json = window.location.href;
	/**************************************/

	this.loadCommand = function ( cmd )
	{
		var cmd_file = window.location.href;
		cmd_file = cmd_file.replace ( /\/([a-zA-Z\.]+)$/, '/commands/' + cmd  + ".json" );

		var http = new XMLHttpRequest();
		http.open ( "GET", cmd_file, true );

		http.onreadystatechange = function ()
		{
			if ( http.readyState == 4 && http.status == 200 )
			{
				shell.commands.push ( eval ( '(' + http.responseText + ')' ));
			}
		}

		http.send ( null );
	}

	if ( document.cookie )
	{
		if ( document.cookie.match ( 'auth=' ) && document.cookie.match ( 'username=([^;]+);?' ))
		{
			this.user = RegExp.$1;
			var params = 'action=getuser';
			var users_php = window.location.href;
			users_php = users_php.replace ( /\/([a-zA-Z\.]+)$/, '/modules/users/users.php' );

			var xml = new XMLHttpRequest();
			xml.open ( "POST", users_php, true );
			xml.setRequestHeader ( "Content-type", "application/x-www-form-urlencoded" );
			xml.setRequestHeader ( "Content-length", params.length );
			xml.setRequestHeader ( "Connection", "close" );

			xml.onreadystatechange = function ()
			{
				if ( xml.readyState == 4 && xml.status == 200 )
				{
					if ( xml.responseText.length > 0 )
					{
						shell.user = xml.responseText;
					} else {
						shell.user = shell.json.user;
					}
				}
			}

			xml.send ( params );

			var xml2 = new XMLHttpRequest();
			xml2.open ( "POST", users_php, true );
			xml2.setRequestHeader ( "Content-type", "application/x-www-form-urlencoded" );
			xml2.setRequestHeader ( "Content-length", params.length );
			xml2.setRequestHeader ( "Connection", "close" );
			params = 'action=gethome';

			xml2.onreadystatechange = function ()
			{
				if ( xml2.readyState == 4 && xml2.status == 200 )
				{
					if ( xml2.responseText.length > 0 )
					{
						shell.home = xml2.responseText;
						shell.path = shell.home;
					} else {
						shell.user = shell.json.user;
					}
				}
			}

			xml2.send ( params );
		}
	}

	this.prompt.focus();

	var json_config = window.location.href;
	json_config = json_config.replace ( /\/([a-zA-Z\.]+)$/, '/system/blash.json' );

	var http = new XMLHttpRequest();
	http.open ( "GET", json_config, true );

	http.onreadystatechange = function ()
	{
		if ( http.readyState == 4 && http.status == 200 )
		{
			shell.json = eval ( '(' + http.responseText + ')' );

			if ( shell.user == '' )
			{
				shell.user = shell.json.user;
			}

			shell.promptText.innerHTML = ( shell.json.promptText ) ? shell.json.promptText : "[%n@%m %W] $ ";
			shell.promptText.innerHTML = shell.unescapePrompt ( promptText.innerHTML, shell.json.promptSequences );

			if ( shell.json.banner.length > 0 )
			{
				var banner = document.createElement ( "span" );
				banner.setAttribute ( "id", "banner" );
				banner.innerHTML = shell.json.banner;
				shell.window.insertBefore ( banner, shell.promptText );
			}

			for ( var i in shell.json.commands )
			{
				shell.loadCommand ( shell.json.commands[i] );
			}

			shell.has_users = false;

			for ( var i=0; i < shell.json.modules.length; i++ )
			{
				var module = shell.json.modules[i];

				if ( module.name == 'users' )
				{
					shell.has_users = module.enabled;
					break;
				}
			}

			shell.files_json = window.location.href;

			if ( shell.has_users )
			{
				shell.files_json = shell.files_json.replace ( /\/([a-zA-Z\.]+)$/, '/modules/users/files.php' );
			} else {
				shell.files_json = shell.files_json.replace ( /\/([a-zA-Z\.]+)$/, '/system/files.json' );
			}

			var http2 = new XMLHttpRequest();
			http2.open ( "GET", shell.files_json, true );

			http2.onreadystatechange = function ()
			{
				if ( http2.readyState == 4 && http2.status == 200 )
				{
					shell.files = eval ( '(' + http2.responseText + ')' );

					// Remove duplicates
					var tmp = new Array();

					for ( var i in shell.files )
					{
						var contains = false;

						for ( var j=0; j < tmp.length && !contains; j++ )
						{
							if ( shell.files[i].path == tmp[j].path )
							{
								contains = true;
							}
						}

						if ( !contains )
						{
							tmp.push ( shell.files[i] );
						}
					}

					shell.files = tmp;
				}
			}

			http2.send ( null );

		}
	}

	http.send ( null );

	this.getKey = function ( e )
	{
		var evt = ( window.event ) ? window.event : e;
		var key = ( evt.charCode ) ? evt.charCode : evt.keyCode;

		if ( key == 68 && evt.ctrlKey )
		{
			/* CTRL-d -> logout */
			for ( i=0; i < this.commands.length; i++ )
			{
				if ( this.commands[i].name == 'logout' )
				{
					var out = this.commands[i].action ();

					if ( this.auto_prompt_refresh )
					{
						var value = this.prompt.value;
						var out = this.cmdOut.innerHTML;

						var text = ( shell.json.promptText ) ? shell.json.promptText : "[%n@%m %W] $ ";
						text = shell.unescapePrompt ( text, shell.json.promptSequences );

						this.window.removeChild ( this.prompt );
						this.window.removeChild ( this.cmdOut );

						if ( this.__first_cmd && this.prompt.value.length > 0 )
						{
							this.window.innerHTML += value + '<br/>' + out + text;
							this.__first_cmd = false;
						} else {
							if ( out )
							{
								if ( out.match ( /^\s*<br.?>\s*/ ))
								{
									out = '';
								}
							}

							this.window.innerHTML += value + '<br/>' + out + text;
						}

						this.prompt = document.createElement ( 'input' );
						this.prompt.setAttribute ( 'name', 'blashPrompt' );
						this.prompt.setAttribute ( 'type', 'text' );
						this.prompt.setAttribute ( 'class', 'promptInput' );
						this.prompt.setAttribute ( 'autocomplete', 'off' );
						this.prompt.setAttribute ( 'onkeydown', 'shell.getKey ( event )' );
						this.prompt.setAttribute ( 'onkeyup', 'this.focus()' );
						this.prompt.setAttribute ( 'onblur', 'return false' );

						this.cmdOut = document.createElement ( 'div' );
						this.cmdOut.setAttribute ( 'id', 'blashCmdOut' );
						this.cmdOut.setAttribute ( 'class', 'blashCmdOut' );
						this.cmdOut.innerHTML = '<br/>';

						this.window.appendChild ( this.prompt );
						this.window.appendChild ( this.cmdOut );

						if ( this.auto_prompt_focus )
						{
							this.prompt.focus();
						}
					}
				}
			}

			return false;
		} else if ( key == 76 && evt.ctrlKey ) {
			// CTRL-l clears the screen
			this.refreshPrompt ( true, false );
			return false;
		} else if ( key == 13 || key == 10 || ( key == 67 && evt.ctrlKey )) {
			if ( this.prompt.value.length != 0 && ( key != 67 || !evt.ctrlKey ))
			{
				this.prompt.value.match ( /^([^\s]+)\s*(.*)$/ );
				var cmd = RegExp.$1;
				var arg = RegExp.$2;
				var cmd_found = false;
				this.history.push ( this.prompt.value );
				this.history_index = -1;

				for ( i=0; i < this.commands.length && !cmd_found; i++ )
				{
					if ( this.commands[i].name == cmd )
					{
						cmd_found = true;
						var out = this.commands[i].action ( arg );

						if ( out )
						{
							if ( out.length > 0 )
							{
								this.cmdOut.innerHTML = out;
							}
						}
					}
				}

				if ( !cmd_found )
				{
					this.cmdOut.innerHTML = this.json.shellName + ": command not found: " + cmd + '<br/>';
				}
			}

			if ( this.auto_prompt_refresh )
			{
				var value = this.prompt.value;
				var out = this.cmdOut.innerHTML;

				var text = ( shell.json.promptText ) ? shell.json.promptText : "[%n@%m %W] $ ";
				text = shell.unescapePrompt ( text, shell.json.promptSequences );

				this.window.removeChild ( this.prompt );
				this.window.removeChild ( this.cmdOut );

				if ( this.__first_cmd && this.prompt.value.length > 0 )
				{
					this.window.innerHTML += value + '<br/>' + out + text;
					this.__first_cmd = false;
				} else {
					if ( out )
					{
						if ( out.match ( /^\s*<br.?>\s*/ ))
						{
							out = '';
						}
					}

					this.window.innerHTML += value + '<br/>' + out + text;
				}

				this.prompt = document.createElement ( 'input' );
				this.prompt.setAttribute ( 'name', 'blashPrompt' );
				this.prompt.setAttribute ( 'type', 'text' );
				this.prompt.setAttribute ( 'class', 'promptInput' );
				this.prompt.setAttribute ( 'autocomplete', 'off' );
				this.prompt.setAttribute ( 'onkeydown', 'shell.getKey ( event )' );
				this.prompt.setAttribute ( 'onkeyup', 'this.focus()' );
				this.prompt.setAttribute ( 'onblur', 'return false' );

				this.cmdOut = document.createElement ( 'div' );
				this.cmdOut.setAttribute ( 'id', 'blashCmdOut' );
				this.cmdOut.setAttribute ( 'class', 'blashCmdOut' );
				this.cmdOut.innerHTML = '<br/>';

				this.window.appendChild ( this.prompt );
				this.window.appendChild ( this.cmdOut );

				if ( this.auto_prompt_focus )
				{
					this.prompt.focus();
				}
			}

			if ( key == 67 && evt.ctrlKey )
			{
				return false;
			}
		} else if (( key == 38 || key == 40 ) && this.history.length > 0 ) {
			if ( key == 38 )
			{
				if ( this.history_index < 0 )
				{
					this.history_index = this.history.length - 1;
					this.prompt.value = this.history[ this.history_index ];
				} else if ( this.history_index == 0 ) {
					// We're already on the first history element
				} else {
					this.history_index--;
					this.prompt.value = this.history[ this.history_index ];
				}
			} else if ( key == 40 ) {
				if ( this.history_index < 0 )
				{
					// We're already on the last element, don't do anything
				} else if ( this.history_index == this.history.length - 1 ) {
					this.prompt.value = '';
				} else {
					this.history_index++;
					this.prompt.value = this.history[ this.history_index ];
				}
			}

			// Put the cursor at the end
			if ( this.prompt.setSelectionRange )
			{
				this.prompt.setSelectionRange ( this.prompt.value.length, this.prompt.value.length );
			}

			this.prompt.focus();
		} else if ( key == 9 ) {
			this.prompt.focus();

			if ( this.prompt.value.match ( /\s(.*)$/ ))
			{
				var arg = RegExp.$1;
				var path = arg;
				var dirs = new Array();

				for ( var i in this.files )
				{
					if ( arg.match ( /^[^\/]/ ) )
					{
						path = this.path + '/' + arg;
						path = path.replace ( /\/+/g, '/' );
					}

					var re = new RegExp ( '^' + path + '[^/]*$' );

					if ( this.files[i].path.match ( re ))
					{
						dirs.push ({
							'name' : this.files[i].path,
							'type' : this.files[i].type,
						});
					}
				}

				if ( dirs.length == 1 ) {
					this.prompt.value = this.prompt.value.replace ( arg, dirs[0].name + (( dirs[0].type == 'directory' ) ? '/' : '' ));
				} else {
					this.cmdOut.innerHTML = '';

					for ( var i in dirs )
					{
						if ( i > 0 )
						{
							this.cmdOut.innerHTML += "<br/>\n";
						}

						this.cmdOut.innerHTML += dirs[i].name;
					}

					if ( dirs.length > 1 )
					{
						// Get the longest sequence in common
						var sequences = new Array();
						var min_len = 0;

						for ( var i in dirs )
						{
							for ( var j in dirs )
							{
								if ( i != j )
								{
									if ( dirs[i].name.length != dirs[j].name.length )
									{
										min_len = ( dirs[i].name.length < dirs[j].name.length ) ? dirs[i].name.length : dirs[j].name.length;
									} else {
										min_len = dirs[i].name.length;
									}

									var k = 0;

									for ( k = min_len-1; k >= 0; k-- )
									{
										if ( dirs[i].name.charAt ( k ) != dirs[j].name.charAt ( k ))
										{
											break;
										}
									}

									var seq = '';

									for ( var l=0; l < k; l++ )
									{
										seq += dirs[i].name.charAt ( l );
									}

									sequences.push ( seq );
								}
							}
						}

						var seq = sequences[0];

						for ( var i in sequences )
						{
							if ( sequences[i].length < seq )
							{
								seq = sequences[i];
							}
						}

						this.prompt.value = this.prompt.value.replace ( arg, seq + (( dirs[0].type == 'directory' ) ? '/' : '' ));
					}
				}
			} else {
				var cmds = new Array();

				for ( var i in this.commands )
				{
					var re = new RegExp ( '^' + this.prompt.value );

					if ( this.commands[i].name.match ( re ))
					{
						cmds.push ( this.commands[i].name );
					}
				}

				if ( cmds.length == 0 )
				{
					this.cmdOut.innerHTML = '<br/>Sorry, no matches for `' + this.prompt.value + "'";
				} else if ( cmds.length == 1 ) {
					this.prompt.value = cmds[0] + ' ';
				} else {
					this.cmdOut.innerHTML = '';

					for ( var i in cmds )
					{
						this.cmdOut.innerHTML += "<br/>\n" + cmds[i];
					}
				}
			}

			if ( this.auto_prompt_focus )
			{
				this.prompt.focus();
				setTimeout ( function()  { shell.prompt.focus(); }, 1 );
			
				if ( this.prompt.setSelectionRange )
				{
					this.prompt.setSelectionRange ( this.prompt.value.length, this.prompt.value.length );
				}
			}

			return false;
		}

		if ( this.auto_prompt_focus )
		{
			this.prompt.focus();
		}
	}

	this.unescapePrompt = function ( prompt, sequences )
	{
		var re = new RegExp ( "([^\]?)#\{([0-9]+)\}" );

		while ( prompt.match ( re ))
		{
			if ( this.__open_spans > 0 )
			{
				prompt = prompt.replace ( re, RegExp.$1 + "</span><span style=\"color: #" + RegExp.$2 + "\">" );
			} else {
				prompt = prompt.replace ( re, RegExp.$1 + "<span style=\"color: #" + RegExp.$2 + "\">" );
				this.__open_spans++;
			}
		}

		if ( this.__open_spans > 0 )
		{
			prompt = prompt + "</span>";
		}

		for ( i=0; i < sequences.length; i++ )
		{
			prompt = this.unescapePromptSequence ( prompt, sequences[i].sequence, sequences[i].text(), sequences[i].default_text );
		}

		return prompt;
	}

	/**
	 * \brief Refresh the shell prompt
	 * \param clearTerm Set this variable as true if you want also to clear the terminal screen
	 * \param clearOut Set this variable as true if you want to clear the latest output command
	 */
	this.refreshPrompt = function ( clearTerm, clearOut )
	{
		var value = this.prompt.value;
		var out = this.cmdOut.innerHTML;
		var text = ( this.json.promptText ) ? this.json.promptText : "[%n@%m %W] $ ";
		text = this.unescapePrompt ( text, this.json.promptSequences );

		this.window.removeChild ( this.prompt );
		this.window.removeChild ( this.cmdOut );

		if ( clearTerm )
		{
			this.window.innerHTML = '';
		}
		
		if ( !clearOut )
		{
			var outDiv = document.createElement ( 'span' );
			outDiv.innerHTML = ((value.length > 0) ? value : '') +
				'<br/>' + ((out.length > 0) ? (out + '<br/>') : '') + text;
			this.window.appendChild ( outDiv );
		}

		this.prompt = document.createElement ( 'input' );
		this.prompt.setAttribute ( 'name', 'blashPrompt' );
		this.prompt.setAttribute ( 'type', 'text' );
		this.prompt.setAttribute ( 'class', 'promptInput' );
		this.prompt.setAttribute ( 'autocomplete', 'off' );
		this.prompt.setAttribute ( 'onkeydown', 'shell.getKey ( event )' );
		this.prompt.setAttribute ( 'onkeyup', 'this.focus()' );
		this.prompt.setAttribute ( 'onblur', 'return false' );

		this.cmdOut = document.createElement ( 'div' );
		this.cmdOut.setAttribute ( 'id', 'blashCmdOut' );
		this.cmdOut.setAttribute ( 'class', 'blashCmdOut' );

		this.window.appendChild ( this.prompt );
		this.window.appendChild ( this.cmdOut );
		this.prompt.focus();
	}

	this.unescapePromptSequence = function ( prompt, sequence, text, default_text )
	{
		var re = new RegExp ( "([^\]?)" + sequence, "g" );
		prompt.replace ( /%W/g, this.path );

		if ( prompt.match ( re ))
		{
			prompt = prompt.replace ( re, (( text ) ? RegExp.$1 + text : RegExp.$1 + default_text ));
		}

		return prompt;
	}

	/**
	 * \brief Expand an argument as path, transforming it into an absolute path, removing extra slashes and expanding '..' notations
	 */
	this.expandPath = function ( arg )
	{
		if ( !arg || arg.length == 0 )
		{
			return false;
		}

		while ( arg.match ( /(^|\/)\.\// ))
		{
			arg = arg.replace ( /(^|\/)\.\//, '/' );
		}

		if ( arg.match ( /^[^\/]/ ))
		{
			arg = this.path + '/' + arg;
		}

		arg = arg.replace ( /\/+/, '/' );

		if ( arg != '/' )
		{
			arg = arg.replace ( /\/*$/, '' );
		}

		while ( arg.match ( /^(.+?\/?\.\.)/ ))
		{
			var part = RegExp.$1;

			if ( arg.match ( /^(.+?)\/?\.\./ ))
			{
				if ( RegExp.$1 == '/' )
				{
					arg = arg.replace ( part, '/' );
				} else {
					part.match ( /^(.*)\/[^\/]*\/\.\..*$/ );
					var sup = RegExp.$1;
					arg = arg.replace ( part, sup );

					if ( arg.length == 0 )
					{
						arg = '/';
					}
				}
			}
		}

		return arg;
	}

	/**
	 * \brief Expand the star '*' notations inside of a path
	 */
	this.expandStar = function ( arg )
	{
		arg = arg.replace ( /([^\\])?\*/g, '$1.*' );

		var matches = new Array();
		var re = new RegExp ( arg );

		for ( var i=0; i < this.files.length; i++ )
		{
			if ( this.files[i].path.match ( re ))
			{
				matches.push ( this.files[i] );
			}
		}

		return matches;
	}
}

