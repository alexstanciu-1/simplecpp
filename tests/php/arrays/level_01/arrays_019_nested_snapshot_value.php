<?php
declare(strict_types=1);

// Copying one nested row into another slot snapshots current contents only.
$x = [];
$x["src"] = ["id" => 1, "name" => "Alex"];
$x["dst"] = $x["src"];
$x["src"]["name"] = "Bob";

var_dump($x);
