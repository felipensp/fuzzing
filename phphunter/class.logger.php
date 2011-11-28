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
