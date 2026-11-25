<?php
namespace scpp\compiler;
require_once __DIR__ . '/compound_assignments.php';

$source = <<<'PHS'
class Totals {
    public $items vector<int> = [10];
    public $map hash<int> = {x: 10};
    public function update(): void { $this->items[0] += 2; $this->map["x"] -= 3; }
}
function key(int &$n): int { $n++; return 0; }
function resize(vector<int> &$v, int &$n): int { $n = $n * 10 + 2; for ($i = 0; $i < 100; $i++) { $v[] = $i; } $v[0] = 100; return 3; }
function replace(vector<int> &$v): int { $v = [100]; return 4; }
$v vector<int> = [10]; $n = 0;
$result = ($v[key($n)] += resize($v, $n));
if ($result !== 13 || $v[0] !== 13 || $n !== 12 || count($v) !== 101) { return 1; }
$v[0] += replace($v);
if ($v[0] !== 17) { return 2; }
$owner = new Totals(); $owner->update();
if ($owner->items[0] !== 12 || $owner->map["x"] !== 7) { return 3; }
$nested vector<hash<int>> = [{x: 5}]; $copy = $nested;
$nested[0]["x"] *= 3;
if ($nested[0]["x"] !== 15 || $copy[0]["x"] !== 5) { return 4; }
$m mixed = {a: {x: 5}}; $mcopy = $m;
$m["a"]["x"] += 2;
$mv int = $m["a"]["x"]; $mc int = $mcopy["a"]["x"];
if ($mv !== 7 || $mc !== 5) { return 5; }
$d dynamic = {a: {x: 5}}; $dcopy = $d;
$d["a"]["x"] += 2;
$dv int = $dcopy["a"]["x"];
if ($dv !== 7) { return 6; }
$text hash<string> = {x: "a"}; $text["x"] .= "b";
if ($text["x"] !== "ab") { return 7; }
return 0;
PHS;
compound_test_source($source);
$cpp = CPP_Probe::text(Model::$cpp_files);
$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->prepare(); $compiler->cpp();
if ($cpp !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Indexed compound no-op changed output'); }
file_put_contents($argv[1] . '/values.cpp', $cpp);

compound_test_source(<<<'PHS'
function side(int &$n): int { $n++; return 1; }
function missing(hash<int> &$v, int &$n): void { $v["x"] += side($n); }
function remove(hash<int> &$v): int { unset($v["x"]); return 1; }
function deleted(hash<int> &$v): void { $v["x"] += remove($v); }
function destroy(mixed &$v): int { $v["a"] = 5; return 1; }
function invalidated(mixed &$v): void { $v["a"]["x"] += destroy($v); }
function absent(mixed &$v, int &$n): void { $v["a"]["x"] .= side($n); }
function dynamic_remove(dynamic &$v): int { unset($v["x"]); return 1; }
function dynamic_deleted(dynamic &$v): void { $v["x"] += dynamic_remove($v); }
function size(hash<int> $v): int { return count($v); }
function child(mixed $v): int { return $v["a"]; }
function make(): mixed { return {a: {x: 10}}; }
function make_dynamic(): dynamic { return {x: 10}; }
function has(dynamic $v): bool { return isset($v["x"]); }
return 0;
PHS);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

int main() {
    scpp::hash_t<scpp::int_t<>> v; scpp::int_t<> n{0};
    try { function_missing(v, n); return 1; } catch (const std::exception&) {}
    if (n.native_value() != 0 || function_size(v).native_value() != 0) return 2;
    v.set(scpp::string_t("x"), scpp::int_t<>(10));
    try { function_deleted(v); return 3; } catch (const std::exception&) {}
    if (function_size(v).native_value() != 0) return 4;
    auto m = function_make();
    try { function_invalidated(m); return 5; } catch (const std::exception&) {}
    if (function_child(m).native_value() != 5) return 6;
    scpp::mixed_t absent;
    try { function_absent(absent, n); return 7; } catch (const std::exception&) {}
    if (n.native_value() != 0 || !absent.is_null().native_value()) return 8;
    auto d = function_makeU_dynamic();
    try { function_dynamicU_deleted(d); return 9; } catch (const std::exception&) {}
    return function_has(d).native_value() ? 10 : 0;
}
CPP;
file_put_contents($argv[1] . '/failures.cpp', $cpp);
foreach ([
    '$v vector<int> = [1]; $v[] += 1;',
    '$v vector<int> = [1]; $v[0]++;',
    'function f(const vector<int> &$v): void { $v[0] += 1; }',
    '$v vector<int> = [1]; $v[0] += ($new = 1);',
    '$v vector<uint32> = [1]; $v[0] += (uint32)1;',
] as $invalid) {
    $failed = false;
    try { compound_test_source($invalid); } catch (\RuntimeException $e) { $failed = true; }
    if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid indexed update accepted'); }
}
echo "Indexed compound checks passed\n";
