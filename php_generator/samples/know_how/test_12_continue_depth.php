<?php
while (true) {
    try {
        continue 3;
    } finally {
        echo "F\n";
    }
}
