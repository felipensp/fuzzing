<?php

require __DIR__ .'/config.php';
require __DIR__ .'/class.bughunter.php';

$longops = array('extension:', 'class:', 'function:', 'class-only', 'method-only', 'function-only');
$opts = getopt('c:e:f:h', $longops);

$hunter = new BugHunter($config);
$hunter->setFuzzer(new TemplateFuzzer($config));

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
		default:
			echo <<<USAGE
BugHunter - v0.1
  Tool for fuzzing PHP extensions

	-e, --extension   extension to be tested
	-c, --class       class to be tested
	-f, --function    function to be tested
	--class-only      only test class when using -c or -e
	--method-only     only test methods when using -c or -e
	--function-only   only test functions when using -e
	
Contact: felipe@php.net

USAGE;
			exit;
	}
}

$hunter->run();
$hunter->showResult();
