<?php

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
}
