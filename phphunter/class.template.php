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
