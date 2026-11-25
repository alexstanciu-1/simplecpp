"""Explicit method types preserve object identity and mutation across PHP/native execution."""
import argparse
import json
from pathlib import Path
import subprocess
import tempfile

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
        'record.php': 'namespace data; class Record { public int $value = 1; }',
        'operations.php': '''namespace workers;
trait Operations {
    public function change(\\data\\Record $row, int $amount): void {
        $row->value = $row->value + $amount;
        return;
    }
    public function identity(\\data\\Record $row): \\data\\Record { return $row; }
}''',
        'worker.php': '''namespace workers;
class Worker {
    use Operations;
    public static function pass(\\data\\Record $row): \\data\\Record { return $row; }
    public function number(float $value): float { return $value; }
    private function local(Worker $other): Worker { return $other; }
    public function same(Worker $other): Worker { return $this->local($other); }
}
interface Contract { public function identity(\\data\\Record $row): \\data\\Record; public function change(\\data\\Record $row, int $amount): void; }
''',
        'main.php': '''$row = new \\data\\Record();
$other = new \\data\\Record();
$worker = new \\workers\\Worker();
$alias = $worker->identity($row);
$worker->change($alias, 4);
$returned = \\workers\\Worker::pass($row);
echo $row->value, ":", $other->value, ":", $returned === $row ? "same" : "different", ":", $worker->same($worker) === $worker ? "same" : "different", ":", $worker->number(2) === $worker->number(2) ? "number" : "bad", "\\n";''',
    }
    for name, body in bodies.items():
        (source / name).write_text('<?php\n' + body + '\n')
    run(['php', TOOLS / 'sync_imports.php', source])
    run(['php', TOOLS / 'check.php', source])
    run(['php', TOOLS / 'convert.php', source, output])
    expected = '5:1:same:same:number\n'
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
    with tempfile.TemporaryDirectory(prefix='scpp-signature-reject-') as temp:
        src = Path(temp) / 'php'; src.mkdir()
        for signature in ['f($x): int', 'f(array $x): int', 'f(int &$x): int',
                          'f(int ...$x): int', 'f(int $x = 1): int',
                          'f(?\\data\\Record $x): int', 'f(int|string $x): int',
                          'f(int $x): mixed', 'f(int $x): self']:
            (src / 'bad.php').write_text('<?php\nclass Bad { public function ' + signature + ' { return 1; } }\n')
            run(['php', TOOLS / 'sync_imports.php', src])
            run(['php', TOOLS / 'check.php', src], ok=False)
            run(['php', TOOLS / 'convert.php', src, Path(temp) / 'out'], ok=False)
    (results / 'summary.json').write_text(json.dumps({'passed': True, 'native': bool(args.target_checkout),
                                                    'expected': expected,
                                                    'target_revision': target['verified_commit'] if args.target_checkout else None}, indent=2) + '\n')
    print('Typed method identity, mutation, void, float and rejection proofs passed.')


if __name__ == '__main__':
    main()
