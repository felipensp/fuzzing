<?php

try {
	$x = new %{classname};

	$x->foo = $x;
} catch (Exception $e) {
	$x->foo = $e;
}

unserialize(serialize($x)) = new %{classname};


