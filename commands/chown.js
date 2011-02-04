{
	"name" : "chown",

	"info" :  {
		"syntax" : "chown &lt;new_user&gt; &lt;file|directory&gt;",
		"brief" : "Change the access permissions to a file or directory for one or more users or groups, example: \"chmod user1,user2,@group1,@group2+r /path\", \"chmod @all+rw /path\""
	},

	"action" : function ( arg )
	{
		var out = '';

		if ( !arg.match ( /^\s*([^+|-]*)(\+|\-)((r|w)+)\s+(.+)\s*$/ ))
		{
			return "Usage: " + this.info.syntax + "<br/>\n";
		}
