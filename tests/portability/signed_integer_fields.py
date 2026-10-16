"""Signed field literals: structural boundaries and executable PHP/native parity."""
import argparse
import hashlib
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks


def snapshot(folder):
    return {str(p.relative_to(folder)): (p.read_bytes(), p.stat().st_mtime_ns)
            for p in folder.rglob('*') if p.is_file()}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'; source.mkdir()
    project = out / 'phpp'
    tools = ROOT / 'tools/php_portability'
    code = '''<?php
class SignedFields {
    public int $negative = -1;
    protected int $protected_value = - /* gap */ 17;
    private int $private_value = +17;
    public int $negative_zero = -0;
    public int $positive_zero = +0;
    public int $maximum = +9223372036854775807;
    public int $negative_maximum = -9223372036854775807;
    public ?int $optional = -17;
    public ?int $optional_zero = +0;
    public ?int $absent = null;
    public static int $shared_value = -17;
    public static ?int $shared_optional = +17;
    public int $observed = 0;
    public function __construct() {
        $this->observed = $this->negative;
        $this->negative = 9;
    }
    public function hidden_sum(): int { return $this->protected_value + $this->private_value; }
}
class FreshField { public int $position = -1; }
$a = new SignedFields();
if ($a->negative !== 9) { throw new \\LogicException('override'); }
if ($a->observed !== -1) { throw new \\LogicException('default before constructor'); }
if ($a->hidden_sum() !== 0) { throw new \\LogicException('visibility'); }
if ($a->negative_zero !== 0) { throw new \\LogicException('negative zero'); }
if ($a->positive_zero !== 0) { throw new \\LogicException('positive zero'); }
if ($a->maximum !== 9223372036854775807) { throw new \\LogicException('maximum'); }
if ($a->negative_maximum !== -9223372036854775807) { throw new \\LogicException('negative maximum'); }
if ($a->optional !== -17) { throw new \\LogicException('optional negative'); }
if ($a->optional_zero !== 0) { throw new \\LogicException('optional zero'); }
if ($a->absent !== null) { throw new \\LogicException('absent'); }
if (SignedFields::$shared_value !== -17) { throw new \\LogicException('static default'); }
if (SignedFields::$shared_optional !== 17) { throw new \\LogicException('nullable static default'); }
SignedFields::$shared_value = 4;
$b = new SignedFields();
if (SignedFields::$shared_value !== 4) { throw new \\LogicException('static reset'); }
$first = new FreshField();
$first->position = 7;
$second = new FreshField();
if ($first->position !== 7) { throw new \\LogicException('mutation'); }
if ($second->position !== -1) { throw new \\LogicException('fresh default'); }
echo "signed fields PASS\\n";
'''
    (source / 'main.php').write_text(code)
    command = ['php', tools / 'convert.php', source, project]
    with CommandRunner(out / 'logs', ROOT) as r:
        assert r.run('php', ['php', source / 'main.php']) == b'signed fields PASS\n'
        token_probe = out / 'tokens.php'
        token_probe.write_text('''<?php
$values = ['9223372036854775807', '-9223372036854775807', '-9223372036854775808', '9223372036854775808'];
$result = ['int_size' => PHP_INT_SIZE, 'tokens' => []];
foreach ($values as $value) {
    $result['tokens'][$value] = array_map(fn($t) => is_array($t) ? [token_name($t[0]), $t[1]] : $t, token_get_all('<?php ' . $value));
}
echo json_encode($result);
''')
        tokens = json.loads(r.run('tokens', ['php', token_probe]))
        assert tokens['int_size'] == 8, 'This boundary proof requires a 64-bit PHP host'
        assert tokens['tokens']['-9223372036854775808'][-1][0] == 'T_DNUMBER'
        (out / 'tokens.json').write_text(json.dumps(tokens, indent=2))
        r.run('convert', command)
        phs = (project / 'main.phs').read_text()
        for expected in ['public int $negative = -1;', 'protected int $protected_value = -17;',
                         'private int $private_value = +17;', '$optional nullable<int> = -17;',
                         'public static int $shared_value = -17;', 'public int $position = -1;']:
            assert expected in phs, expected
        assert phs.count('function __construct(') == 1
        before = snapshot(project)
        reuse = json.loads(r.run('reuse', command))
        assert reuse['reused'] == 1 and snapshot(project) == before
        bad_values = ['-', '--1', '+-1', '-1.5', '-1e2', '-0xff', '-077', '-0b1', '-1_000',
                      '-(1)', '(-1)', '-LIMIT', '-answer()', '-1 + 2', '-9223372036854775808',
                      '+9223372036854775808', '9223372036854775808']
        rejections = [f'class Bad {{ public {kind} $value = {value}; }}'
                      for kind in ['int', '?int'] for value in bad_values]
        rejections += ['class Bad { public int $value /** uint32 */ = -1; }',
                       'class Bad { public readonly int $value = -1; }',
                       'class Bad { public function __construct(public int $value = -1) {} }',
                       'class Bad { public function f(int $value = -1): void {} }',
                       '/** @scpp-struct */ class Bad { public int $value = -1; }']
        for i, body in enumerate(rejections):
            (source / 'bad.php').write_text('<?php\n' + body + '\n')
            r.run(f'reject-{i}', command, expected=1, cli_reason='Converter rejection exits the process')
            diagnostic = (out / 'logs' / f'reject-{i}.stderr').read_text()
            assert 'bad.php:2:' in diagnostic, diagnostic
            assert snapshot(project) == before, body
        (source / 'bad.php').unlink()
        minimum = out / 'minimum.php'
        minimum.write_text('<?php class Minimum { public int $value = -9223372036854775808; } $x = new Minimum();')
        r.run('php-minimum', ['php', minimum], expected=255, cli_reason='PHP invalid typed property terminates the process')
        # Preserve existing unsigned spelling and neighboring field families.
        unchanged = out / 'unchanged'; unchanged.mkdir()
        (unchanged / 'main.php').write_text('''<?php
class Existing {
 public int $zero = 0;
 public int $positive = 17;
 public int $hex = 0xff;
 public int $separated = 1_000;
 public int $required;
 public ?int $absent = null;
 public bool $flag = false;
 public string $label = 'ok';
 public int $compact /** uint32 */ = 4294967295;
}
''')
        r.run('unchanged', ['php', tools / 'convert.php', unchanged, out / 'unchanged-phpp'])
        old = (out / 'unchanged-phpp/main.phs').read_text()
        assert '0xff' in old and '1_000' in old and 'uint32 = 4294967295' in old
        run_tasks([Task('nullable-fields', lambda: r.run('nullable-fields', ['python3', ROOT / 'tests/portability/nullable_fields.py']))], jobs=12)
        r.run('framework', ['php', tools / 'install_native_runtime.php', project])
        cli = ROOT / 'bin/scpp.php'
        r.run('init', ['php', cli, 'init', '--php-profile=strict'], project, cli_reason='CLI project setup')
        config_path = project / 'prism.json'; config = json.loads(config_path.read_text())
        config['build']['cxx'] = 'clang++-18'; config['runtime']['modules'] = []
        config_path.write_text(json.dumps(config, indent=2) + '\n')
        r.run('build', ['php', cli, 'build', '--build-runtime', '--no-stan'], project,
              timeout=300, cli_reason='CLI native build')
        assert r.run('native', [project / '.prism/build/main'], project) == b'signed fields PASS\n'
        generated = project / '.prism/generated'
        cpp = '\n'.join(p.read_text() for p in generated.rglob('*') if p.suffix in ['.cpp', '.hpp'])
        (out / 'generated.txt').write_text(cpp)
        assert 'int_t<> position = (-static_cast<int_t<> >(1));' in cpp
        assert 'nullable<int_t<>> optional = (-static_cast<int_t<> >(17));' in cpp
        assert 'int_t<> SignedFields::shared_value = (-static_cast<int_t<> >(17));' in cpp
        assert 'nullable<int_t<>> SignedFields::shared_optional = (+static_cast<int_t<> >(17));' in cpp
    (out / 'summary.json').write_text(json.dumps(dict(passed=True, rejections=len(rejections),
        php_int_size=tokens['int_size'], source_sha256=hashlib.sha256(code.encode()).hexdigest(),
        stan='skipped'), indent=2) + '\n')
    print('Signed integer fields: PASS')


if __name__ == '__main__':
    main()
