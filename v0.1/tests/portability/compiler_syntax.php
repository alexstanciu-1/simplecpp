<?php
require_once __DIR__ . '/../../tools/php_portability/src/converter.php';
function ensure(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
$converter = new scpp\portability\Converter(require __DIR__ . '/../../tools/php_portability/function_map.php');
$source = <<<'SOURCE'
<?php
namespace syntax_proof;
/** @scpp-no-export */
final class Host {
    public function browser(): void { echo "{$this->value}"; unknown_host_function(); }
}
final class Work {
    /** @scpp-no-export */
    public static function report(): void { unknown_host_function(); }
    public int $cleanups = 0;
    public function run(bool $fail): int {
        try {
            try { if ($fail) { throw new \RuntimeException('expected'); } }
            finally { $this->cleanups++; }
        } catch (\RuntimeException $error) {
            return 9;
        } finally { $this->cleanups++; }
        $values /** hash<int> */ = [];
        $value = $values['missing'] ?? 4;
        switch ($value) { case 4: $value = 5; break; default: $value = 0; }
        do { $value++; } while ($value < 6);
        return match ($value) { 5, 6 => 8, default => 0 };
    }
}
$work = new Work();
echo $work->run(false), ':', $work->cleanups, ':', $work->run(true), ':', $work->cleanups, "\n";
SOURCE;
$out = $converter->convert($source, 'syntax.php');
ensure(!str_contains($out, 'class Host') && !str_contains($out, 'function report'), 'Host declarations leaked');
ensure(!str_contains($out, 'unknown_host_function'), 'Host body leaked');
foreach (['finally {', 'catch (\\scpp_portability_exception', "['missing'] ?? 4", 'switch ($value)', 'match ($value)', 'do {'] as $fragment) {
    ensure(str_contains($out, $fragment), 'Missing supported syntax: ' . $fragment);
}
ob_start(); eval(substr($source, 5)); $trace = ob_get_clean();
ensure($trace === "8:2:9:4\n", 'Incorrect cleanup/dispatch behavior: ' . $trace);
foreach (['/** @scpp-no-export */ $x = 1;', 'class X { /** @scpp-no-export */ public int $x = 0; }',
    'interface X { /** @scpp-no-export */ public function host(): void; }', '$x = unknown_host_function();', '$x = $row instanceof $type;'] as $body) {
    try { $converter->convert('<?php ' . $body, 'bad.php'); }
    catch (RuntimeException $error) { ensure(str_contains($error->getMessage(), 'bad.php'), 'Missing location'); continue; }
    throw new RuntimeException('Accepted unsupported syntax: ' . $body);
}
echo "Compiler syntax: host omission, cleanup, dispatch, coalescing and retained rejections passed\n";
