<?php

set_time_limit(0);

date_default_timezone_set('America/Sao_Paulo');

/* Options */
define('FUZZ_CLASS',		'c');
define('FUZZ_EXTENSION',	'e');
define('FUZZ_FUNCTION', 	'f');
define('FUZZ_METHOD', 		'm');

function fuzz_error_handler($errno, $errstr, $errfile, $errline) {
	global $OPTIONS;

	if (isset($OPTIONS['v'])) {
		printf("\n[%s; line: %d]\n", $errstr, $errline);
	}
}

set_error_handler('fuzz_error_handler');

/* Skip */
$SKIP = parse_ini_file(dirname(__FILE__) .'/bughunter.ini', true);

class _stdclass extends stdclass {
	public $a;
	public function __toString() {
		return '';
	}
}

$arr = array();
$arr[0] = &$arr;

$objtest = new _stdclass;
$objtest->a = array();
$objtest->a[] = &$objtest->a;

/* Parameters */
$PARAMS = array('Maximum integer' 	=> PHP_INT_MAX,
				'Negative integer'	=> ~PHP_INT_MAX,
				'Zero'				=> 0,
				'Float' 			=> PHP_INT_MAX+1,
				'Large string'		=> str_repeat(implode(range('a', 'z')), 200),
				'NULL Byte'			=> "\0",
				'Empty string'		=> '',
				'Binary string'		=> b'',
				'Resource'			=> fopen(__FILE__, 'r'),
				'Object'			=> new _stdclass,
				'Valid class name'  => '_stdClass',
				'NULL'				=> NULL,
				'Array'				=> range(1, 100),
				'Array recursion'	=> $arr,
				'Valid callback'	=> 'strtoupper',
				'Invalid callback'  => 'foobar::bar',
				'Invalid callback 2 ' => array('foobar', 'bar'),
				'Invalid callback 3'  => array(1, 2),
				'Object 2'			  => $objtest,
				'Closure'			  => function () { return new stdClass; });

/* Valid arguments for class instantiation to be used in methods invocation */
$INSTANCE = array(	/* Date */
					'dateinterval'					=> array('P3Y6M4DT12H30M5S'),
					'datetimezone'					=> array('gmt'),
					/* DOM */
					'domelement' 					=> array('foobar'),
					'domattr'						=> array('foobar'),
					'domcdatasection'				=> array('foobar'),
					'domentityreference'			=> array('foobar'),
					'domprocessinginstruction' 		=> array('foobar'),
					'domxpath'						=> array(new DOMDocument),
					/* PDO */
					'pdo'							=> array('sqlite:memory:'),
					/* Phar */
					'phar'  						=> array(__DIR__ .'/phar_test.phar'),
					/* Reflection */
					'reflectionfunction' 			=> array('trim'),
					'reflectionparameter'			=> array('trim', 'str'),
					'reflectionproperty'			=> array('reflectionclass', 'name'),
					'reflectionmethod'				=> array('simplexmlelement::asxml'),
					'reflectionclass'				=> array('simplexmlelement'),
					'reflectionextension' 			=> array('pcre'),
					/* SimpleXMLElement */
					'simplexmlelement'				=> array('<foo><foobar/></foo>'),
					'simplexmliterator'				=> array('<foo><foobar/></foo>'),
					/* Soap */
					'soapclient' 					=> array('ext/soap/tests/classmap003.wsdl',	array('actor' =>'http://schemas.nothing.com', 'typemap' => array(array('type_ns' => 'http://schemas.nothing.com', 'type_name' => 'book', 'from_xml'  => 'book_from_xml')))),
					/* SPL */
					'cachingiterator'				=> array(new ArrayIterator(array(1, 2, 3)),  CachingIterator::CALL_TOSTRING),
					'directoryiterator'				=> array(__DIR__),
					'iteratoriterator'				=> array(new SimpleXMLElement('<a></a>')),
					'filesystemiterator'			=> array(__DIR__),
					'globiterator'					=> array(__DIR__),
					'infiniteiterator'				=> array(new ArrayIterator(range(0,2))),
					'limititerator'					=> array(new ArrayIterator(array(1,2,3,4)), 1, 2),
					'norewinditerator'				=> array(new ArrayIterator(array(0 => 'A', 1 => 'B', 2 => 'C'))),
					'parentiterator'				=> array(new RecursiveArrayIterator(array(1,array(21,22, array(231)),3))),
					'recursivearrayiterator' 		=> array(array(1, 2, array(31, 32, array(331)), 4)),
					'recursivecachingiterator' 		=> array(new RecursiveArrayIterator(array(1, 2, array(31, 32, array(331)), 4))),
					'recursiveiteratoriterator' 	=> array(new RecursiveCachingIterator(new RecursiveArrayIterator(array(1, 2, array(31, 32, array(331)), 4)))),
					'recursiveiteratoriterator' 	=> array(new RecursiveArrayIterator(array(1, array(21, 22), 3))),
					'recursiveregexiterator' 		=> array(new RecursiveArrayIterator(array('Foo', array('Bar'), 'FooBar', array('Baz'), 'Biz')), '/bar/'),
					'recursivetreeiterator'			=> array(new RecursiveArrayIterator(array('a' => array('b'), 'c' => array('d')))),
					'recursivedirectoryiterator' 	=> array('.'),
					'regexiterator'					=> array(new ArrayIterator(range(1, 10)), '/\d/'),
					'splfileinfo'					=> array(__FILE__),
					'splfileobject'					=> array(__FILE__),
					/* SQLite3 */
					'sqlite3'						=> array(__DIR__ .'/sqlite3test'),
					'sqlite3stmt'					=> array(new sqlite3(__DIR__ .'/sqlite3test'), 2),
					'sqlitedatabase'				=> array(__DIR__ .'/sqlite3test'));

if (version_compare(PHP_VERSION, '5.3', '>=')) {
	$INSTANCE['dateperiod'] = array(new DateTime('2008-07-20T22:44:53+0200'), DateInterval::createFromDateString('1 day'), 10);
}

if (extension_loaded('intl')) {
	/* Intl */
	$INSTANCE['collator'] = array('en_US');
	$INSTANCE['numberformatter'] = array('en_US', NumberFormatter::DECIMAL);
	$INSTANCE['messageformatter'] = array('en_US', '{0,number,integer}');
	$INSTANCE['intldateformatter'] = array('en_US', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'America/Los_Angeles', IntlDateFormatter::GREGORIAN);
}

if (extension_loaded('oauth')) {
	$INSTANCE['oauth'] = array(1, 1);
}

/* Check if the given class/method/function should be skipped */				  
function fuzz_skip($mode, $test, $name, $param = NULL) {
	global $SKIP;
	
	$name = strtolower($name);
	
	switch ($mode) {
		case FUZZ_CLASS:
			if (in_array($name, $SKIP['ignored_classes']['class'])) {
				return true;
			}
			/* Ignored classes in specific version */
			if (in_array($name, $SKIP['ignored_classes:'.substr(PHP_VERSION_ID, 0, 3)]['class'])) {
				return true;
			}			
			/* Skip per test */
			if (is_null($param) && in_array($name, $SKIP[$test]['skip'])) {
				return true;
			}
			/* Skip per test in specific version */
			if (is_null($param) && in_array($name, $SKIP[$test.':'.substr(PHP_VERSION_ID, 0, 3)]['skip'])) {
				return true;
			}
			/* Skip per argument type */
			if (!is_null($param) && preg_grep('/'. $name .':(?:\d+,)*'. $param .'(?:,.*|$)/', $SKIP[$test]['skip'])) {
				return true;
			}
			break;
		case FUZZ_FUNCTION:		
			if (in_array($name, $SKIP['ignored_functions']['func'])) {
				return true;
			}
			/* Ignored classes in specific version */
			if (in_array($name, $SKIP['ignored_functions:'.substr(PHP_VERSION_ID, 0, 3)]['class'])) {
				return true;
			}		
			/* Skip per test */
			if (is_null($param) && in_array($name, $SKIP[$test]['skip'])) {
				return true;
			}
			/* Skip per test in specific version */
			if (is_null($param) && in_array($name, $SKIP[$test.':'.substr(PHP_VERSION_ID, 0, 3)]['skip'])) {
				return true;
			}
			/* Skip per argument type */
			if (!is_null($param) && preg_grep('/'. $name .':(?:\d+,)*'. $param .'(?:,.*|$)/', $SKIP[$test]['skip'])) {
				return true;
			}
			break;
		case FUZZ_METHOD:
			if (in_array($name, $SKIP['ignored_methods']['method'])) {
				return true;
			}
			/* Ignored classes in specific version */
			if (in_array($name, $SKIP['ignored_methods:'.substr(PHP_VERSION_ID, 0, 3)]['class'])) {
				return true;
			}		
			/* Skip per test */
			if (is_null($param) && in_array($name, $SKIP[$test]['skip'])) {
				return true;
			}
			/* Skip per test in specific version */
			if (is_null($param) && in_array($name, $SKIP[$test.':'.substr(PHP_VERSION_ID, 0, 3)]['skip'])) {
				return true;
			}
			/* Skip per argument type */
			if (!is_null($param) && preg_grep('/'. $name .':(?:\d+,)*'. $param .'/', $SKIP[$test]['skip'])) {
				return true;
			}
			break;
	}
	return false;
}

/* Return a instance to given class name */
function fuzz_get_instance($class) {
	global $INSTANCE;
	
	$class_name = strtolower($class->name);
	if (isset($INSTANCE[$class_name])) {
		return $class->newInstanceArgs($INSTANCE[$class_name]);
	} 
	return $class->newInstance();
}

function fuzz_return_value($value) {
	if (!is_object($value) || (is_object($value) && method_exists($value, '__tostring'))) {
		$max_len = 30;
		$value = strval($value);
		$val_len = strlen($value);

		if ($val_len <= $max_len) {
			return sprintf("'%s'", $value);
		} else {
			return sprintf("'%s...' (length: %d)", substr($value, 0, $max_len), $val_len);
		}
	} else {
		return "'<object>'";
	}
}

function fuzz_check_params($mode, ReflectionFunctionAbstract $function) {
	global $OPTIONS, $PARAMS;

	$func_name = $function->getName();
	$func_args_required = $function->getNumberOfRequiredParameters();
	$func_args_total = $function->getNumberOfParameters();

	/* Check if it is a method */
	if ($mode == FUZZ_METHOD) {
		$func_name = $function->class .'::'. $func_name;		
	}
	
	printf("\n%s: %s - Arguments: [%d:%d]\n", ($mode == FUZZ_METHOD ? 'Method' : 'Function'), $func_name, $func_args_required, $func_args_total);
	print "---------------------------------------------------------------\n";
	
	/* Skipping */
	if (fuzz_skip($mode, __FUNCTION__, $func_name)) {
		print " >> Skipped!\n";
		return;
	}

	/* Skip constructors */
	if ($mode == FUZZ_METHOD && $function->isConstructor()) {
		print " >> Skipped (It's a constructor)\n";
		return;
	}
	
	$arg_num = 0;
	$instance = false;

	/* Get the class instance for invoking a method */
	if ($mode == FUZZ_METHOD && !$function->isConstructor()) {
		if ($function->isStatic()) {
			$instance = NULL;
		} else {
			/* Trying to create a instance */
			$class = $function->getDeclaringClass();
			if ($class->isInstantiable()) {
				$instance = fuzz_get_instance($class);
			}
		}
	}

	foreach ($PARAMS as $title => &$value) {		
		try {
			/* Skip argument type not desired */
			if (isset($OPTIONS['a']) && $OPTIONS['a'] != $arg_num) {
				$arg_num++;
				continue;
			}

			printf(" >> [%d] <%s> Argument type: %s\n", $arg_num, $func_name, $title);
			print "    -------------------------------------------------------\n";
			
			if (fuzz_skip($mode, __FUNCTION__, $func_name, $arg_num)) {
				print "      >> Skipped the argument type!\n";
				$arg_num++;
				continue;
			}
			
			/* void function */
			if ($func_args_total == 0) {
				print "    - Passing 0 parameter\n";
				if ($mode == FUZZ_FUNCTION) {					
					$retval = $function->invokeArgs(array());					
					printf("      return: %s\n", fuzz_return_value($retval));
				} else {
					if ($instance !== false) {
						$retval = $function->invoke($instance);
						printf("      return: %s\n", fuzz_return_value($retval));
					} else {
						print "      It isn't instantiable\n";
					}
				}
			} else {
				for ($i = $func_args_total; $i >= ($func_args_required ? $func_args_required : 1); $i--) {
					printf("    - Passing %d parameter%s\n", $i, ($i == 1 ? '' : 's'));
					if ($mode == FUZZ_METHOD) {
						if ($instance !== false) {
							$retval = $function->invokeArgs($instance, array_fill(0, $i, $value));
						} else {
							print "      It isn't instantiable\n";
						}
					} else {
						$retval = $function->invokeArgs(array_fill(0, $i, $value));
					}
					printf("      return: %s\n", fuzz_return_value($retval));
				}
			}
		} catch (Exception $e) {
			printf("failed (%s)\n", $e->getMessage());
		}
		$arg_num++;
	}
}

/* Testing the class handlers */
function fuzz_check_class_handlers(ReflectionClass $class) {
	global $SKIP, $INSTANCE;

	printf("\nClass: %s\n", $class->name);
	print "    -------------------------------------------------------\n";

	if (!$class->isInstantiable()) {
		print "    - It isn't instantiable\n";
		return;
	}
	
	if (fuzz_skip(FUZZ_CLASS, __FUNCTION__, $class->name)) {
		print " >> Skipped!\n";
		return;
	}
	
	$instance = fuzz_get_instance($class);

	if (fuzz_skip(FUZZ_CLASS, __FUNCTION__, $class->name, 1)) {
		print "    >> Skipped attempt to read inexistent property!\n";
	} else {
		printf("    - Trying to read inexistent property: %d\n", $instance->inexistent);
		printf("    - Checking isset() with inexistent property: %d\n", isset($instance->inexistent));
		printf("    - Checking empty() with inexistent property: %d\n", empty($instance->inexistent));
		printf("    - Trying to set a property: %d\n", $instance->inexistent = 1);
		printf("    - Checking isset() with existent property: %d\n", isset($instance->inexistent));
		printf("    - Checking empty() with existent property: %d\n", empty($instance->inexistent));
	}

	if (fuzz_skip(FUZZ_CLASS, __FUNCTION__, $class->name, 1)) {
		print "    >> Skipped attempt to read inexistent array property!\n";
	} else {
		printf("    - Trying to read inexistent array property: %d\n", $instance->inexistent2[1]);
		printf("    - Checking isset() with inexistent array property: %d\n", isset($instance->inexistent2[1]));
		printf("    - Checking empty() with inexistent array property: %d\n", empty($instance->inexistent2[1]));
		printf("    - Trying to set a array property: %d\n", $instance->inexistent2[] = 1);
		printf("    - Checking isset() with existent array property: %d\n", isset($instance->inexistent2[0]));
		printf("    - Checking empty() with existent array property: %d\n", empty($instance->inexistent2[0]));	
	}

	printf("    - Checking comparison: %d\n", $instance == $instance);
	printf("    - Serializing object: %s\n", $serialized = serialize($instance));
	
	if (fuzz_skip(FUZZ_CLASS, __FUNCTION__, $class->name, 2)) {
		printf("    >> Skipped attempt to unserialising object\n");
	} else {
		printf("    - Unserialising object: %s\n", unserialize($serialized) == $instance);
	}
	
	if (fuzz_skip(FUZZ_CLASS, __FUNCTION__, $class->name, 4)) {
		printf("    >> Skipped attempt to count object!\n");
	} else {
		try {
			printf("    - Checking the count() return: %d\n", count($instance));
		} catch (Exception $e) { }
	}

	if (fuzz_skip(FUZZ_CLASS, __FUNCTION__, $class->name, 3)) {
		printf("    >> Skipped attempt to iterate object!\n");
	} else {
		printf("    - Testing iterator:\n\t");
		foreach ($instance as $key => $value) { print '.'; }
		print "\n";
	}

	if (!fuzz_skip(FUZZ_CLASS, __FUNCTION__, $class->name, 1)) {
		printf("    - Checking value after unset(): %s\n", $instance->inexistent);
	}
	
	if (fuzz_skip(FUZZ_CLASS, __FUNCTION__, $class->name, 0)) {
		print "    >> Skipped attempt to clone object!\n";
	} else {
		printf("    - Trying to clone the object: %d\n", $instance2 = clone $instance);
		if (!fuzz_skip(FUZZ_CLASS, __FUNCTION__, $class->name, 1)) {
			printf("    - Trying to access a cloned property: %s\n", $instance2->inexistent);
		}
	}
	printf("    - Testing with var_export:\n\t%s\n", var_export($instance, true));
	printf("    - Testing unset the properties and the object\n");
	
	if (!fuzz_skip(FUZZ_CLASS, __FUNCTION__, $class->name, 1)) {
		unset($instance->inexistent);
		unset($instance->inexistent2[0]);
		unset($instance->inexistent2[1]);
		unset($instance->inexistent2);
	}
	unset($instance);
}

function fuzz_handler_test($type, $name) {
	switch ($type) {
		case FUZZ_CLASS:
			try {
				$class = new ReflectionClass($name);

				fuzz_check_class_handlers($class);

				foreach ($class->getMethods() as $method) {
					fuzz_handler_test(FUZZ_METHOD, $class->name .'::'. $method->name);
				}
			} catch (Exception $e) {
				printf("Error: %s\n", $e->getMessage());
			}
			break;
		case FUZZ_EXTENSION:
			if ($name === false) {
				$extensions = get_loaded_extensions();
				natcasesort($extensions);

				foreach ($extensions as $extension) {
					fuzz_handler_test(FUZZ_EXTENSION, $extension);
				}
			} else {
				try {
					$extension = new ReflectionExtension($name);

					foreach ($extension->getClasses() as $class) {
						fuzz_handler_test(FUZZ_CLASS, $class->getName());
					}
					foreach ($extension->getFunctions() as $function) {
						fuzz_handler_test(FUZZ_FUNCTION, $function->getName());
					}
				} catch (Exception $e) {
					printf("Error: %s\n", $e->getMessage());
				}
			}
			break;
		case FUZZ_FUNCTION:
			try {
				$function = new ReflectionFunction($name);
				fuzz_check_params($type, $function);
			} catch (Exception $e) {
				printf("Error: %s\n", $e->getMessage());
			}
			break;
		case FUZZ_METHOD:
			try {
				$method = new ReflectionMethod($name);
				fuzz_check_params($type, $method);
			} catch (Exception $e) {
				printf("Error: %s\n", $e->getMessage());
			}
			break;
	}
}

$OPTIONS = getopt('a:c:e::f:hm:v');

foreach ($OPTIONS as $option => $value) {
	switch ($option) {
		case 'h':
			print <<<OPTIONS
Options:
 -a argument type
 -c class name
 -e extension name
 -f function name
 -m method name 
 -v verbose (show the error messages)


OPTIONS;
			break;
		case FUZZ_CLASS:
		case FUZZ_EXTENSION:
		case FUZZ_FUNCTION:
		case FUZZ_METHOD:
			fuzz_handler_test($option, $value);
			break;
	}
}

