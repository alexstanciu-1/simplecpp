<?php
// Documentation evidence adapter: strict-profile S2S only, no STAN/native build.
require $argv[1] . '/generators/php/bin/bootstrap.php';
try {
    $emitter = new Scpp\S2S\Transpiler(phpProfile: 'strict');
    $result = $emitter->transpile($argv[2]);
    if (basename(dirname($argv[2])) === '44') {
        $helper = (new Scpp\S2S\Transpiler(phpProfile: 'strict'))->transpile(dirname($argv[2]) . '/helper.phs', emitProgramEntry: false);
        echo "// Per-file emission only: project include/link assembly not performed.\n";
        echo "// === helper.hpp ===\n", implode("\n", $helper->headerLines), "\n";
        echo "// === helper.cpp ===\n", implode("\n", $helper->sourceLines), "\n";
    }
    echo "// === main.hpp ===\n", implode("\n", $result->headerLines), "\n";
    echo "// === main.cpp ===\n", implode("\n", $result->sourceLines), "\n";
    if ($result->errors !== []) {
        fwrite(STDERR, implode("\n", $result->errors) . "\n");
        exit(1);
    }
} catch (Throwable $error) {
    fwrite(STDERR, get_class($error) . ': ' . $error->getMessage() . "\n");
    exit(1);
}
