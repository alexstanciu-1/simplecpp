"""Keyed presence and by-value iteration preserve typed-map and vector behavior."""
import argparse
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--target-checkout', type=Path)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    results = args.results.resolve()
    results.mkdir(parents=True, exist_ok=False)
    source, output = results / 'php', results / 'phpp'
    source.mkdir()
    events = []

    def run(command, ok=True, cwd=ROOT):
        p = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        events.append({'command': list(map(str, command)), 'exit': p.returncode,
                       'stdout': p.stdout, 'stderr': p.stderr})
        (results / 'commands.json').write_text(json.dumps(events, indent=2) + '\n')
        assert (p.returncode == 0) == ok, (command, p.stdout, p.stderr)
        return p.stdout

    bodies = {
        'row.php': r'''namespace samples;
class Row { public int $id = 0; }
class Lookup {
    private array $positions /** hash<int, int> */ = [];
    public function put(int $id, int $position): void { $this->positions[$id] = $position; }
    public function contains(Row $row): bool { return isset($this->positions[$row->id]); }
}''',
        'main.php': r'''$map /** hash<int, int> */ = [];
$map[12] = 0;
$map[37] = 5;
echo isset($map[12]) ? "zero-present" : "bad", ":", isset($map[91]) ? "bad" : "missing", ":", q_count($map), "\n";
$sum = 0;
foreach ($map as $id => $position) { $sum = $sum + $id + $position; }
echo $sum, ":", q_count($map), "\n";
$paths /** hash<int> */ = [];
$paths["/a"] = 12;
$paths["/b"] = 37;
$path_sum = 0;
foreach ($paths as $path => $id) {
    if (isset($paths[$path])) { $path_sum = $path_sum + $id; }
}
echo $path_sum, ":", isset($paths["/missing"]) ? "bad" : "missing", ":", q_count($paths), "\n";
$values /** vector<int> */ = [];
$values[] = 4;
$values[] = 7;
$values[] = 9;
foreach ($values as $value) { $value = 100; }
$weighted = 0;
foreach ($values as $index => $value) {
    if ($index === 1) { continue; }
    $weighted = $weighted + $value;
    if ($index === 2) { break; }
}
echo $values[0], ":", $values[1], ":", $weighted, "\n";
$rows /** vector<\samples\Row> */ = [];
$row = new \samples\Row();
$row->id = 12;
$rows[] = $row;
foreach ($rows as $record) { $record->id = 37; }
$lookup = new \samples\Lookup();
$lookup->put(37, 0);
echo $row->id, ":", $lookup->contains($row) ? "found" : "bad", "\n";
$flags /** hash<bool> */ = [];
$flags["false"] = false;
echo isset($flags["false"]) ? "false-present\n" : "bad\n";
$empty /** vector<int> */ = [];
$visits = 0;
foreach ($empty as $item) { $visits = $visits + 1; }
echo $visits, "\n";
''',
    }
    for name, body in bodies.items():
        (source / name).write_text('<?php\n' + body + '\n')
    run(['php', TOOLS / 'sync_imports.php', source])
    run(['php', TOOLS / 'check.php', source])
    run(['php', TOOLS / 'convert.php', source, output])
    for generated in output.rglob('*.phs'):
        assert not __import__('re').search(r'/\*\*\s*(?:vector|hash|nullable)<', generated.read_text()), generated
    expected = 'zero-present:missing:2\n54:2\n49:missing:2\n4:7:13\n37:found\nfalse-present\n0\n'
    php = run(['php', '-r', 'foreach (array_slice($argv,1) as $path) { require $path; }',
               TOOLS / 'runtime/bootstrap.php', *[source / name for name in bodies]])
    assert php == expected, php
    run(['php', TOOLS / 'install_native_runtime.php', output])
    if args.target_checkout:
        target = json.loads((ROOT / 'compiler/tools/portability_target.json').read_text())
        checkout = args.target_checkout.resolve()
        assert run(['git', '-C', checkout, 'rev-parse', 'HEAD']).strip() == target['verified_commit']
        assert run(['git', '-C', checkout, 'status', '--porcelain']).strip() == ''
        cli = checkout / target['cli']
        run(['php', cli, 'init', '--php-profile=strict'], cwd=output)
        config_path = output / 'prism.json'
        config = json.loads(config_path.read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = []
        config_path.write_text(json.dumps(config, indent=2) + '\n')
        native = run(['php', cli, 'run', '--build-runtime'], cwd=output)
        assert native.endswith(expected), native
    (results / 'summary.json').write_text(json.dumps({'passed': True, 'native': bool(args.target_checkout),
                                                    'expected': expected,
                                                    'target_revision': target['verified_commit'] if args.target_checkout else None}, indent=2) + '\n')
    print('Typed map probes and by-value iteration proofs passed.')


if __name__ == '__main__':
    main()
