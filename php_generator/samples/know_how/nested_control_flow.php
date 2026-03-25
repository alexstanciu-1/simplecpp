<?php

$a = 100;
while ($a > 0) {
	for ($i = 10; $i < 20; $i++) {
		if ($i > 15) {
			break;
		}
	}
	$a--;
}
