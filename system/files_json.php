<?php

$files_json = <<<JSON
[
	{
		"path" : "/",
		"type" : "directory",
		"can_read" : "@all",
		"can_write" : "root"
	},
	{
		"path" : "/blog",
		"type" : "directory"
	},
	{
		"path" : "/news",
		"type" : "directory"
	},
	{
		"path" : "/forum",
		"type" : "directory"
	},
	{
		"path" : "/tutorials",
		"type" : "directory"
	},
	{
		"path" : "/software",
		"type" : "directory"
	},
	{
		"path" : "/etc",
		"type" : "directory"
	},
	{
		"path" : "/home",
		"type" : "directory"
	},
	{
		"path" : "/home/guest",
		"type" : "directory"
	},
	{
		"path" : "/home/guest/mbox",
		"type" : "file",
		"content" : "No new mail"
	},
	{
		"path" : "/home/blacklight",
		"type" : "directory",
		"can_read" : "blacklight",
		"can_write" : "blacklight"
	},
	{
		"path" : "/home/blacklight/mbox",
		"type" : "file",
		"content" : "No new mail"
	},
	{
		"path" : "/google",
		"type" : "file",
		"href" : "http://www.google.com"
	},
	{
		"path" : "/blog/post1",
		"type" : "file",
		"content" : "This is my first post"
	},
	{
		"path" : "/blog/post2",
		"type" : "file",
		"content" : "This is my second post"
	},
	{
		"path" : "/blog/post3",
		"type" : "file",
		"content" : "This is my third post"
	},
	{
		"path" : "/etc/blashrc",
		"type" : "file",
		"content" : "This is the default blash configuration file"
	},
	{
		"path" : "/forum/post1",
		"type" : "file",
		"content" : "lol"
	},
	{
		"path" : "/forum/post2",
		"type" : "file",
		"content" : "lol"
	},
	{
		"path" : "/home/guest/.blashrc",
		"type" : "file",
		"content" : "Custom blash configuration file"
	},
	{
		"path" : "/news/news1",
		"type" : "file",
		"content" : "Nothing new under the sun"
	},
	{
		"path" : "/software/soft1",
		"type" : "file",
		"href" : "/software/soft1.tar.gz"
	},
	{
		"path" : "/software/soft2",
		"type" : "file",
		"href" : "/software/soft2.tar.gz"
	},
	{
		"path" : "/software/soft3",
		"type" : "file",
		"href" : "/software/soft3.tar.gz"
	},
	{
		"path" : "/tutorials/tut1",
		"type" : "file",
		"href" : "/software/tut1.pdf"
	},
	{
		"path" : "/tutorials/tut2",
		"type" : "file",
		"href" : "/software/tut2.pdf"
	},
	{
		"path" : "/github",
		"type" : "file",
		"href" : "https://github.com/BlackLight/blash"
	},
	{
		"path" : "/aboutme",
		"type" : "file",
		"content" : "Luke, I am your father"
	},
	{
		"path" : "/contacts",
		"type" : "file",
		"content" : "Contact me at spam@montypython.com"
	},
	{
		"path" : "/irc",
		"type" : "file",
		"content" : "IRC channel at #thegame@irc.randomstuff.com"
	},
	{
		"path" : "/root",
		"type" : "directory",
		"can_read" : "root",
		"can_write" : "root"
	}
]
JSON;

?>
