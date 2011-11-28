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
	private $methods = array();
	private $config  = array();
	private $logger;
	
	public function __construct($config) {
		$this->config = $config;
		
		if (empty($config['php'])) {
			printf("Cannot found PHP executable in the configuration!\n");
			exit;
		}
		
		$this->logger = new Logger(
			array($config['stdout'], $config['stderr'])
		);
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
	
	public function setMethod($method) {
		try {
			$this->methods[] = new ReflectionMethod($method);
		} catch (Exception $e) {
			printf("Error: %s\n", $e->getMessage());
			exit;
		}
	}
	
	public function setFuzzer($fuzzer) {
		$fuzzer->init($this->logger, $this->config);
		
		$this->fuzzers[] = $fuzzer;
	}
	
	private function runClass($fuzzer, ReflectionClass $class) {
		if (!($this->flags & (self::METHOD_ONLY|self::FUNCTION_ONLY))) {
			$metadata = array(
				'class' => $class->name,
				'name'  => $class->name,
				'type'  => 'class'
			);
			$fuzzer->runFuzzer($metadata);
		}		
		if (!($this->flags & (self::CLASS_ONLY|self::FUNCTION_ONLY))) {
			foreach ($class->getMethods() as $method) {
				$metadata = array(
					'class'  => $class->name,
					'method' => $method->name,
					'name'   => $class->name .'::'. $method->name,
					'type'   => 'method'
				);
				$fuzzer->runFuzzer($metadata);
			}
		}
	}
	
	public function run() {
		$this->logger->start();
		
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
						$fuzzer->runFuzzer($metadata);
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
				$fuzzer->runFuzzer($metadata);
			}
			// -m option
			foreach ($this->methods as $method) {
				$metadata = array(
					'class'  => $method->class,
					'method' => $method->name,
					'name'   => $method->class .'::'. $method->name,
					'type'   => 'method'
				);
				$fuzzer->runFuzzer($metadata);
			}
		}
		
		$this->logger->end();
	}
	
	public function showResult() {
		if (!file_exists($this->config['stderr'])) {
			return;
		}
		
		$xml = simplexml_load_file($this->config['stderr']);
		
		printf("== RESULTS ==\n");		
		if (!$xml->stderr) {
			printf("No errors!\n");
			return;
		}
		printf("Error(s) found:\n");
		$names = array_unique(array_map('strval', $xml->xpath('//stderr/name')));
		
		foreach ($names as $name) {
			printf("- %s\n", $name);
		}
		printf("Check out the %s file!\n", $this->config['stderr']);		
	}

	public function saveHtml($file) {
		file_put_contents($file, $this->logger->toHtml());
	}
}
