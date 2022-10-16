{
	"name" : "eval",

	"info" : {
		"syntax" : "eval &lt;javascript expression&gt;",
		"brief" : "Executes a certain JavaScript expression",
	},

	"action" : function ( arg )
	{
		var out = '';

		if ( !arg || arg.length == 0 )
		{
			return "Argument required<br/>\n";
		}

		out = eval ( arg );

		if ( out )
			return out;
		else
			return '';
	},
}

