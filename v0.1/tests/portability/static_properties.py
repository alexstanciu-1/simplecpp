"""Static fields share state across workers and preserve old roots after reset."""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--target-checkout', type=Path)
    args = parser.parse_args()
    results = args.results.resolve()
    results.mkdir(parents=True, exist_ok=False)
    source, output = results / 'php', results / 'phpp'
    source.mkdir()
    events = []

    def run(command, ok=True, cwd=ROOT):
        start = time.monotonic()
        p = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        events.append(dict(command=list(map(str, command)), cwd=str(cwd), exit=p.returncode,
                           seconds=round(time.monotonic() - start, 3), stdout=p.stdout, stderr=p.stderr))
        (results / 'commands.json').write_text(json.dumps(events, indent=2) + '\n')
        assert (p.returncode == 0) == ok, events[-1]
        return p.stdout + p.stderr

    bodies = {
        'model.php': r'''namespace demo;
final class Row { public int $value = 0; }
enum Phase { case ready; }
final class Model {
    public static int $count = 0;
    public static bool $active = false;
    public static string $label = 'idle';
    public static Phase $phase = Phase::ready;
    public static ?Row $root = null;
    public static array $rows /** vector<Row> */ = [];
    public static array $names /** hash<int> */ = [];
    private static int $generation = 0;
    protected static int $step = 1;
    public static function reset(): void {
        self::$count = 0;
        self::$active = false;
        self::$label = 'idle';
        $rows /** vector<Row> */ = [];
        $names /** hash<int> */ = [];
        self::$rows = $rows;
        self::$names = $names;
        self::$root = null;
        self::$generation = self::$generation + self::$step;
    }
    public static function restart(): void { self::reset(); }
    public static function current_generation(): int { return self::$generation; }
}
''',
        'worker.php': r'''namespace demo;
final class Worker {
    public function add(Row $row): void {
        Model::$rows[] = $row;
        Model::$names['last'] = Model::$count;
        Model::$root = $row;
        Model::$count = Model::$count + 1;
        Model::$active = true;
        Model::$label = 'loaded';
    }
    public function count(): int { return Model::$count; }
}
''',
        'main.php': r'''$first = new \demo\Worker();
$second = new \demo\Worker();
$row = new \demo\Row();
$row->value = 7;
$first->add($row);
$second->add($row);
echo $first->count(), ':', $second->count(), ':', \demo\Model::$names['last'], ':', \demo\Model::$label, ':', \demo\Model::$active ? 'yes' : 'no', "\n";
foreach (\demo\Model::$rows as $item) { $item->value = $item->value + 1; }
echo $row->value, ':', \demo\Model::$root === $row ? 'same' : 'bad', ':', \demo\Model::$phase === \demo\Phase::ready ? 'ready' : 'bad', "\n";
echo isset(\demo\Model::$names['last']) ? 'present' : 'bad', ":";
unset(\demo\Model::$names['last']);
echo isset(\demo\Model::$names['last']) ? 'bad' : 'removed', "\n";
\demo\Model::restart();
echo $second->count(), ':', \demo\Model::$root === null ? 'empty' : 'bad', ':', $row->value, ':', \demo\Model::current_generation(), ':', \demo\Model::$label, "\n";
$second->add($row);
echo $first->count(), ':', \demo\Model::$names['last'], "\n";
''',
    }
    for name, body in bodies.items():
        (source / name).write_text('<?php\n' + body + '\n')
    expected = '2:2:1:loaded:yes\n9:same:ready\npresent:removed\n0:empty:9:1:idle\n1:0\n'
    run(['php', TOOLS / 'sync_imports.php', source])
    php = run(['php', '-r', 'foreach (array_slice($argv,1) as $path) { require $path; }',
               TOOLS / 'runtime/bootstrap.php', *[source / name for name in bodies]])
    assert php == expected, php
    checkpoint = {name: hashlib.sha256((source / name).read_bytes()).hexdigest() for name in bodies}
    (results / 'php_checkpoint.json').write_text(json.dumps(checkpoint, indent=2) + '\n')
    run(['php', TOOLS / 'check.php', source])
    run(['php', TOOLS / 'convert.php', source, output])
    generated = (output / 'model.phs').read_text()
    assert 'public static int $count = 0;' in generated
    assert 'public static $rows vector<Row> = [];' in generated
    run(['php', TOOLS / 'convert.php', source, output, '--stats'])
    with tempfile.TemporaryDirectory(prefix='scpp-static-reject-') as temp:
        bad = Path(temp) / 'php'; bad.mkdir()
        cases = [
            'class Bad { public static array $rows = []; }',
            'class Bad { public static int $count = "wrong"; }',
            'class Bad { public static ?int $count = "wrong"; }',
            'class Bad { public static readonly int $count; }',
            'class Bad { public static int $count = 0; } Bad::${"count"} = 1;',
            '$name = "Bad"; $name::$count = 1;',
            'class Bad { public static function run(): void { static::$count = 1; } }',
            'class Bad { public static function run(): void { parent::$count = 1; } }',
            'class Bad { public static function run(): void { self::$callback(); } }',
        ]
        for body in cases:
            (bad / 'bad.php').write_text('<?php\n' + body + '\n')
            failure = run(['php', TOOLS / 'check.php', bad], ok=False)
            assert 'bad.php' in failure
            run(['php', TOOLS / 'convert.php', bad, Path(temp) / 'output'], ok=False)
            assert not (Path(temp) / 'output/bad.phs').exists()
    native = False
    if args.target_checkout:
        target = json.loads((ROOT / 'compiler/tools/portability_target.json').read_text())
        checkout = args.target_checkout.resolve()
        assert run(['git', '-C', checkout, 'rev-parse', 'HEAD']).strip() == target['verified_commit']
        assert run(['git', '-C', checkout, 'status', '--porcelain']).strip() == ''
        run(['php', TOOLS / 'install_native_runtime.php', output])
        cli = checkout / target['cli']
        run(['php', cli, 'init', '--php-profile=strict'], cwd=output)
        config_path = output / 'prism.json'
        config = json.loads(config_path.read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = []
        config_path.write_text(json.dumps(config, indent=2) + '\n')
        actual = run(['php', cli, 'run', '--build-runtime'], cwd=output)
        assert actual.endswith(expected), actual
        native = True
    (results / 'summary.json').write_text(json.dumps(dict(passed=True, native=native, expected=expected,
        target_revision=target['verified_commit'] if native else None), indent=2) + '\n')
    print('Static fields: sharing, typed containers, reset, retained identity and rejections passed.')


if __name__ == '__main__':
    main()
