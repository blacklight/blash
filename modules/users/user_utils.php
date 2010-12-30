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
		return 'mkdir: Error: Empty JSON file container';
	}

	$user = getUser();
	$json = json_decode ( $files_json, true );
	$dir_found = false;

	for ( $i=0; $i < count ( $json ) && !$dir_found; $i++ )
	{
		$path = $json[$i]['path'];

		if ( !$path || strlen ( $path ) == 0 )
		{
			continue;
		}

		if ( $path == $dir )
		{
			$dir_found = true;
			$perms = getPerms ( $dir );
			$perms = json_decode ( $perms, true );

			if ( $perms['write'] == false )
			{
				$dir = str_replace ( '<', '&lt;', $dir );
				$dir = str_replace ( '>', '&gt;', $dir );
				return "rmdir: Could not remove directory $dir: Permission denied\n";
			} else {
				array_splice ( $json, $i, 1 );
			}
		}
	}

	if ( !$dir_found )
	{
		$dir = str_replace ( '<', '&lt;', $dir );
		$dir = str_replace ( '>', '&gt;', $dir );
		return "mkdir: Could not remove directory $dir: File not found\n";
	}

	if ( !( $fp = fopen ( "../../system/files_json.php", "w" )))
	{
		return "mkdir: Unable to write on directories file\n";
	}

	fwrite ( $fp, "<?php\n\n\$files_json = <<<JSON\n".__json_encode ( $json )."\nJSON;\n\n?>");
	fclose ( $fp );
	return "";
}

?>
