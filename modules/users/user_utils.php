<?php

$sudo_cmd = false;

function getUser ()
{
	include 'userlist.php';

	if ( isset ( $_COOKIE['username'] ) && isset ( $_COOKIE['auth'] ))
	{
		if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
		{
			return "Unable to open the users XML file\n";
		}

		for ( $i = 0; $i < count ( $xml->user ); $i++ )
		{
			if ( !strcasecmp ( $xml->user[$i]['name'], $_COOKIE['username'] ))
			{
				$auth = md5 ( $xml->user[$i]['name'] . $xml->user[$i]['pass'] );

				if ( !strcasecmp ( $auth, $_COOKIE['auth'] ))
				{
					return $xml->user[$i]['name'];
				} else {
					return "guest";
				}
			}
		}

		return "guest";
	}

	return "guest";
}

function userExists ( $user )
{
	include 'userlist.php';

	if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
	{
		return "Unable to open the users XML file\n";
	}

	for ( $i = 0; $i < count ( $xml->user ); $i++ )
	{
		if ( !strcmp ( $xml->user[$i]['name'], $user ))
		{
			return true;
		}
	}

	return false;
}

function getHome ()
{
	include 'userlist.php';

	if ( isset ( $_COOKIE['username'] ) && isset ( $_COOKIE['auth'] ))
	{
		if ( !( $xml = new SimpleXMLElement ( $xmlcontent )))
		{
			return "Unable to open the users XML file\n";
		}

		for ( $i = 0; $i < count ( $xml->user ); $i++ )
		{
			if ( !strcasecmp ( $xml->user[$i]['name'], $_COOKIE['username'] ))
			{
				$auth = md5 ( $xml->user[$i]['name'] . $xml->user[$i]['pass'] );

				if ( !strcasecmp ( $auth, $_COOKIE['auth'] ))
				{
					return $xml->user[$i]['home'];
				} else {
					return '/';
				}
			}
		}

		return '/';
	}

	return '/';
}

function getPerms ( $resource )
{
	include "../../system/files_json.php";

	if ( !$files_json || strlen ( $files_json ) == 0 )
	{
		return '{ "message": "Empty JSON file container" }';
	}

	$user = getUser();
	$resource = str_replace ( '"', '\"', $resource );

	if ( $user == 'root' || $GLOBALS['sudo_cmd'] )
	{
		return '{ "resource" : "'.$resource.'", "read" : true, "write" : true }'."\n";
	}

	if ( preg_match ( '@/[^/]+/+$@', $resource ))
	{
		$resource = preg_replace ( '@/+$@', '', $resource );
	}

	$json = json_decode ( $files_json, true );
	$dir = $resource;
	$response = "{ \"resource\": \"$dir\"\n";

	$read_perm_found = false;   // Have we found information about the read permissions of this resource?
	$write_perm_found = false;  // Have we found information about the write permissions of this resource?
	$res_found = false;         // Have we found the resource?
	$can_read = false;
	$can_write = false;

	if ( !$json || count ( $json ) == 0 )
	{
		return '{ "message": "Empty JSON file" }';
	}

	do
	{
		for ( $i=0; $i < count ( $json ); $i++ )
		{
			if ( !strcmp ( $json[$i]['path'], $dir ))
			{
				$res_found = true;

				if ( !$read_perm_found )
				{
					if ( isset ( $json[$i]['can_read'] ))
					{
						$read_perm_found = true;
						$read = $json[$i]['can_read'];

						if ( preg_match ( '/[\s,]*'.$user.'[\s,]*/', $read ))
						{
							$response .= ", \"read\": true\n";
							$can_read = true;
						} else if ( preg_match_all ( "/[\s,]?@([^\s,]+)[\s,]?/", $read, $matches )) {
							for ( $j=1; $j < count ( $matches ); $j++ )
							{
								if ( !strcasecmp ( $matches[$j][0], "all" ))
								{
									$response .= ", \"read\": true\n";
									$can_read = true;
								} else if ( !strcasecmp ( $matches[$j], "registered" ) && $user != 'guest' ) {
									$response .= ", \"read\": true\n";
									$can_read = true;
								} else {
									if ( isset ( $json['groups'] ))
									{
										for ( $k=0; $k < count ( $json['groups'] ); $k++ )
										{
											if ( $json['groups'][$k]['name'] == $matches[$k] )
											{
												if ( isset ( $json['groups'][$k]['users'] ))
												{
													if ( preg_match ( '/[\s,]*'.$user.'[\s,]*/', $json['groups'][$k]['users'] ))
													{
														$can_read = true;
													}
												}

												break;
											}
										}
									}
								}
							}
						}

						if ( !$can_read )
						{
							$response .= ", \"read\": false\n";
						}
					}
				}

				if ( !$write_perm_found )
				{
					if ( isset ( $json[$i]['can_write'] ))
					{
						$write_perm_found = true;
						$write = $json[$i]['can_write'];

						if ( preg_match ( '/[\s,]*'.$user.'[\s,]*/', $write ))
						{
							$response .= ", \"write\": true\n";
							$can_write = true;
						} else if ( preg_match_all ( "/[\s,'\"]?@([^\s,'\"]+)[\s,'\"]/", $write, $matches )) {
							for ( $j=1; $j < count ( $matches ); $j++ )
							{
								if ( !strcasecmp ( $matches[$j], "all" ))
								{
									$response .= ", \"write\": true\n";
									$can_write = true;
								} else if ( !strcasecmp ( $matches[$j], "registered" ) && $user != 'guest' ) {
									$response .= ", \"write\": true\n";
									$can_write = true;
								} else {
									if ( isset ( $json['groups'] ))
									{
										for ( $k=0; $k < count ( $json['groups'] ); $k++ )
										{
											if ( $json['groups'][$k]['name'] == $matches[$k] )
											{
												if ( isset ( $json['groups'][$k]['users'] ))
												{
													if ( preg_match ( '/[\s,]*'.$user.'[\s,]*/', $json['groups'][$k]['users'] ))
													{
														$can_write = true;
													}
												}

												break;
											}
										}
									}
								}
							}
						}

						if ( !$can_write )
						{
							$response .= ", \"write\": false\n";
						}
					}
				}
			}
		}

		if ( !$res_found )
		{
			return '{ "message": "Resource not found" }';
		}

		if ( $read_perm_found && $write_perm_found )
		{
			break;
		}

		if ( preg_match ( '@/[^/]+/@', $dir ))
		{
			$dir = preg_replace ( '@/[^/]+$@', '', $dir );
		} else if ( preg_match ( '@^/[^/]+$@', $dir )) {
			$dir = '/';
		} else if ( $dir == '/' ) {
			$dir = '';
		}
	} while ( strlen ( $dir ) > 0 );

	$response .= "}\n";
	return $response;
}

function __link ( $resource, $link, $type )
{
	$ret =  __touch ( $link, null );

	if ( strlen ( $ret ) > 0 )
	{
		return $ret;
	}

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
		$path = $json[$i]['path'];

		if ( !$path || strlen ( $path ) == 0 )
		{
			continue;
		}

		if ( $path == $link )
		{
			unset ( $json[$i]['content'] );

			if ( $type == 'href' )
			{
				$json[$i]['href'] = $resource;
			} else if ( $type == 'local' ) {
				$json[$i]['link_to'] = $resource;
			} else {
				return "No link type specified (href|local)\n";
			}

			if ( !( $fp = fopen ( "../../system/files_json.php", "w" )))
			{
				return "Unable to write on directories file\n";
			}

			fwrite ( $fp, "<?php\n\n\$files_json = <<<JSON\n".__json_encode ( $json )."\nJSON;\n\n?>");
			fclose ( $fp );
			return false;
		}
	}

	return "Unable to link the resource";
}

function __json_encode( $data ) {           
	if ( is_array ($data) || is_object ($data) ) {
		$islist = is_array ($data) && ( empty ($data) || array_keys ($data) === range (0,count($data)-1) );

		if ( $islist ) {
			$json = '['."\n" . implode(', ', array_map('__json_encode', $data) ) . ']'."\n";
		} else {
			$items = Array();

			foreach ( $data as $key => $value ) {
				$items[] = __json_encode("$key") . ': ' . __json_encode($value);
			}

			$json = '{' . implode(', ', $items) . '}'."\n";
		}
	} elseif ( is_string ( $data )) {
		# Escape non-printable or Non-ASCII characters.
		# I also put the \\ character first, as suggested in comments on the 'addclashes' page.
		#$string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"';
		$string = '"' . $data . '"';
		$json    = '';
		$len    = strlen ($string);

		# Convert UTF-8 to Hexadecimal Codepoints.
		for ( $i = 0; $i < $len; $i++ ) {
			$char = $string[$i];
			$c1 = ord($char);

			# Single byte;
			if( $c1 <128 ) {
				$json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);
				continue;
			}

			# Double byte
			$c2 = ord($string[++$i]);
			if ( ($c1 & 32) === 0 ) {
				$json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);
				continue;
			}

			# Triple
			$c3 = ord($string[++$i]);
			if( ($c1 & 16) === 0 ) {
				$json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128));
				continue;
			}

			# Quadruple
			$c4 = ord($string[++$i]);
			if( ($c1 & 8 ) === 0 ) {
				$u = (($c1 & 15) << 2) + (($c2>>4) & 3) - 1;

				$w1 = (54<<10) + ($u<<6) + (($c2 & 15) << 2) + (($c3>>4) & 3);
				$w2 = (55<<10) + (($c3 & 15)<<6) + ($c4-128);
				$json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
			}
		}
	} else {
		# int, floats, bools, null
		$json = strtolower(var_export( $data, true ));
	}
	
	return $json;
} 

function __chmod ( $resource, $userlist, $perms )
{
	include "../../system/files_json.php";
	$user = getUser();
	$file_index = -1;

	if ( !$files_json || strlen ( $files_json ) == 0 )
	{
		return 'Error: Empty JSON file container';
	}

	$json = json_decode ( $files_json, true );

	if ( !$json )
	{
		return 'Error: Empty JSON file container';
	}

	for ( $i=0; $i < count ( $json ) && $file_index == -1; $i++ )
	{
		if ( $json[$i]['path'] == $resource )
		{
			$file_index = $i;
		}
	}

	if ( $file_index == -1 )
	{
		return "chmod: No such file or directory";
	}

	$perm = json_decode ( getPerms ( $json[$file_index]['path'] ), true );

	if ( !$perm['write'] )
	{
		return "chmod: Permission denied";
	}

	if ( $userlist )
	{
		$userlist = preg_split ( '/,\s*/', $userlist );
	} else {
		$userlist = array();
		$userlist[0] = $user;
	}

	$can = array();
	$perm = array();
	$perm['set'] = ( $perms & 0x4 ) ? true : false;
	$perm['read'] = ( $perms & 0x2 ) ? true : false;
	$perm['write'] = ( $perms & 0x1 ) ? true : false;

	foreach ( array ( 'read', 'write' ) as $action )
	{
		if ( $perm['set'] )
		{
			if ( $perm[$action] )
			{
				if ( !$json[$file_index]['can_'.$action] )
				{
					$json[$file_index]['can_'.$action] = join ( ", ", $userlist );
				} else {
					$out = '';
					$can[$action] = preg_split ( '/,\s*/', $json[$file_index]['can_'.$action] );

					for ( $i=0; $i < count ( $userlist ); $i++ )
					{
						if ( !userExists ( $userlist[$i] ) && !preg_match ( '/^\s*@/', $userlist[$i] ))
						{
							continue;
						}

						$user_found = false;

						for ( $j=0; $j < count ( $can[$action] ) && !$user_found; $j++ )
						{
							if ( $userlist[$i] == $can[$action][$j] )
							{
								$user_found = true;
							}
						}

						if ( !$user_found )
						{
							if ( strlen ( $out ) == 0 )
							{
								$out .= $userlist[$i];
							} else {
								$out .= ', '.$userlist[$i];
							}
						}
					}

					if ( strlen ( $out ) > 0 )
					{
						$json[$file_index]['can_'.$action] .= ', '.$out;
					}
				}
			}
		} else {
			if ( $perm[$action] )
			{
				if ( !$json[$file_index]['can_'.$action] )
				{
					continue;
				} else {
					for ( $i=0; $i < count ( $userlist ); $i++ )
					{
						if ( preg_match ( '/(,?\s*)'.$userlist[$i].'(,?)/', $json[$file_index]['can_'.$action], $matches ))
						{
							$replace = '';

							if ( preg_match ( '/^\s*$/', $matches[1] ) || preg_match ( '/^\s*$/', $matches[2] ))
							{
								$replace = '';
							} else {
								$replace = ", ";
							}

							$json[$file_index]['can_'.$action] = preg_replace ( '/(,?\s*)'.$userlist[$i].'(,?)/', $replace, $json[$file_index]['can_'.$action] );
						}
					}

					if ( strlen ( $json[$file_index]['can_'.$action] ) == 0 )
					{
						unset ( $json[$file_index]['can_'.$action] );
					}
				}
			}
		}
	}

	if ( !( $fp = fopen ( "../../system/files_json.php", "w" )))
	{
		return "Unable to write on directories file\n";
	}

	fwrite ( $fp, "<?php\n\n\$files_json = <<<JSON\n".__json_encode ( $json )."\nJSON;\n\n?>");
	fclose ( $fp );
}

function set_content ( $file, $content )
{
	$perms = json_decode ( getPerms ( $file ), true );
	$can_write = true;

	if ( !$perms['write'] )
	{
		$can_write = false;

		if ( $perms['message'] )
		{
			if ( !strcasecmp ( $perms['message'], "Resource not found" ))
			{
				$parent = preg_replace ( "@/[^/]+$@", '', $file );
				$perms = json_decode ( getPerms ( $parent ), true );

				if ( !$perms['write'] )
				{
					if ( $perms['message'] )
					{
						if ( !strcasecmp ( $perms['message'], "Resource not found" ))
						{
							return "Cannot save the file: Parent directory not found";
						} else {
							return $perms['message'];
						}
					} else {
						return "Cannot write to the file: Permission denied";
					}
				} else {
					$can_write = true;
				}
			} else {
				return $perms['message'];
			}
		} else {
			return "Cannot write to the file: Permission denied";
		}
	}

	$resp = __touch ( $file, null );
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
		$path = $json[$i]['path'];

		if ( !$path || strlen ( $path ) == 0 )
		{
			continue;
		}

		if ( $path == $file )
		{
			$content = str_replace ( '<', '&lt;', $content );
			$content = str_replace ( '>', '&gt;', $content );
			$content = str_replace ( "\'", "'", $content );
			$content = str_replace ( '"', "'", $content );
			$content = str_replace ( '\\', '', $content );
			$content = str_replace ( "\r", '', $content );
			$content = str_replace ( "\n", '<br/>', $content );

			$json[$i]['content'] = $content;

			if ( !( $fp = fopen ( "../../system/files_json.php", "w" )))
			{
				return "Unable to write on directories file\n";
			}

			fwrite ( $fp, "<?php\n\n\$files_json = <<<JSON\n".__json_encode ( $json )."\nJSON;\n\n?>");
			fclose ( $fp );
			return "File successfully saved";
		}
	}
}

function __touch ( $file, $own_perms )
{
	include "../../system/files_json.php";

	if ( !$files_json || strlen ( $files_json ) == 0 )
	{
		return 'touch: Error: Empty JSON file container';
	}

	if ( preg_match ( "@[^0-9a-zA-Z_\./\ ]@", $file ))
	{
		return "touch: Invalid character(s) for a file name out of range '[0-9a-zA-Z_./ ]'\n";
	}

	$has_perms = false;

	if ( $own_perms )
	{
		if ( is_array ( $own_perms ))
		{
			$has_perms = true;
		}
	}

	$user = getUser();
	$json = json_decode ( $files_json, true );
	$parent_dir = preg_replace ( '@/[^/]+$@', '', $file );
	$parent_dir_found = false;

	if ( preg_match ( "/^\s*$/", $parent_dir ))
	{
		$parent_dir = '/';
	}

	for ( $i=0; $i < count ( $json ); $i++ )
	{
		$path = $json[$i]['path'];

		if ( !$path || strlen ( $path ) == 0 )
		{
			continue;
		}

		if ( $path == $parent_dir )
		{
			$parent_dir_found = true;
			$perms = getPerms ( $parent_dir );
			$perms = json_decode ( $perms, true );

			if ( $perms['write'] == false )
			{
				$file = str_replace ( '<', '&lt;', $file );
				$file = str_replace ( '>', '&gt;', $file );
				return "touch: Could not touch $file: Permission denied\n";
			}
		}

		if ( $path == $file )
		{
			$file = str_replace ( '<', '&lt;', $file );
			$file = str_replace ( '>', '&gt;', $file );
			return "touch: Could not touch $file: The file already exists\n";
		}
	}

	if ( !$parent_dir_found )
	{
		$file = str_replace ( '<', '&lt;', $file );
		$file = str_replace ( '>', '&gt;', $file );
		return "touch: Could not touch $file: Parent directory not found\n";
	}

	$newfile = array();
	$newfile['path'] = "$file";
	$newfile['type'] = 'file';
	$newfile['owner'] = ($has_perms) ? $own_perms['owner'] : "$user";
	$newfile['can_read'] = ($has_perms) ? $own_perms['can_read'] : '@all';
	$newfile['can_write'] = ($has_perms) ? $own_perms['can_write'] : "$user";
	$newfile['content'] = "";

	array_push ( $json, $newfile );

	if ( !( $fp = fopen ( "../../system/files_json.php", "w" )))
	{
		return "touch: Unable to write on directories file\n";
	}

	fwrite ( $fp, "<?php\n\n\$files_json = <<<JSON\n".__json_encode ( $json )."\nJSON;\n\n?>");
	fclose ( $fp );
	return "";
}

function __rm ( $file )
{
	include "../../system/files_json.php";

	if ( !$files_json || strlen ( $files_json ) == 0 )
	{
		return 'rm: Error: Empty JSON file container';
	}

	$user = getUser();
	$json = json_decode ( $files_json, true );
	$file_found = false;

	for ( $i=0; $i < count ( $json ) && !$file_found; $i++ )
	{
		$path = $json[$i]['path'];

		if ( !$path || strlen ( $path ) == 0 )
		{
			continue;
		}

		if ( $path == $file )
		{
			if ( $json[$i]['type'] != 'file' )
			{
				$file = str_replace ( '<', '&lt;', $file );
				$file = str_replace ( '>', '&gt;', $file );
				return "rm: Could not remove file $file: It is not a regular file\n";
			} else {
				$file_found = true;
				$perms = getPerms ( $path );
				$perms = json_decode ( $perms, true );

				if ( $perms['write'] == false )
				{
					$path = str_replace ( '<', '&lt;', $path );
					$path = str_replace ( '>', '&gt;', $path );
					return "rm: Could not remove file $path: Permission denied\n";
				} else {
					array_splice ( $json, $i, 1 );
					$i--;
				}
			}
		}
	}

	if ( !$file_found )
	{
		$file = str_replace ( '<', '&lt;', $file );
		$file = str_replace ( '>', '&gt;', $file );
		return "rm: Could not remove $file: File not found\n";
	}

	if ( !( $fp = fopen ( "../../system/files_json.php", "w" )))
	{
		return "rm: Unable to write on directories file\n";
	}

	fwrite ( $fp, "<?php\n\n\$files_json = <<<JSON\n".__json_encode ( $json )."\nJSON;\n\n?>");
	fclose ( $fp );
	return "";
}

function __mkdir ( $dir, $own_perms )
{
	include "../../system/files_json.php";

	if ( !$files_json || strlen ( $files_json ) == 0 )
	{
		return 'mkdir: Error: Empty JSON file container';
	}

	if ( preg_match ( "@[^0-9a-zA-Z_\./\ ]@", $dir ))
	{
		return "mkdir: Invalid character(s) for a directory name out of range '[0-9a-zA-Z_./ ]'\n";
	}

	$has_perms = false;

	if ( $own_perms )
	{
		if ( is_array ( $own_perms ))
		{
			$has_perms = true;
		}
	}

	$user = getUser();
	$json = json_decode ( $files_json, true );
	$parent_dir = preg_replace ( '@/[^/]+$@', '', $dir );
	$parent_dir_found = false;

	if ( preg_match ( "/^\s*$/", $parent_dir ))
	{
		$parent_dir = '/';
	}

	for ( $i=0; $i < count ( $json ); $i++ )
	{
		$path = $json[$i]['path'];

		if ( !$path || strlen ( $path ) == 0 )
		{
			continue;
		}

		if ( $path == $parent_dir )
		{
			$parent_dir_found = true;
			$perms = getPerms ( $parent_dir );
			$perms = json_decode ( $perms, true );

			if ( $perms['write'] == false )
			{
				$dir = str_replace ( '<', '&lt;', $dir );
				$dir = str_replace ( '>', '&gt;', $dir );
				return "mkdir: Could not create directory $dir: Permission denied\n";
			}
		}

		if ( $path == $dir )
		{
			$dir = str_replace ( '<', '&lt;', $dir );
			$dir = str_replace ( '>', '&gt;', $dir );
			return "mkdir: Could not create directory $dir: The file already exists\n";
		}
	}

	if ( !$parent_dir_found )
	{
		$dir = str_replace ( '<', '&lt;', $dir );
		$dir = str_replace ( '>', '&gt;', $dir );
		return "mkdir: Could not create directory $dir: Parent directory not found\n";
	}

	$newdir = array();
	$newdir['path'] = "$dir";
	$newdir['type'] = 'directory';
	$newdir['owner'] = ($has_perms) ? $own_perms['owner'] : "$user";
	$newdir['can_read'] = ($has_perms) ? $own_perms['can_read'] : '@all';
	$newdir['can_write'] = ($has_perms) ? $own_perms['can_write'] : "$user";

	array_push ( $json, $newdir );

	if ( !( $fp = fopen ( "../../system/files_json.php", "w" )))
	{
		return "mkdir: Unable to write on directories file\n";
	}

	fwrite ( $fp, "<?php\n\n\$files_json = <<<JSON\n".__json_encode ( $json )."\nJSON;\n\n?>");
	fclose ( $fp );
	return "";
}

function __rmdir ( $dir )
{
	include "../../system/files_json.php";

	if ( !$files_json || strlen ( $files_json ) == 0 )
	{
		return 'rmdir: Error: Empty JSON file container';
	}

	$user = getUser();
	$json = json_decode ( $files_json, true );
	$dir_found = false;

	for ( $i=0; $i < count ( $json ); $i++ )
	{
		$path = $json[$i]['path'];

		if ( !$path || strlen ( $path ) == 0 )
		{
			continue;
		}

		if ( $path == $dir )
		{
			if ( $json[$i]['type'] != 'directory' )
			{
				$dir = str_replace ( '<', '&lt;', $dir );
				$dir = str_replace ( '>', '&gt;', $dir );
				return "rmdir: Could not remove directory $dir: It is not a directory\n";
			}
		}

		if ( preg_match ( "@^".$dir."(/+.*)?@", $path ))
		{
			$dir_found = true;
			$perms = getPerms ( $path );
			$perms = json_decode ( $perms, true );

			if ( $perms['write'] == false )
			{
				$path = str_replace ( '<', '&lt;', $path );
				$path = str_replace ( '>', '&gt;', $path );
				return "rmdir: Could not remove directory $path Permission denied\n";
			} else {
				array_splice ( $json, $i, 1 );
				$i--;
			}
		}
	}

	if ( !$dir_found )
	{
		$dir = str_replace ( '<', '&lt;', $dir );
		$dir = str_replace ( '>', '&gt;', $dir );
		return "rmdir: Could not remove directory $dir: File not found\n";
	}

	if ( !( $fp = fopen ( "../../system/files_json.php", "w" )))
	{
		return "rmdir: Unable to write on directories file\n";
	}

	fwrite ( $fp, "<?php\n\n\$files_json = <<<JSON\n".__json_encode ( $json )."\nJSON;\n\n?>");
	fclose ( $fp );
	return "";
}

?>
