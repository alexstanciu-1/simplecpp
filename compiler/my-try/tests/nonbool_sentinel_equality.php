<?php
namespace scpp\compiler;
$carrier=$argv[2]; $payload=$argv[3];
if (!in_array($carrier,['result_or_false','result_or_bool'],true) || !in_array($payload,['int','float','string'],true)) { throw new \InvalidArgumentException('Expected sentinel carrier and non-bool scalar payload'); }
require __DIR__.'/wrapper_equality_fixture.php';
