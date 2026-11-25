"""Explicit nested vector/hash annotations preserve membership and map key behavior."""
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
        'indexes.php': r'''namespace samples;
class Indexes {
    private array $folders /** vector<vector<int>> */ = [];
    private array $positions /** hash<int, int> */ = [];
    protected array $paths /** hash<int> */ = [];
    public function initialize(): void {
        $ids /** vector<int> */ = [];
        $ids[] = 12;
        $ids[] = 37;
        $this->folders[] = $ids;
        $this->positions[12] = 0;
        $this->positions[37] = 1;
        $this->paths["/root/a.phs"] = 12;
    }
    public function row(int $id): int { return $this->positions[$id]; }
    public function file(string $path): int { return $this->paths[$path]; }
    public function first(): int {
        $ids /** vector<int> */ = $this->folders[0];
        return $ids[0];
    }
    public function copy_edit(): int {
        $copy = $this->folders;
        $copy[0][0] = 99;
        $copy[0][] = 104;
        return q_count($copy[0]);
    }
}''',
        'main.php': r'''$index = new \samples\Indexes();
$index->initialize();
echo $index->row(37), ":", $index->file("/root/a.phs"), ":", $index->copy_edit(), ":", $index->first(), "\n";
$positions /** hash<int, int> */ = [];
$positions[42] = 2;
$positions[7] = 5;
$positions_copy = $positions;
$positions_copy[42] = 8;
echo q_count($positions), ":", $positions[42], ":", $positions_copy[42], ":", $positions[7], "\n";
$groups /** hash<vector<int>, string> */ = [];
$ids /** vector<int> */ = [];
$ids[] = 3;
$groups["folder"] = $ids;
$groups_copy = $groups;
$groups_copy["folder"][0] = 9;
echo $groups["folder"][0], ":", $groups_copy["folder"][0], "\n";
''',
    }
    for name, body in bodies.items():
        (source / name).write_text('<?php\n' + body + '\n')
    run(['php', TOOLS / 'sync_imports.php', source])
    run(['php', TOOLS / 'check.php', source])
    run(['php', TOOLS / 'convert.php', source, output])
    expected = '1:12:3:12\n2:2:8:5\n3:9\n'
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
    print('Nested vector/map declarations, private fields, keys and independent copies passed.')


if __name__ == '__main__':
    main()
