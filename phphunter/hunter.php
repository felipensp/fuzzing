<?php

require __DIR__ .'/config.php';
require __DIR__ .'/class.bughunter.php';

$longops = array(
	// Test options
	'extension:',
	'class:',
	'function:',
	'method:',
	'class-only',
	'method-only',
	'function-only',
	// Output options
	'html:'
	);
$opts = getopt('c:e:f:hm:', $longops);

$html = NULL;

$hunter = new BugHunter($config);
$hunter->setFuzzer(new TemplateFuzzer);

foreach ($opts as $opt => $value) {
	switch ($opt) {
		case 'function-only':
			$hunter->setFlag(BugHunter::FUNCTION_ONLY);
			break;
		case 'method-only':
			$hunter->setFlag(BugHunter::METHOD_ONLY);
			break;
		case 'class-only':
			$hunter->setFlag(BugHunter::CLASS_ONLY);
			break;			
		case 'c':
		case 'class':
			$hunter->setClass($value);
			break;
		case 'e':
		case 'extension':
			$hunter->setExtension($value);
			break;
		case 'f':
		case 'function':
			$hunter->setFunction($value);
			break;
		case 'm':
		case 'method':
			$hunter->setMethod($value);
			break;
		case 'html':
			$html = $value;
			break;
		default:
			echo <<<USAGE
BugHunter - v0.1
  Tool for fuzzing PHP extensions

	-e, --extension   extension to be tested
	-c, --class       class to be tested
	-f, --function    function to be tested
	-m, --method      method to be tested
	--class-only      only test class when using -c or -e
	--method-only     only test methods when using -c or -e
	--function-only   only test functions when using -e
	
	--html=file       generate a HTML with stderr data
	
Contact: felipe@php.net

USAGE;
			exit;
	}
}

$hunter->run();
$hunter->showResult();

// Save stderr result as HTML
if ($html) {
	$hunter->saveHTML($html);
}
