<?php
function f(): int {
    try {
        return 10;
    } finally {
        throw new Exception("boom");
    }
}
try {
    f();
} catch (Exception $e) {
    echo $e->getMessage(), "\n";
}
