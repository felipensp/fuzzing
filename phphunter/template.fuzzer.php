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

require_once __DIR__ .'/class.utils.php';
require_once __DIR__ .'/class.template.php';

class TemplateFuzzer extends UtilsFuzzer {
	private $templates = array();
	private $blacklist = array();
	private $args      = array();
	protected $config  = array();
	protected $logger;
	
	public function init(Logger $logger, $config) {
		$types = array(1 => 'function', 2 => 'method', 3 => 'class');

		// Template files
		foreach (glob(__DIR__ .'/template/{1,2,3}*.t', GLOB_BRACE) as $template) {
			$this->addTemplate($template);
		}
		
		$this->config = $config;
		$this->logger = $logger;
		
		// Blacklist
		$this->blacklist = parse_ini_file(__DIR__ .'/blacklist.ini', true);
		
		// Pre-defined set of argument types
		$this->args = $this->genArgs();
		
		@unlink('/tmp/fuzzer-memcheck');
	}
	
	private function addTemplate($template) {
		$types = array(1 => 'function', 2 => 'method', 3 => 'class');
		
		$id = intval(substr(basename($template), 0, 1));
		$this->templates[$types[$id]][] = new Template($template);
	}
	
	public function setTemplate($template) {
		unset($this->templates);
		
		$template = __DIR__ .'/template/'. $template;
		$this->addTemplate($template);
	}
	
	private function genArgs() {
		$types = array(
			'',
			'1',
			'-1',
			'NULL',
			'fopen("php://temp", "r")',
			'"abc://foobar"',
			'"phar://a.a//"',
			'"phar:///usr/local/bin/phar.phar/*%08x-%08x-%08x-%08x-%08x-%08x-%08x-%08x-%08x"',
			'"php://filter/resource=http://www.example.com"',
			'"php://temp"',
			'"strtoupper"',
			'array("reflectionclass", "export")',
			'function () { }',
			'"%08x-%08x-%08x-%08x-%08x-%08x-%08x-%08x-%08x%s"',
			'chr(0)',
			'getcwd().chr(0)."foo"',
			'PHP_INT_MAX',
			'PHP_INT_MAX-1',
			'array(new stdClass)',
			'str_repeat("A", 10000)',
			'new class($a) { function b() { return 1; } }',
			'new class() { }',
			'(function() { yield 1; })()',
		);
		
		// Call-time pass-by-reference has been removed in 5.5.0+
		if (version_compare(PHP_VERSION, '5.5.0-dev', '<')) {
			$types[] = '&$x';
		}
	
		$args = array();
		foreach ($types as $arg) {
			$args[] = $arg;
			if (!empty($arg)) {
				$args[] = implode(',', array_fill(0, 2, $arg));
				$args[] = implode(',', array_fill(0, 3, $arg));
				$args[] = implode(',', array_fill(0, 4, $arg));
				$args[] = implode(',', array_fill(0, 5, $arg));
			}
		}
		
		return $args;
	}
	
	private function runTest($metadata, Template $template, $memcheck = false) {
		$test_args = $template->hasArgs();
		
		foreach ($this->args as $key => $arg) {			
			// Prepare for next template
			$template->clear();
			
			$template->replace('funcname', @$metadata['function']);
			$template->replace('classname', @$metadata['class']);
			$template->replace('methodname', @$metadata['method']);

			// For argument concatenation
			$template->replace('args2', empty($arg) ? $arg : $arg .',');
			
			// For argument without concatenation
			$template->replace('args', $arg);

			if (version_compare(PHP_VERSION, '8.0', '>='))
				if (preg_match('/\s*,?&\$/', $template->getSource()))
					continue;
			
			if ($test_args) {
				printf("- %s - Args: %s\n", $metadata['name'], $arg);
			} else {
				printf("- %s:\n", $metadata['name']);
			}

			if ($memcheck) {
				$ret = $this->memcheck($metadata['name'], $template->getSource());
			} else {
				$ret = $this->execute($metadata['name'], $template->getSource());
						
				switch ($ret) {
					case 139: /* signal 11 */
						printf(" SIGSEGV\n", $ret);
						break;
					default:
						printf(" Exit status = %d\n", $ret);
						break;
				}
				
				if (!$test_args) {
					break;
				}
			}
		}
	}
	
	public function runFuzzer($metadata, $memcheck) {
		$type = $metadata['type'];
		
		if (isset($this->blacklist[$type]['name'])
			&& in_array($metadata['name'], $this->blacklist[$type]['name'])) {
			printf("- %s is in the blacklist of %s!\n", $metadata['name'], $type);
			return;
		}

		printf("Testing %s %s\n", $type, $metadata['name']);
			
		foreach ($this->templates[$type] as $template) {
			printf("- Using template %s:\n", $template->getPath());
				
			$this->runTest($metadata, $template, $memcheck);
		}
	}
}
