var shell = null;

function blash ()
{
	/************ ATTRIBUTES **************/
	/** Object containing the parsed JSON configuration object */
	this.json = {};

	/** Shell window object */
	this.window = document.getElementById ( "blashWindow" );

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
	/**************************************/

	this.prompt.focus();

	var json_config = window.location.href;
	json_config = json_config.replace ( /\/([a-zA-Z\.]+)$/, '/blash.json' );

	var http = new XMLHttpRequest();
	http.open ( "GET", json_config, true );

	http.onreadystatechange = function ()
	{
		if ( http.readyState == 4 && http.status == 200 )
		{
			shell.json = eval ( '(' + http.responseText + ')' );

			shell.promptText.innerHTML = ( shell.json.promptText ) ? shell.json.promptText : "[%n@%m %W] $ ";
			shell.promptText.innerHTML = shell.unescapePrompt ( promptText.innerHTML, shell.json.promptSequences );

			if ( shell.json.banner.length > 0 )
			{
				var banner = document.createElement ( "span" );
				banner.setAttribute ( "id", "banner" );
				banner.innerHTML = shell.json.banner;
				shell.window.insertBefore ( banner, shell.promptText );
			}
		}
	}

	http.send ( null );
	
	this.getKey = function ( e )
	{
		var evt = ( window.event ) ? window.event : e;
		var key = ( evt.charCode ) ? evt.charCode : evt.keyCode;

		if ( key == 13 || key == 10 )
		{
			if ( this.prompt.value.length != 0 )
			{
				this.prompt.value.match ( /^([^\s]+)\s*(.*)$/ );
				var cmd = RegExp.$1;
				var arg = RegExp.$2;
				var cmd_found = false;
				this.history.push ( this.prompt.value );
				this.history_index = -1;

				for ( i=0; i < this.json.commands.length && !cmd_found; i++ )
				{
					if ( this.json.commands[i].name == cmd )
					{
						cmd_found = true;
						var out = this.json.commands[i].action ( arg );

						if ( out.length > 0 )
						{
							this.cmdOut.innerHTML = out;
						}
					}
				}

				if ( !cmd_found )
				{
					this.cmdOut.innerHTML = this.json.shellName + ": command not found: " + cmd + '<br/>';
				}

				var value = this.prompt.value;
				var out = this.cmdOut.innerHTML;

				this.window.removeChild ( this.prompt );
				this.window.removeChild ( this.cmdOut );
				this.window.innerHTML += value + '<br/>' + out + this.promptText.innerHTML;

				this.prompt = document.createElement ( 'input' );
				this.prompt.setAttribute ( 'name', 'blashPrompt' );
				this.prompt.setAttribute ( 'type', 'text' );
				this.prompt.setAttribute ( 'class', 'promptInput' );
				this.prompt.setAttribute ( 'autocomplete', 'off' );
				this.prompt.setAttribute ( 'onkeyup', 'shell.getKey ( event )' );

				this.cmdOut = document.createElement ( 'div' );
				this.cmdOut.setAttribute ( 'id', 'blashCmdOut' );
				this.cmdOut.setAttribute ( 'class', 'blashCmdOut' );
				this.cmdOut.innerHTML = '<br/>';

				this.window.appendChild ( this.prompt );
				this.window.appendChild ( this.cmdOut );
				this.prompt.focus();
			}
		} else if ( key == 38 || key == 40 ) {
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

			this.prompt.focus();
		} else if ( key == 9 ) {
			if ( this.prompt.value.match ( /\s(.*)$/ ))
			{
				var arg = RegExp.$1;
				var path = arg;
				var dirs = new Array();

				for ( var i in this.json.directories )
				{
					if ( arg.match ( /^[^\/]/ ) )
					{
						path = this.path + '/' + arg;
						path = path.replace ( /\/+/g, '/' );
					}

					var re = new RegExp ( '^' + path + '[^/]*$' );

					if ( this.json.directories[i].path.match ( re ))
					{
						dirs.push ({
							'name' : this.json.directories[i].path,
							'type' : this.json.directories[i].type,
						});
					}
				}

				if ( dirs.length == 0 )
				{
					this.cmdOut.innerHTML = '<br/>Sorry, no matches for `' + this.prompt.value + "'";
				} else if ( dirs.length == 1 ) {
					this.prompt.value = this.prompt.value.replace ( arg, dirs[0].name + (( dirs[0].type == 'directory' ) ? '/' : '' ));
				} else {
					this.cmdOut.innerHTML = '';

					for ( var i in dirs )
					{
						this.cmdOut.innerHTML += "<br/>\n" + dirs[i].name;
					}
				}
			} else {
				var cmds = new Array();

				for ( var i in this.json.commands )
				{
					var re = new RegExp ( '^' + this.prompt.value );

					if ( this.json.commands[i].name.match ( re ))
					{
						cmds.push ( this.json.commands[i].name );
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
		}

		this.prompt.focus();
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

	this.unescapePromptSequence = function ( prompt, sequence, text, default_text )
	{
		var re = new RegExp ( "([^\]?)" + sequence, "g" );

		if ( prompt.match ( re ))
		{
			prompt = prompt.replace ( re, (( text ) ? RegExp.$1 + text : RegExp.$1 + default_text ));
		}

		return prompt;
	}
}

