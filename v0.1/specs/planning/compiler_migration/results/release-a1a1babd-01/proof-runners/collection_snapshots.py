"""Owned collection versions preserve old rows through explicit replacement copying."""
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
        'row.php': r'''namespace snapshots;
class Row {
    public int $value = 0;
    public string $label = "";
    public array $tags /** vector<int> */ = [];
    public function copy_row(): Row {
        $copy = new Row();
        $copy->value = $this->value;
        $copy->label = $this->label;
        $copy->tags = $this->tags;
        return $copy;
    }
}''',
        'store.php': r'''namespace snapshots;
class Store {
    public array $rows /** vector<Row> */ = [];
    public function shallow_copy(): Store {
        $next = new Store();
        $next->rows = $this->rows;
        return $next;
    }
    public function with_value(int $index, int $value): Store {
        $next = $this->shallow_copy();
        $row = $this->rows[$index]->copy_row();
        $row->value = $value;
        $next->rows[$index] = $row;
        return $next;
    }
}''',
        'main.php': r'''$first = new \snapshots\Row();
$first->value = 10;
$first->label = "original";
$first->tags[] = 3;
$second = new \snapshots\Row();
$second->value = 20;
$base = new \snapshots\Store();
$base->rows[] = $first;
$base->rows[] = $second;
$next = $base->with_value(0, 11);
$latest = $next->with_value(0, 12);
echo $base->rows[0]->value, ":", $next->rows[0]->value, ":", $latest->rows[0]->value, "\n";
echo $base !== $next ? "owner-distinct" : "bad", ":", $base->rows[0] !== $next->rows[0] ? "changed-distinct" : "bad", ":", $base->rows[1] === $next->rows[1] ? "unchanged-shared" : "bad", "\n";
$next->rows[0]->tags[0] = 8;
$next->rows[0]->tags[] = 9;
$next->rows[0]->label = "edited";
echo $base->rows[0]->tags[0], ":", $next->rows[0]->tags[0], ":", $latest->rows[0]->tags[0], ":", count($base->rows[0]->tags), ":", count($next->rows[0]->tags), ":", $base->rows[0]->label, ":", $latest->rows[0]->label, "\n";
$next->rows[] = new \snapshots\Row();
echo count($base->rows), ":", count($next->rows), ":", count($latest->rows), "\n";
// Separate witness: shallow membership copies do not freeze shared rows.
$shallow = $base->shallow_copy();
$shallow->rows[1]->value = 99;
echo $base->rows[1]->value, ":", $latest->rows[1]->value, "\n";
''',
    }
    for name, body in bodies.items():
        (source / name).write_text('<?php\n' + body + '\n')
    run(['php', TOOLS / 'sync_imports.php', source])
    run(['php', TOOLS / 'check.php', source])
    run(['php', TOOLS / 'convert.php', source, output])
    expected = '10:11:12\nowner-distinct:changed-distinct:unchanged-shared\n3:8:3:1:2:original:original\n2:3:2\n99:99\n'
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
    print('Owned collection membership, explicit row copying and snapshot identity proofs passed.')


if __name__ == '__main__':
    main()
