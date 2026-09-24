"""Explicit nullable parameters preserve absence, omission and present empty values."""
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
interface Payload {}
final class Row implements Payload { public int $value = 7; }
interface Optional_Check {
    public function present(?Payload $value = null): bool;
}
trait Nullable_Operations {
    public function required_nullable(?int $value): int {
        $out = 42;
        if (take_nullable($out, $value)) { return $out; }
        return 99;
    }
}
final class Holder implements Optional_Check {
    use Nullable_Operations;
    public ?Row $token = null;
    public function attach(?Row $row = null): void { $this->token = $row; }
    public function present(?Payload $value = null): bool { return $value === null ? false : true; }
    public static function text(?string $value = null): string {
        $out = 'fallback';
        if (take_nullable($out, $value)) { return $out; }
        return 'absent';
    }
    public function flag(?bool $value = null): string {
        $out = true;
        if (take_nullable($out, $value)) { return $out ? 'true' : 'false'; }
        return 'absent';
    }
    public static function as_real(float $value): float { return $value; }
    public function real(?float $value = null): bool { return $value === null; }
}
''',
        'main.php': r'''$holder = new \demo\Holder();
$row = new \demo\Row();
$payload /** \demo\Payload */ = $row;
echo $holder->token === null ? 'empty' : 'bad', ':', $holder->present() ? 'bad' : 'absent', "\n";
$holder->attach($row);
echo $holder->token === $row ? 'same' : 'bad', ':', $holder->present($payload) ? 'present' : 'bad', "\n";
$holder->attach();
echo $holder->token === null ? 'cleared' : 'bad', ':', $row->value, "\n";
$holder->attach($row);
$holder->attach(null);
echo $holder->token === null ? 'cleared' : 'bad', "\n";
echo $holder->required_nullable(null), ':', $holder->required_nullable(0), ':', $holder->required_nullable(5), "\n";
echo \demo\Holder::text(), ':', \demo\Holder::text(null), ':', \demo\Holder::text(''), ':', \demo\Holder::text('yes'), "\n";
echo $holder->flag(), ':', $holder->flag(false), ':', $holder->flag(true), "\n";
echo $holder->real() ? 'absent' : 'bad', ':', $holder->real(\demo\Holder::as_real(0)) ? 'bad' : 'present', "\n";
''',
    }
    for name, body in bodies.items():
        (source / name).write_text('<?php\n' + body + '\n')
    expected = 'empty:absent\nsame:present\ncleared:7\ncleared\n99:0:5\nabsent:absent::yes\nabsent:false:true\nabsent:present\n'
    run(['php', TOOLS / 'sync_imports.php', source])
    php = run(['php', '-r', 'foreach (array_slice($argv,1) as $path) { require $path; }',
               TOOLS / 'runtime/bootstrap.php', *[source / name for name in bodies]])
    assert php == expected, php
    checkpoint = {name: hashlib.sha256((source / name).read_bytes()).hexdigest() for name in bodies}
    (results / 'php_checkpoint.json').write_text(json.dumps(checkpoint, indent=2) + '\n')
    run(['php', TOOLS / 'check.php', source])
    run(['php', TOOLS / 'convert.php', source, output])
    generated = (output / 'model.phs').read_text()
    assert '$row nullable<Row> = null' in generated
    assert '$value nullable<int>' in generated
    run(['php', TOOLS / 'convert.php', source, output, '--stats'])
    with tempfile.TemporaryDirectory(prefix='scpp-nullable-parameter-reject-') as temp:
        bad = Path(temp) / 'php'; bad.mkdir()
        cases = [
            'class Bad { public function f(int $x = null): void {} }',
            'class Bad { public function f(?int $x = 0): void {} }',
            'class Bad { public function f(?int $x = null, int $y): void {} }',
            'class Bad { public function f(?array $x = null): void {} }',
            'class Bad { public function f(?int &$x): void {} }',
            'class Bad { public function f(?int ...$x): void {} }',
            'class Bad { public function f(?mixed $x): void {} }',
            'class Bad { public function f(int|string $x): void {} }',
            'class Bad { public function f(?int $x = 1 + 2): void {} }',
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
    print('Nullable parameters: omission, explicit null, present values, interface/trait methods and reset passed.')


if __name__ == '__main__':
    main()
