<?php
declare(strict_types=1);

// Overwriting an existing key updates only that slot.
$x = [];
$x["name"] = "Alex";
$x["name"] = "Bob";
$x["id"] = 7;

var_dump($x);
