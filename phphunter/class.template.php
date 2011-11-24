<?php

class Template {
	private $path; // Template file path
	private $src;  // Real template file source
	private $tmp;  // Temporary string where the replaces are done
	
	public function __construct($path) {
		if (!file_exists($path)) {
			printf("Template file '%s' not found!\n", $path);
			exit;
		}	
		$this->path = $path;
		$this->src  = file_get_contents($path);
		$this->tmp  = $this->src;
	}
	
	public function clear() {
		$this->tmp = $this->src;
	}
	
	public function getSource() {
		return $this->tmp;
	}
	
	public function getPath() {
		return basename($this->path);
	}
	
	public function hasArgs() {
		return preg_match('/%\{args2?\}/', $this->src);
	}
	
	public function replace($tag, $value) {
		$this->tmp = preg_replace('/%\{'. preg_quote($tag) .'\}/', $value, $this->tmp);
	}
}
