<?php

include 'userlist.php';
include 'user_utils.php';

global $sudo_cmd;

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
			return '';
		}

		if ( preg_match ( '/[^a-fA-F0-9]/', $password ) || strlen ( $password ) != 32 )
		{
			print "The provided password is not a valid hash\n";
			return '';
		}

		if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
		{
			print "Unable to open the users XML file\n";
			return '';
		}

		for ( $i = 0; $i < count ( $xml->user ); $i++ )
		{
			if ( !strcasecmp ( $xml->user[$i]['name'], $username ))
			{
				print "The specified user already exists\n";
				return '';
			}
		}

		$newuser = $xml->addChild ( 'user' );
		$newuser->addAttribute ( 'name', $username );
		$newuser->addAttribute ( 'pass', $password );
		$newuser->addAttribute ( 'home', '/home/' . $username );

		if ( !( $fp = fopen ( 'userlist.php', 'w' )))
		{
			print "Unable to add the specified user, unknown error\n";
			return '';
		}

		fwrite ( $fp, '<?php'."\n\n".'$xmlcontent = <<<XML'."\n" . $xml->asXML() . "\nXML;\n\n?>\n" );
		fclose ( $fp );

		$perms = array();
		$perms['owner'] = $username;
		$perms['can_read'] = $username;
		$perms['can_write'] = $username;

		$GLOBALS['sudo_cmd'] = true;

		print __mkdir ( '/home/'.$username, $perms )."<br/>\n";
		set_content ( '/home/'.$username.'/.blashrc', file_get_contents ( '../../system/default_blashrc.json' ));
		set_content ( '/home/'.$username.'/.stylerc', file_get_contents ( '../../system/default_stylerc.css' ));

		include "../../system/files_json.php";

		if ( !$files_json || strlen ( $files_json ) == 0 )
		{
			return 'Error: Empty JSON file container';
		}

		$json = json_decode ( $files_json, true );

		if ( !$json )
		{
			return 'Error: Empty JSON file container';
		}

		for ( $i=0; $i < count ( $json ); $i++ )
		{
			if ( $json[$i]['path'] == '/home/'.$username.'/.blashrc'
				|| $json[$i]['path'] == '/home/'.$username.'/.stylerc' )
			{
				$json[$i]['can_read'] = $username;
				$json[$i]['can_write'] = $username;

				if ( !( $fp = fopen ( "../../system/files_json.php", "w" )))
				{
					return "Unable to write on directories file\n";
				}

				fwrite ( $fp, "<?php\n\n\$files_json = <<<JSON\n".__json_encode ( $json )."\nJSON;\n\n?>");
				fclose ( $fp );
			}
		}

		$GLOBALS['sudo_cmd'] = false;

		print 'User "'.$username.'" successfully added, home directory set to "/home/'.$username."\"\n";
		break;

	case 'del':
		$user= $_REQUEST['user'];

		if ( $user == null )
		{
			return false;
		}

		$cur_user = getUser();

		if ( $cur_user != 'root' && $cur_user != $user && !$GLOBALS['sudo_cmd'] )
		{
			print "You cannot remove the specified user: Permission denied\n";
			return false;
		}

		if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
		{
			print "Unable to open the users XML file\n";
			return false;
		}

		$user_found = false;

		for ( $i = 0; $i < count ( $xml->user ) && !$user_found; $i++ )
		{
			if ( !strcmp ( $xml->user[$i]['name'], $user ))
			{
				unset ( $xml->user[$i] );
				$user_found = true;
			}
		}

		if ( !$user_found )
		{
			print "Username not found\n";
			return false;
		}

		if ( !( $fp = fopen ( 'userlist.php', 'w' )))
		{
			print "Unable to add the specified user, unknown error\n";
			return false;
		}

		fwrite ( $fp, '<?php'."\n\n".'$xmlcontent = <<<XML'."\n" . $xml->asXML() . "\nXML;\n\n?>\n" );
		fclose ( $fp );
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
			return '';
		}

		if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
		{
			print "Unable to open the users XML file\n";
			return '';
		}

		for ( $i = 0; $i < count ( $xml->user ) && !$found; $i++ )
		{
			if ( !strcasecmp ( $xml->user[$i]['name'], $username ))
			{
				if ( strcasecmp ( $xml->user[$i]['pass'], $password ))
				{
					print "Wrong password provided for user '$username'\n";
					return '';
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
		break;

	case 'getuser':
		print getUser();
		break;

	case 'gethome':
		print getHome();
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
			return '';
		}

		if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
		{
			print "Unable to open the users XML file\n";
			return '';
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
						return '';
					}
				}

				$xml->user[$i]['pass'] = $new_pass;

				if ( !( $fp = fopen ( 'userlist.php', 'w' )))
				{
					print "Unable to change the password for the specified user, unknown error\n";
					return '';
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

	case 'mkdir':
		$dir = $_REQUEST['dir'];

		if ( !$dir )
		{
			return false;
		}

		print __mkdir ( $dir, null );
		break;

	case 'rmdir':
		$dir = $_REQUEST['dir'];

		if ( !$dir )
		{
			return false;
		}

		print __rmdir ( $dir );
		break;

	case 'touch':
		$file = $_REQUEST['file'];

		if ( !$file )
		{
			return false;
		}

		print __touch ( $file, null );
		break;

	case 'rm':
		$file = $_REQUEST['file'];

		if ( !$file )
		{
			return false;
		}

		print __rm ( $file );
		break;

	case 'chmod':
		$resource = $_REQUEST['resource'];
		$perms = $_REQUEST['perms'];
		$userlist = $_REQUEST['userlist'];

		if ( !( $resource && $perms ))
		{
			return false;
		}

		print __chmod ( $resource, (( $userlist ) ? $userlist : null ), $perms );
		break;

	case 'set_content':
		$file = $_REQUEST['file'];
		$content = $_REQUEST['content'];

		if ( !( $file && $content ))
		{
			return false;
		}

		print set_content ( $file, $content );
		break;

	case 'list':
		if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
		{
			print "Unable to open the users XML file\n";
			return false;
		}

		$users = array();

		for ( $i = 0; $i < count ( $xml->user ); $i++ )
		{
			array_push ( $users, $xml->user[$i]['name'] );
		}

		sort ( $users, SORT_STRING );

		foreach ( $users as $i => $user )
		{
			print $user;

			if ( $i < count ( $users ) - 1 )
			{
				print "<br/>";
			}

			print "\n";
		}

		break;

	case 'link':
		$resource = $_REQUEST['resource'];
		$link = $_REQUEST['link'];
		$type = $_REQUEST['type'];

		if ( !( $resource && $link && $type ))
		{
			return false;
		}

		print __link ( $resource, $link, $type );
		break;

	default :
		print "Unallowed action\n";
		break;
}

return "";

?>
