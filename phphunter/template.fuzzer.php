<?php

require_once __DIR__ .'/class.utils.php';

class TemplateFuzzer extends UtilsFuzzer {
	private $templates = array();
	private $blacklist = array();
	private $args      = array();
	protected $config  = array();
	
	public function __construct($config) {
		$types = array(1 => 'function', 2 => 'method', 3 => 'class');

		// Template files
		foreach (glob(__DIR__ .'/template/{1,2,3}*.t', GLOB_BRACE) as $template) {			
			$id = intval(substr(basename($template), 0, 1));
			$this->templates[$types[$id]][] = $template;
		}
		
		$this->config = $config;
		
		// Remove the error log files
		@unlink($config['stdout']);
		@unlink($config['stderr']);
		
		// Blacklist
		$this->blacklist = parse_ini_file(__DIR__ .'/blacklist.ini', true);
		
		// Pre-defined set of argument types
		$this->args = $this->genArgs();
	}
	
	private function genArgs() {
		$types = array(
			'',
			'1',
			'-1',
			'NULL',
			'fopen("php://temp")',
			'"abc://foobar"',
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
			'&$x'
		);
	
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
	
	private function runTest($php, $metadata, $src) {
		// Save the template
		$orig = $src;
		
		foreach ($this->args as $key => $arg) {
			// Change the template for each arg type
			$src = $orig;
			
			$this->replace($src, 'funcname', @$metadata['function']);
			$this->replace($src, 'classname', @$metadata['class']);
			$this->replace($src, 'methodname', @$metadata['method']);
			
			// For argument concatenation
			$this->replace($src, 'args2', empty($arg) ? $arg : $arg .',');
			
			// For argument without concatenation
			$this->replace($src, 'args', $arg);
			
			printf("- %s - Args: %s\n", $metadata['name'], $arg);

			$ret = $this->execute($php, $metadata['name'], $src);
					
			switch ($ret) {
				case 139: /* signal 11 */
					printf(" SIGSEGV\n", $ret);
					break;
				default:
					printf(" Exit status = %d\n", $ret);
					break;
			}
		}
	}
	
	public function runFuzzer($php, $metadata) {
		$type = $metadata['type'];
		
		if (in_array($metadata['name'], $this->blacklist[$type]['name'])) {
			printf("- %s is in the blacklist of %s!\n", $metadata['name'], $type);
			return;
		}

		printf("Testing %s %s\n", $type, $metadata['name']);
			
		foreach ($this->templates[$type] as $file) {
			printf("- Using template %s:\n", $file);
				
			$this->runTest($php, $metadata,
				file_get_contents($file));
		}
	}
}
