<?php

function weakref(object $x): WeakReference
{
	return WeakReference::create($x);
}

function weakref_get(WeakReference $w): ?object
{
	return $w->get();
}

if (is_file($argv[1])) {
	require_once $argv[1];
}

