<?php

include 'userlist.php';
include 'user_utils.php';

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

		fwrite ( $fp, '<?php'."\n\n".'$xmlcontent = <<<XML'."\n" . $xml->asXML() . "\nXML;\n\n?>\n" );
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

					print "Successfully logged in as '$username'\n";
					return 0;
				}
			}
		}

		print "Username not found: '$username'\n";
		return 1;
		break;

	case 'getuser':
		print getUser();
		return 0;
		break;

	case 'logout':
		setcookie ( 'username', '', 0, "/" );
		setcookie ( 'auth', '', 0, "/" );
		break;

	case 'changepwd':
		$old_pass = $_REQUEST['oldpass'];
		$new_pass = $_REQUEST['newpass'];
		$user = $_REQUEST['user'];
		$cur_user = getUser();

		// If the current user is not root and he's trying to change someone else's password, STOP HIM!
		if ( $cur_user != 'root' && $cur_user != $user )
		{
			print "You cannot change the password for the user '$user'\n";
			return 1;
		}

		if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
		{
			print "Unable to open the users XML file\n";
			return 1;
		}

		for ( $i = 0; $i < count ( $xml->user ); $i++ )
		{
			// If we've found the user whose password should be changed...
			if ( !strcasecmp ( $xml->user[$i]['name'], $user ))
			{
				$found = true;

				// If the current user is not root, check his own inserted current password
				if ( $cur_user != 'root' )
				{
					if ( $xml->user[$i]['pass'] != $old_pass )
					{
						print "The provided current password is wrong\n";
						return 1;
					}
				}

				$xml->user[$i]['pass'] = $new_pass;

				if ( !( $fp = fopen ( 'userlist.php', 'w' )))
				{
					print "Unable to change the password for the specified user, unknown error\n";
					return 1;
				}

				fwrite ( $fp, "<?php\n\n\$xmlcontent = <<<XML\n" . $xml->asXML() . "\nXML;\n\n?>\n" );
				fclose ( $fp );

				print 'Password successfully changed for the user '.$user."\n";
				return 0;
			}
		}

		break;

	case 'getperms':
		$res = $_REQUEST['resource'];

		if ( !$res )
		{
			return false;
		}

		print getPerms ( $res );
		break;
}

?>

