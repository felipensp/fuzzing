<?php

$protocols = array(
	'GET',
	'POST',
	'HEAD',
	'DELETE',
	'0',
	'%s%s%s%s',
	'PURGE',
	'POLL',
	'OPTIONS',
	'NOTIFY',
	'SEARCH',
	'SUBSCRIBE',
	'MOVE',
	'REPORT',
	'UNLOCK',
);

$versions = array(
	'1.0',
	'1.1',/*
	chr(0),
	'/',
	'2.0',
	'%s%s%s%s',*/
);

$uris = array(/*
	'/',
	chr(0),
	'//',
	'\\'.chr(0).'/',
	'..',
	'/bug.php',*/
	//'%s%s%s%s',
	'%x%x%x%x',/*
	'index.html?',
	'index.html?'. str_repeat('a[]=1', 20),
	'index.html?'. str_repeat('a[]='. str_repeat('A', 10000), 20),
	'http://localhost/',
	'http://localhost//////',*/
);

$headers = array(
	'Host',
	'Content-length',
	'Content-type',
	'User-agent',
	'Referer',
	'HTTP_X_TEST',
	'Location',
	'Range',
	'Set-Cookie',
	'Upgrade',
	'X-Real-IP',
	'%s%s%s%s',
);

$values = array(
	'www.google.com',
	//chr(0),
	PHP_INT_MAX,
	~PHP_INT_MAX,
	'foo'. chr(0),
	str_repeat("\xe0\x81", 1000),
	str_repeat('A', 80000),
	'%s%s%s%s',
);

function request($src) {
	$fdspec = array(
	   0 => array('pipe', 'r'),
	   1 => array('pipe', 'w'),
	   2 => array('pipe', 'w')
	);

	$cmd = $_SERVER['_'];
	$process = proc_open($cmd, $fdspec, $pipes, '/tmp');

	if (is_resource($process)) {
		fwrite($pipes[0], $src);
		fclose($pipes[0]);
		
		// stdout
		if ($err = stream_get_contents($pipes[1])) {
		//	var_dump($err);
		}
		
		// stderr
		if ($err = stream_get_contents($pipes[2])) {
		//	var_dump($err);
		}
		
		return proc_close($process);
	}
}

foreach ($protocols as $protocol) {
	printf("- Protocol: %s\n", $protocol);
	
	foreach ($versions as $version) {
		foreach ($uris as $uri) {	
			foreach ($headers as $header) {
				foreach ($values as $value) {
					printf("Testing '%s'\n", $header);
					printf("Value '%s'\n", substr($value, 0, 30));
					printf("URI '%s'\n", substr($uri, 0, 30));
					printf("Version: '%s'\n", $version);
					printf("Header '%s'\n", $header);
					
					$source = '';
					
					$source .= '<?php
					
					$fp = fsockopen(\'localhost\', 80, $errno, $errstr, 10);
					if (!$fp) {
						echo "$errstr ($errno)\n";
						exit;
					};' . PHP_EOL;

					$source .= '$out .= "'. $protocol . ' '.  $uri .' HTTP/'. $version .'\r\n";'. PHP_EOL;

					$source .= '$out .= "'. $header .': '. $value . '\r\n";'. PHP_EOL;

					$source .= '$out .= "Connection: Close\r\n\r\n";'. PHP_EOL;

					$source .= 'fwrite($fp, $out);
					while (!feof($fp)) {
						echo fgets($fp, 128);
					}
					fclose($fp);' . PHP_EOL;
					
					// $source .= 'var_dump($out);';
					
					if ($exit = request($source)) {
						echo "Exit: ", $exit, PHP_EOL;
						exit;
					}

					printf("-------------------------------------------------------\n");
				}
			}
		}
	}
}

