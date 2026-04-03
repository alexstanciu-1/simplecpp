<?php
function f(): int {
    $a = 5;
    try {
        return $a;
    } finally {
        $a = 99;
    }
}
var_dump(f());
