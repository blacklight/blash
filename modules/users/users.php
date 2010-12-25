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
			print "The provided password is not a valid hash\n";
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

	case 'login':
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

		if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
		{
			print "Unable to open the users XML file\n";
			return 1;
		}

		for ( $i = 0; $i < count ( $xml->user ) && !$found; $i++ )
		{
			if ( !strcasecmp ( $xml->user[$i]['name'], $username ))
			{
				if ( strcasecmp ( $xml->user[$i]['pass'], $password ))
				{
					print "Wrong password provided for user '$username'\n";
					return 1;
				} else {
					$auth = md5 ( $xml->user[$i]['name'] . $xml->user[$i]['pass'] );
					setcookie ( 'username', $xml->user[$i]['name'], 0, "/" );
					setcookie ( 'auth', $auth, 0, "/" );

					print "Successfully logged in as '$username' $auth\n";
					return 0;
				}
			}
		}

		print "Username not found: '$username'\n";
		break;

	case 'getuser':
		if ( isset ( $_COOKIE['username'] ) && isset ( $_COOKIE['auth'] ))
		{
			if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
			{
				print "Unable to open the users XML file\n";
				return 1;
			}

			for ( $i = 0; $i < count ( $xml->user ) && !$found; $i++ )
			{
				if ( !strcasecmp ( $xml->user[$i]['name'], $_COOKIE['username'] ))
				{
					$auth = md5 ( $xml->user[$i]['name'] . $xml->user[$i]['pass'] );

					if ( !strcasecmp ( $auth, $_COOKIE['auth'] ))
					{
						print $xml->user[$i]['name'];
						return 0;
					} else {
						print "guest";
						return 1;
					}
				}
			}

			print "guest";
			return 1;
		}

		print "guest";
		return 1;
		break;
}

?>

