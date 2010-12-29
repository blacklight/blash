<?php

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

	if ( $user == 'root' )
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

?>
