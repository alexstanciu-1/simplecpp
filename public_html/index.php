<?php

require_once __DIR__ . '/../generator/cpp.php';

// 1. The User's Code
$user_code = <<<'PHP'
<?php
echo "Hello to you too!";
PHP;

$ast = ast\parse_code($user_code, $version = 85);

$compiler = new \simplecpp\generator\cpp();
$cpp_output = $compiler->generate($ast);

echo "<pre>\n";
echo htmlspecialchars($cpp_output);

