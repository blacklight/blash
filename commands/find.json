{
	"name" : "find",

	"info" :  {
		"syntax" : "find &lt;text|regex&gt;",
		"brief" : "Search for file or directories in the root hierarchy",
	},

	"action" : function ( arg )
	{
		var out = '';

		if ( !arg || arg.length == 0 )
		{
			return "Argument required<br/>\n";
		}

		if ( arg.match ( "^['\"](.*)['\"]$" ))
		{
			arg = RegExp.$1;
		}

		var re = new RegExp ( arg, "i" );

		for ( var i in shell.files )
		{
			var dir = shell.files[i];

			if ( dir.path.match ( re ))
			{

				if ( dir.type == 'directory' )
				{
					out += '<span class="directory">' + dir.path + "</span><br/>\n";
				} else if ( dir.type == 'file' && dir.href ) {
					out += '<span class="file">' +
						'<a href="' + dir.href + '" target="_new">' +
						dir.path + "</a></span><br/>\n";
				} else {
					out += dir.path + "<br/>\n";
				}
			}
		}

		return out;
	},
}

