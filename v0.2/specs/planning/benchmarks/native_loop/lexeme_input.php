<?php
// Prepare a standalone benchmark package and the serial compiler's lexeme boundaries.
declare(strict_types=1);
use scpp\compiler\{Tokenizer, flavor};

$root = dirname(__DIR__, 4);
$dir = $argv[1];
if (!mkdir($dir)) throw new RuntimeException('Expected a new package directory');
if ($argc === 4) {
    $pattern = '~' . file_get_contents($argv[3]) . '~u';
    $project = $argv[2];
} else {
    require $root . '/compiler/boot.php';
    $pattern = (new ReflectionMethod(Tokenizer::class, 'pattern'))->invoke(null, flavor::php);
    $project = "$root/samples/accounting-large";
}
if (!str_starts_with($pattern, '~') || !str_ends_with($pattern, '~u')) {
    throw new RuntimeException('Update the native PCRE options for this pattern');
}
file_put_contents("$dir/pattern", substr($pattern, 1, -2));
$paths = array_merge(glob("$project/*.phs"), glob("$project/src/*.phs"));
if (!$paths) throw new RuntimeException("No PHS sources in $project");
sort($paths, SORT_STRING);
foreach ($paths as $i => $path) {
    $source = file_get_contents($path);
    if (preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE) === false) {
        throw new RuntimeException(preg_last_error_msg());
    }
    $end = 0;
    $expected = [];
    foreach ($matches[0] as [$word, $start]) {
        if ($start !== $end) throw new RuntimeException("Gap in $path");
        $expected[] = $end += strlen($word);
    }
    if ($end !== strlen($source)) throw new RuntimeException("Incomplete match in $path");
    file_put_contents("$dir/$i.src", $source);
    file_put_contents("$dir/$i.ends", implode("\n", $expected));
}
file_put_contents("$dir/count", (string)count($paths));
