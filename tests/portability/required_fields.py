"""Required fields retain explicit types and are populated before publication."""
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
enum Phase { case ready; }
final class Row { public int $value; }
final class Record {
    public int $offset;
    public bool $complete;
    public string $label;
    public Phase $phase;
    public Row $row;
    public array $rows /** vector<Row> */;
    public array $lookup /** hash<int> */;
    private int $secret;
    protected string $prefix;
    public float $ratio;
    public function __construct(int $secret, string $prefix) {
        $this->secret = $secret;
        $this->prefix = $prefix;
    }
    public function populate(Row $row, float $ratio): void {
        $this->offset = 12;
        $this->complete = true;
        $this->label = 'source';
        $this->phase = Phase::ready;
        $this->row = $row;
        $rows /** vector<Row> */ = [];
        $rows[] = $row;
        $this->rows = $rows;
        $lookup /** hash<int> */ = [];
        $lookup['row'] = 0;
        $this->lookup = $lookup;
        $this->ratio = $ratio;
    }
    public function status(): string { return $this->prefix . ':' . $this->secret; }
}
final class Model {
    public static Record $root;
    public static int $generation;
    public static array $rows /** vector<Record> */;
    public static array $by_name /** hash<int> */;
    public static function publish(Record $record, int $generation): void {
        self::$root = $record;
        self::$generation = $generation;
        $rows /** vector<Record> */ = [];
        $rows[] = $record;
        self::$rows = $rows;
        $by_name /** hash<int> */ = [];
        $by_name['current'] = 0;
        self::$by_name = $by_name;
    }
}
''',
        'main.php': r'''$row = new \demo\Row();
$row->value = 7;
$record = new \demo\Record(3, 'ok');
$record->populate($row, 2);
\demo\Model::publish($record, 1);
echo $record->offset, ':', $record->complete ? 'ready' : 'bad', ':', $record->label, ':', $record->status(), "\n";
echo $record->phase === \demo\Phase::ready ? 'phase' : 'bad', ':', $record->row === $row ? 'same' : 'bad', ':', $record->rows[0]->value, ':', $record->lookup['row'], "\n";
$old = \demo\Model::$root;
$next = new \demo\Record(3, 'ok');
$next->populate($row, 3);
\demo\Model::publish($next, 2);
echo $old === $record ? 'retained' : 'bad', ':', \demo\Model::$root === $next ? 'replaced' : 'bad', ':', \demo\Model::$generation, ':', \demo\Model::$by_name['current'], "\n";
foreach (\demo\Model::$rows as $published) { echo $published === $next ? 'published' : 'bad', "\n"; }
echo $record->ratio < $next->ratio ? 'float' : 'bad', "\n";
''',
    }
    for name, body in bodies.items():
        (source / name).write_text('<?php\n' + body + '\n')
    expected = '12:ready:source:ok:3\nphase:same:7:0\nretained:replaced:2:0\npublished\nfloat\n'
    run(['php', TOOLS / 'sync_imports.php', source])
    php = run(['php', '-r', 'foreach (array_slice($argv,1) as $path) { require $path; }',
               TOOLS / 'runtime/bootstrap.php', *[source / name for name in bodies]])
    assert php == expected, php
    checkpoint = {name: hashlib.sha256((source / name).read_bytes()).hexdigest() for name in bodies}
    (results / 'php_checkpoint.json').write_text(json.dumps(checkpoint, indent=2) + '\n')
    run(['php', TOOLS / 'check.php', source])
    run(['php', TOOLS / 'convert.php', source, output])
    generated = (output / 'model.phs').read_text()
    assert 'public Row $row;' in generated
    assert 'public static Record $root;' in generated
    assert 'public $rows vector<Row>;' in generated
    assert 'nullable<' not in generated
    run(['php', TOOLS / 'convert.php', source, output, '--stats'])
    with tempfile.TemporaryDirectory(prefix='scpp-required-field-reject-') as temp:
        bad = Path(temp) / 'php'; bad.mkdir()
        cases = [
            'class Bad { public $value; }',
            'class Bad { public mixed $value; }',
            'class Bad { public object $value; }',
            'class Bad { public array $value; }',
            'class Bad { public array $value /** Storage<Row, false> */; }',
            'class Bad { public ?Row $value; }',
            'class Bad { public ?array $value /** vector<Row> */; }',
            'class Bad { public int|string $value; }',
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
    print('Required fields: explicit types, publication, static replacement and retained identity passed.')


if __name__ == '__main__':
    main()
