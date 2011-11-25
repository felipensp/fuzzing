<?php

class Logger {
	const STDOUT = 0;
	const STDERR = 1;
	
	private $files = array();
	private $fds = array();
	
	public function __construct($arr) {
		foreach ($arr as $type => $file) {
			$this->files[$type] = $file;
			$this->fds[$type] = fopen($file, 'w');
		}
	}
	
	public function start() {
		foreach ($this->fds as $fd) {
			fwrite($fd, "<results>\n");
		}
	}
	
	public function end() {		
		foreach ($this->fds as $fd) {
			fwrite($fd, "</results>\n");
			fclose($fd);
		}
	}
	
	public function log($type, $data) {
		$tag = $type == self::STDOUT ? 'stdout' : 'stderr';
		
		$xml = sprintf("\t<%s>\n", $tag);
		$xml .= sprintf("\t\t<name>%s</name>\n", $data['name']);
		$xml .= sprintf("\t\t<code><![CDATA[\n%s\n]]></code>\n", $data['src']);
		$xml .= sprintf("\t\t<result><![CDATA[%s]]></result>\n", $data['out']);
		$xml .= sprintf("\t</%s>\n", $tag);
		
		fwrite($this->fds[$type], $xml);
	}
	
	public function toHtml() {
		$xml = simplexml_load_file($this->files[self::STDERR]);
		
		if (!isset($xml->stderr)) {
			return;
		}
		
		$html = "<html>\n";
		$html .= " <head>\n";
		$html .= "  <title>PHPHunter - Log</title>\n";
		$html .= "  <style>pre { background: #CCC; }</style>\n";
		$html .= " </head>\n";
		$html .= " <body>\n";
		$html .= "  <h1>Results</h1>\n";
		
		foreach ($xml->stderr as $info) {
			$html .= "  <div id=\"result\">\n";
			$html .= "   <h3>". $info->name ."</h3>\n";
			$html .= "   <pre>". highlight_string(trim($info->code), true) ."</pre>\n";
			$html .= "   <p><strong>Result:</strong><br/><pre>". htmlentities($info->result) ."</pre></p>\n";
		}
		
		$html .= " </body>\n";
		$html .= "</html>";
		
		return $html;
	}
}
