{
	"name" : "echo",

	"info" : {
		"syntax" : "echo [text]",
		"brief" : "Display a line of text",
	},

	"action" : function ( arg )
	{
		var out = arg + "<br/>\n";
		out = out.replace ( /(^|[^\\])"/g, '$1' );
		out = out.replace ( /(^|[^\\])'/g, '$1' );
		out = out.replace ( /\\"/g, '"' );
		out = out.replace ( /\\'/g, "'" );
		return out;
	},
}

