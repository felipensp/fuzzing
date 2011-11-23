<?php

ini_set('memory_limit', -1);
set_time_limit(0);

// Configuration
$config = array(
	'stdout' => '/tmp/fuzzer-stdout',   // Set to false if wan't to log it
	'stderr' => '/tmp/fuzzer-stderr',
	'php'    => realpath($_SERVER['_']) // Current PHP executable
);
