<?php

include '../../system/files_json.php';
include 'user_utils.php';

if ( !$files_json || strlen ( $files_json ) == 0 )
{
	print "Empty JSON files content\n";
	return false;
}

$json = json_decode ( $files_json, true );

if ( !$json )
{
	print "Empty or invalid JSON files content\n";
	return false;
}

print "[\n";

for ( $i=0; $i < count ( $json ); $i++ )
{
	$can_read = false;
	$perms = getPerms ( $json[$i]['path'] );
	$perms = json_decode ( $perms, true );

	if ( $perms['read'] == true )
	{
		$keys = array_keys ( $json[$i] );

		print "{\n";

		foreach ( $keys as $k )
		{
			print '"'.$k.'": "'.$json[$i][$k].'",'."\n";
		}

		print "},\n\n";
	}
}

print "]\n";

?>
