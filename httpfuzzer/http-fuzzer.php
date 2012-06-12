<?php

$protocols = array(
	'GET',/*
	'POST',
	'HEAD',
	'DELETE',
	'0',*/
);

$versions = array(
	'1.0',/*
	'1.1',
	chr(0),
	'/',
	'2.0',*/
);

$uris = array(/*
	'/',
	chr(0),
	'//',
	'\\'.chr(0).'/',
	'..',*/
	'/bug.php'
);

$headers = array(/*
	'Host',
	'Content-length',
	'Content-type',
	'User-agent',
	'Referer',*/
	'HTTP_X_TEST'
);

$values = array(/*
	'www.google.com',
	chr(0),
	PHP_INT_MAX,
	~PHP_INT_MAX,
	'foo'. chr(0),
	"\xe0\x81"*/
	str_repeat('A', 8000)
);

foreach ($protocols as $protocol) {
	printf("- Protocol: %s\n", $protocol);
	
	foreach ($versions as $version) {
		foreach ($uris as $uri) {	
			foreach ($headers as $header) {
				foreach ($values as $value) {
					printf("Testing '%s'\n", $header);
					printf("-------------------------------------------------------\n");
					
					$fp = fsockopen('localhost', 80, $errno, $errstr, 30);
					if (!$fp) {
						echo "$errstr ($errno)\n";
						exit;
					}

					$out = $protocol .' '. $uri .' HTTP/'. $version ."\r\n";
			

					$out .= $header .': '. $value . "\r\n";

					$out .= "Connection: Close\r\n\r\n";
					
					echo $out, "\n";

					fwrite($fp, $out);
					while (!feof($fp)) {
						echo fgets($fp, 128);
					}
					fclose($fp);
					printf("-------------------------------------------------------\n");
				}
			}
		}
	}
}

