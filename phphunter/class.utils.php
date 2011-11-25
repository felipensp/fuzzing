<?php

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
				
			$str = '>> '. $tag . "\n" . str_repeat('=', 30);
			$str .= "\n" . $src;
			
			// stdout
			if ($err = stream_get_contents($pipes[1])) {
				if ($this->config['stdout']) {
					$log = $str . 'Error: '. "\n". $err . "\n";
			
					file_put_contents($this->config['stdout'], $log, FILE_APPEND);
				}
			}
			
			// stderr
			if ($err = stream_get_contents($pipes[2])) {
				if ($this->config['stderr']) {
					if (!preg_match('/^sh:/', $err)) {
						$log = $str . 'Error: '. "\n". $err . "\n";
					
						file_put_contents($this->config['stderr'], $log, FILE_APPEND);
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
