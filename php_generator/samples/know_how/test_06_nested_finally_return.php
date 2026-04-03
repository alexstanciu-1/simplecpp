<?php
function f(): int {
    try {
        try {
            return 10;
        } finally {
            echo "F2\n";
        }
    } finally {
        echo "F1\n";
    }
}
var_dump(f());
