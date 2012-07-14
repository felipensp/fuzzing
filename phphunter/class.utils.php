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

require_once __DIR__ .'/class.logger.php';

abstract class UtilsFuzzer {	
	// Create the process to run the test code
	public function execute($tag, $src) {
		$fdspec = array(
		   0 => array('pipe', 'r'),
		   1 => array('pipe', 'w'),
		   2 => array('pipe', 'w')
		);
		
		$cmd = trim($this->config['php'] . ' '. $this->config['args']);

		$process = proc_open($cmd, $fdspec, $pipes, '/tmp');

		if (is_resource($process)) {
			fwrite($pipes[0], $src);
			fclose($pipes[0]);
			
			// stdout
			if ($err = stream_get_contents($pipes[1])) {
				if ($this->config['stdout']) {
					$result = array(
						'name' => $tag,
						'src' => $src,
						'out' => trim($err)
					);
					
					$this->logger->log(Logger::STDOUT, $result);
				}
			}
			
			// stderr
			if ($err = stream_get_contents($pipes[2])) {
				if ($this->config['stderr']) {
					if (!preg_match('/^sh:/', $err)) {
						$result = array(
							'name' => $tag,
							'src' => $src,
							'out' => trim($err)
						);
						
						$this->logger->log(Logger::STDERR, $result);
					}
				}
			}
			
			
			fclose($pipes[1]);
			fclose($pipes[2]);

			return proc_close($process);
		}
		die("Cannot open process!\n");
	}
		
	public function memcheck($tag, $src) {
		$fdspec = array(
		   0 => array('pipe', 'r'),
		   1 => array('pipe', 'w'),
		   2 => array('pipe', 'w')
		);
		
		$cmd = 'valgrind -q --tool=memcheck --log-file=/tmp/fuzzer-memcheck ';
		$cmd .= trim($this->config['php'] . ' '. $this->config['args']);
		
		$env = array('USE_ZEND_ALLOC' => '0');
		
		$process = proc_open($cmd, $fdspec, $pipes, '/tmp', $env);

		if (is_resource($process)) {
			fwrite($pipes[0], $src);
			fclose($pipes[0]);
			fclose($pipes[1]);
			fclose($pipes[2]);	
			
			// stderr
			if ($err = @file_get_contents('/tmp/fuzzer-memcheck')) {
				if ($this->config['stderr']) {
					if (!preg_match('/^sh:/', $err)) {
						$result = array(
							'name' => $tag,
							'src' => $src,
							'out' => trim($err)
						);
						
						$this->logger->log(Logger::STDERR, $result);
					}
				}
			}
			return proc_close($process);
		}
		die("Cannot open process!\n");
	}
}
