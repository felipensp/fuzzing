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

error_reporting(E_ALL);

ini_set('memory_limit', -1);
set_time_limit(0);

// Configuration
$config = array(
	// Set to false if wan't to log it
	'stdout' => '/tmp/fuzzer-stdout', 
	// File where will go the crash and memleak reports
	'stderr' => '/tmp/fuzzer-stderr',
	// Current PHP executable
	'php'    => realpath($_SERVER['_']), 
	// Arguments to PHP executable (e.g. -dextension=...)
	'args'   => '-n'
);
