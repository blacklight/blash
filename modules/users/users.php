<?php

include 'userlist.php';
$action = $_REQUEST['action'];

if ( $action == null )
{
	die ("");
}

switch ( $action )
{
	case 'add':
		$username = $_REQUEST['user'];
		$password = $_REQUEST['pass'];

		if ( !( $username != null && $password != null ))
		{
			die ("");
		}

		if ( preg_match ( '/[^a-zA-Z0-9_]/', $username ))
		{
			print "The username can only contain characters in the charset '[a-zA-Z0-9_]'\n";
			return 1;
		}

		if ( preg_match ( '/[^a-zA-Z0-9]/', $password ) || strlen ( $password ) != 32 )
		{
			print "The provided password '$password' is not a valid hash\n";
			return 1;
		}

		if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
		{
			print "Unable to open the users XML file\n";
			return 1;
		}

		for ( $i = 0; $i < count ( $xml->user ); $i++ )
		{
			if ( !strcasecmp ( $xml->user[$i]['name'], $username ))
			{
				print "The specified user already exists\n";
				return 1;
			}
		}

		$newuser = $xml->addChild ( 'user' );
		$newuser->addAttribute ( 'name', $username );
		$newuser->addAttribute ( 'pass', $password );
		$newuser->addAttribute ( 'home', '/home/' . $username );

		if ( !( $fp = fopen ( 'userlist.php', 'w' )))
		{
			print "Unable to add the specified user, unknown error\n";
			return 1;
		}

		fwrite ( $fp, "<?php\n\n\$xmlcontent = <<<XML\n" . $xml->asXML() . "\nXML;\n\n?>\n" );
		fclose ( $fp );

		print 'User "'.$username.' successfully added, home directory set to "/home/'.$username."\"\n";
		break;
}

?>

