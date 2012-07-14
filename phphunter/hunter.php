<?php

/*
 * PHPHunter
 * 
 * Author: Felipe Pena (aka sigsegv)
 * Contact: felipensp at gmail dot com
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require __DIR__ .'/config.php';
require __DIR__ .'/class.bughunter.php';

$longops = array(
	// Test options
	'extension:',
	'class:',
	'function:',
	'method:',
	'template:',
	'class-only',
	'method-only',
	'function-only',
	'memcheck',
	// Output options
	'html:'
	);
$opts = getopt('c:e:f:hm:t:', $longops);

$html = NULL;
$memcheck = false;

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
		case 't':
		case 'template':
			$hunter->setTemplate($value);
			break;
		case 'html':
			$html = $value;
			break;
		case 'memcheck':
			$hunter->setFlag(BugHunter::MEMORY_CHECK);
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
	--memcheck        performs memory checks (requires valgrind)
	
	--html=file       generate a HTML with stderr data
	
Contact: felipe@php.net

USAGE;
			exit;
	}
}

$hunter->run($memcheck);
$hunter->showResult();

// Save stderr result as HTML
if ($html) {
	$hunter->saveHTML($html);
}
