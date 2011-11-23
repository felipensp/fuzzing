<?php

require_once __DIR__ .'/class.utils.php';
require_once __DIR__ .'/template.fuzzer.php';

class BugHunter {
	const METHOD_ONLY   = 1;
	const FUNCTION_ONLY = 2;
	const CLASS_ONLY    = 4;
	
	private $flags   = 0;
	private $fuzzers = array();
	private $exts    = array();
	private $classes = array();
	private $funcs   = array();
	private $config  = array();
	private $php;
	
	public function __construct($config) {
		$this->config = $config;
		
		if (empty($config['php'])) {
			printf("Cannot found PHP executable in the configuration!\n");
			exit;
		}
		$this->php = $config['php'];
	}
	
	public function setFlag($value) {
		$this->flags |= $value;
	}
	
	public function setExtension($ext) {
		try {
			if (empty($ext)) {
				foreach (get_loaded_extensions() as $ext) {
					$this->exts[] = new ReflectionExtension($ext);
				}
			} else {
				$this->exts[] = new ReflectionExtension($ext);
			}
		} catch (Exception $e) {
			printf("Error: %s\n", $e->getMessage());
			exit;
		}
	}
	
	public function setClass($class) {
		try {
			$this->classes[] = new ReflectionClass($class);
		} catch (Exception $e) {
			printf("Error: %s\n", $e->getMessage());
			exit;			
		}
	}
	
	public function setFunction($func) {
		try {
			$this->funcs[] = new ReflectionFunction($func);
		} catch (Exception $e) {
			printf("Error: %s\n", $e->getMessage());
			exit;
		}
	}
	
	public function setFuzzer($fuzzer) {
		$this->fuzzers[] = $fuzzer;
	}
	
	private function runClass($fuzzer, ReflectionClass $class) {
		if (!($this->flags & (self::METHOD_ONLY|self::FUNCTION_ONLY))) {
			$metadata = array(
				'class' => $class->name,
				'name'  => $class->name,
				'type'  => 'class'
			);
			$fuzzer->runFuzzer($this->php, $metadata);
		}		
		if (!($this->flags & (self::CLASS_ONLY|self::FUNCTION_ONLY))) {
			foreach ($class->getMethods() as $method) {
				$metadata = array(
					'class'  => $class->name,
					'method' => $method->name,
					'name'   => $class->name .'::'. $method->name,
					'type'   => 'method'
				);
				$fuzzer->runFuzzer($this->php, $metadata);
			}
		}
	}
	
	public function run() {
		foreach ($this->fuzzers as $fuzzer) {
			// -e option
			foreach ($this->exts as $ext) {
				if (!($this->flags & (self::METHOD_ONLY|self::CLASS_ONLY))) {
					// Extension functions
					foreach ($ext->getFunctions() as $function) {
						$metadata = array(
							'function' => $function->name,
							'name'     => $function->name,
							'type'     => 'function'
						);
						$fuzzer->runFuzzer($this->php, $metadata);
					}
				}
				if (!($this->flags & (self::FUNCTION_ONLY))) {
					// Extension classes
					foreach ($ext->getClasses() as $class) {
						$this->runClass($fuzzer, $class);
					}
				}
			}
			// -c option
			foreach ($this->classes as $class) {
				$this->runClass($fuzzer, $class);
			}
			// -f option
			foreach ($this->funcs as $func) {
				$metadata = array(
					'function' => $func->name,
					'name'     => $func->name,
					'type'     => 'function'
				);
				$fuzzer->runFuzzer($this->php, $metadata);
			}
		}
	}
	
	public function showResult() {
		if (!file_exists($this->config['stderr'])) {
			return;
		}
		
		$log = file_get_contents($this->config['stderr']);
		
		printf("== RESULTS ==\n");		
		if (!$log) {
			printf("No errors!\n");
			return;
		}
		preg_match_all('/>> (.+)/', $log, $m);
		foreach (array_unique($m[1]) as $name) {
			printf("- %s\n", $name);
		}
		printf("%d errors found, %d functions/methods/classes with problem!\n",
			count($m[1]), count(array_unique($m[1])));
	}
}
