<?php
function f() {
    try {
        throw new Exception("x");
    } finally {
        echo "F\n";
    }
}
try {
    f();
} catch (Exception $e) {
    echo $e->getMessage(), "\n";
}
