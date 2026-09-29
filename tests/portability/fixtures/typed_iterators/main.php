<?php
namespace scpp\compiler;

$cursor = Fixture::retained();
Fixture::display($cursor);
$empty = new empty_children_iterator();
Fixture::display($empty);
$source = new function_node();
$one = $source->children();
$two = $source->children();
$one->next();
echo $one->current()->value, ':', $two->current()->value, "\n";

try {
	$empty->current();
	echo "unexpected current";
}
catch (\LogicException $error) {
	echo "empty rejected;";
}
try {
	$cursor->rewind();
	echo "unexpected rewind";
}
catch (\LogicException $error) {
	echo "rewind rejected\n";
}
