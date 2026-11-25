<?php
namespace scpp\compiler;
$payload=$argv[2];
if (!in_array($payload,['bool','string'],true)) { throw new \InvalidArgumentException('Expected bool or string'); }
require __DIR__.'/wrapper_equality_fixture.php';
