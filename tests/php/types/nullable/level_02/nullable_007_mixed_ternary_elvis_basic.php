<?php
declare(strict_types=1);

function a(): mixed { return null; }
function b(): mixed { return 22; }
function c(): mixed { return 0; }
function d(): mixed { return "done"; }
function e(): mixed { return false; }

var_dump(a() ?: 7);
var_dump(b() ?: 7);
var_dump(c() ?: 7);
var_dump(d() ?: 7);
var_dump(e() ?: 7);
