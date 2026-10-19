<?php
namespace scpp\compiler;
$carrier='result';
$payload=$argv[2];
if (!in_array($payload,['int','float','bool','string'],true)) { throw new \InvalidArgumentException('Expected canonical scalar payload'); }
require __DIR__.'/wrapper_equality_fixture.php';
