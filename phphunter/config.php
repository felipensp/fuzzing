<?php

error_reporting(E_ALL);

ini_set('memory_limit', -1);
set_time_limit(0);

// Configuration
$config = array(
	// Set to false if wan't to log it
	'stdout' => '/tmp/fuzzer-stdout', 
	// File where will go the crash and memleak reports
	'stderr' => '/tmp/fuzzer-stderr',
	// Current PHP executable
	'php'    => realpath($_SERVER['_']), 
	// Arguments to PHP executable (e.g. -dextension=...)
	'args'   => ''
);
