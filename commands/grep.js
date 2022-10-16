{
	"name" : "grep",

	"info" :  {
		"syntax" : "grep [-i] &lt;text|regex&gt; &lt;file(s)&gt;",
		"brief" : "Search for a text or a regex inside of one or more files. If one of the target is a directory, it automatically performs a recursive search in it. Use <i>-i</i> option in order to perform case insensitive search",
	},

	"action" : function ( arg )
	{
		var case_insensitive = false;

		if ( !arg || arg.length == 0 )
		{
			return "Usage: " + this.info.syntax + "<br/>\n";
		}

		arg = arg.replace ( /^\s*/, '' );
		arg = arg.replace ( /\s*$/, '' );

		var re = new RegExp ( "^([^'|\"]*\s*)-i(\s*)" );

		if ( arg.match ( re ))
		{
			case_insensitive = true;
			arg = arg.replace ( re, '$1$2' );
		}

		if ( arg.length == 0 || !arg.match ( /[^\s]+\s+[^\s]+/ ))
		{
			return "Usage: " + this.info.syntax + "<br/>\n";
		}

		arg = arg.replace ( /^\s*/, '' );
		arg = arg.replace ( /\s*$/, '' );

		var text = null;
		var files = null;

		if ( arg.match ( /^'([^']+)'\s+(.*)$/ ) || arg.match ( /^"([^']+)"\s+(.*)$/ ))
		{
			text = RegExp.$1;
			files = RegExp.$2;
		} else if ( arg.match ( /^([^\s]+)\s+(.*)$/ )) {
			text = RegExp.$1;
			files = RegExp.$2;
		}

		files = files.split ( /\s+/ );
		out = '';

		for ( var i=0; i < files.length; i++ )
		{
			if ( files[i].path )
				files[i] = shell.expandPath ( files[i].path );
			else
				files[i] = shell.expandPath ( files[i] );

			if ( files[i] )
			{
				if ( files[i].match ( /([^\\])?\*/ ))
				{
					matches = shell.expandStar ( files[i] );

					if ( matches.length > 1 ) {
						for ( var j in matches )
						{
							if ( matches[j].type == 'file' && matches[j].content )
							{
								files.push ( matches[j] );
							}
						}

						files.splice ( i--, 1 );
					}
				} else {
					var found = false;

					for ( var j in shell.files )
					{
						if ( shell.files[j].path == files[i] )
						{
							found = true;

							if ( shell.files[j].type == 'file' )
							{
								files[i] = shell.files[j];
							} else if ( shell.files[j].type == 'directory' ) {
								re = new RegExp ( shell.files[j].path + '/[^/]+$' );

								for ( var k in shell.files )
								{
									if ( shell.files[k].path.match ( re ))
									{
										files.push ( shell.files[k] );
									}
								}

								files.splice ( i--, 1 );
							}

							break;
						}
					}

					if ( !found )
					{
						files.splice ( i--, 1 );
					}
				}
			}
		}

		if ( files.length == 0 )
		{
			out = "grep: Cannot open provided file(s): No such file or directory<br/>\n";
		}

		for ( var i in files )
		{
			if ( files[i].type == 'file' && files[i].content.length > 0 )
			{
				var lines = files[i].content.split ( /\n|\r/ );

				for ( var j in lines )
				{
					if ( case_insensitive )
					{
						re = new RegExp ( '(' + text + ')', 'gi' );
					} else {
						re = new RegExp ( '(' + text + ')', 'g' );
					}

					if ( lines[j].match ( re ))
					{
						var tmp = parseInt(j) + 1;
						lines[j] = lines[j].replace ( re, '<span class="match">$1</span>' );

						out += '<span class="filematch">' + files[i].path + '</span>:' +
							'<span class="linematch">' + tmp + '</span>: ' +
							 lines[j] + "<br/>\n";
					}
				}
			}
		}

		return out;
	}
}

