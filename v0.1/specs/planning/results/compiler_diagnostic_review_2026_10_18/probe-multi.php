<?php
namespace scpp\compiler;
require_once '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/boot.php';
$directory = $argv[1];
$stage = 'parse';
try {
    $compiler = new Compiler();
    $compiler->init([$directory]);
    $compiler->select_entry('main', 'main.phs');
    $compiler->sync([]);
    $stage = 'prepare';
    $compiler->prepare();
    file_put_contents($directory . '/prepared.txt', "passed\n");
    $stage = 'emit';
    $compiler->cpp();
    file_put_contents($directory . '/artifacts.json', CPP_Output::encode(Model::$cpp_files));
    echo json_encode(['stage' => 'emitted']);
} catch (\Throwable $error) {
    echo json_encode(['stage' => $stage, 'error' => $error->getMessage()]);
}
