"""Integer method defaults: shared declarations, cross-file calls and native boundaries."""
import argparse
import hashlib
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks
from signed_integer_fields import snapshot


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    out = args.results.resolve(); out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'; source.mkdir()
    project = out / 'phpp'; tools = ROOT / 'tools/php_portability'; cli = ROOT / 'bin/scpp.php'
    model = '''<?php
interface NumberContract { public function read(int $value = 5): int; }
trait IntegerOperations {
    public function trait_value(int $value = -17): int { return $value; }
}
abstract class NumberBase {
    abstract public function abstract_value(int $value = +17): int;
    protected function protected_value(int $value = -3): int { return $value; }
    public function inherited_value(): int { return $this->protected_value(); }
}
class Numbers extends NumberBase implements NumberContract {
    use IntegerOperations;
    public function read(int $value = 5): int { return $value; }
    public function abstract_value(int $value = +17): int { return $value; }
    public static function combine(int $required, int $left = 0, int $right = -2): int { return $required + $left + $right; }
    private function private_value(int $value = -0): int { return $value; }
    public function private_default(): int { return $this->private_value(); }
    public static function through_interface(NumberContract $target): int { return $target->read(); }
    public static function through_base(NumberBase $target): int { return $target->abstract_value(); }
    public function unchanged(bool $flag = true, ?int $value = null): bool { return $flag && $value === null; }
}
class MismatchBase { public function read(int $value = 4): int { return $value; } }
class MismatchChild extends MismatchBase { public function read(int $value = 9): int { return $value; } }
class MismatchCaller { public static function read(MismatchBase $target): int { return $target->read(); } }
'''
    forms = [('zero', '0', 0), ('plus', '+17', 17), ('negative', '-17', -17),
             ('positive_zero', '+0', 0), ('negative_zero', '-0', 0), ('hexadecimal', '0xff', 255),
             ('octal', '010', 8), ('binary', '0b10', 2), ('separated', '1_000', 1000),
             ('maximum', '9223372036854775807', 9223372036854775807),
             ('negative_maximum', '-9223372036854775807', -9223372036854775807),
             ('comment', '- /* gap */ 3', -3)]
    model += 'class LiteralDefaults {\n'
    for name, literal, value in forms:
        model += f' public static function {name}(int $value = {literal}): int {{ return $value; }}\n'
    model += '}\n'
    (source / 'model.php').write_text(model)
    main_source = '''<?php
$n = new Numbers();
if ($n->read() !== 5) { throw new \\LogicException('instance omitted'); }
if ($n->read(0) !== 0) { throw new \\LogicException('explicit zero'); }
if ($n->read(-7) !== -7) { throw new \\LogicException('explicit negative'); }
if ($n->read(8) !== 8) { throw new \\LogicException('explicit positive'); }
if (Numbers::combine(10) !== 8) { throw new \\LogicException('two omitted'); }
if (Numbers::combine(10, 3) !== 11) { throw new \\LogicException('partial omission'); }
if (Numbers::combine(10, 3, 4) !== 17) { throw new \\LogicException('explicit all'); }
if ($n->trait_value() !== -17) { throw new \\LogicException('trait'); }
if ($n->inherited_value() !== -3) { throw new \\LogicException('protected'); }
if ($n->private_default() !== 0) { throw new \\LogicException('private'); }
if (Numbers::through_interface($n) !== 5) { throw new \\LogicException('interface'); }
if (Numbers::through_base($n) !== 17) { throw new \\LogicException('abstract'); }
if (!$n->unchanged()) { throw new \\LogicException('bool and nullable defaults'); }
'''
    for name, literal, value in forms:
        main_source += f'if (LiteralDefaults::{name}() !== {value}) {{ throw new \\LogicException("literal {name}"); }}\n'
    main_source += '''echo "method defaults PASS\\n";
$mismatch = new MismatchChild();
echo MismatchCaller::read($mismatch), "\\n";
'''
    (source / 'main.php').write_text(main_source)
    host = out / 'host.php'
    host.write_text('<?php\nrequire $argv[1]; require $argv[2]; require $argv[3];\n')
    command = ['php', tools / 'convert.php', source, project]
    with CommandRunner(out / 'logs', ROOT) as r:
        php = r.run('php', ['php', host, tools / 'runtime/bootstrap.php', source / 'model.php', source / 'main.php'])
        assert php == b'method defaults PASS\n9\n'
        r.run('check', ['php', tools / 'check.php', source], cli_reason='Checker exits the process')
        r.run('convert', command)
        phs = (project / 'model.phs').read_text()
        assert 'int $left = 0, int $right = -2' in phs
        assert 'int $value = -17' in phs and 'int $value = +17' in phs
        before = snapshot(project)
        assert json.loads(r.run('reuse', command))['reused'] == 2
        assert snapshot(project) == before
        invalid = ['true', "'1'", '1.5', 'null', 'LIMIT', 'answer()', '1 + 2', '--1', '+-1', '-0xff',
                   '-010', '-0b10', '-1_000', '-(1)', '-9223372036854775808', '9223372036854775808']
        bad_cases = [f'class Bad {{ public function f(int $value = {value}): int {{ return $value; }} }}' for value in invalid]
        bad_cases += ['class Bad { public function f(int $x = 0, int $y): int { return $y; } }',
                      'class Bad { public function f(?int $value = 0): void {} }',
                      'class Bad { public function __construct(public int $value = -1) {} }']
        for index, body in enumerate(bad_cases):
            (source / 'bad.php').write_text('<?php\n' + body + '\n')
            r.run(f'reject-{index}', command, expected=1, cli_reason='Converter rejection exits the process')
            diagnostic = (out / 'logs' / f'reject-{index}.stderr').read_text()
            assert 'bad.php:2:' in diagnostic, diagnostic
            assert snapshot(project) == before
        (source / 'bad.php').unlink()
        minimum = out / 'minimum.php'
        minimum.write_text('<?php class Minimum { public function f(int $value = -9223372036854775808): int { return $value; } }')
        r.run('php-minimum', ['php', minimum], expected=255, cli_reason='PHP invalid parameter default terminates the process')
        r.run('framework', ['php', tools / 'install_native_runtime.php', project])
        r.run('init', ['php', cli, 'init', '--php-profile=strict'], project, cli_reason='CLI project setup')
        config = json.loads((project / 'prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'; config['runtime']['modules'] = []
        (project / 'prism.json').write_text(json.dumps(config, indent=2) + '\n')
        r.run('build', ['php', cli, 'build', '--build-runtime', '--no-stan'], project,
              timeout=300, cli_reason='CLI native build')
        native = r.run('native', [project / '.prism/build/main'], project)
        # Default arguments follow the static C++ declaration; mismatch is explicit unsupported PHP parity.
        assert native == b'method defaults PASS\n4\n'
        (out / 'virtual-default-boundary.json').write_text(json.dumps(dict(php=9, native=4,
            supported_contract='Use matching defaults across overrides; differing defaults are not PHP-equivalent'), indent=2))
        header = (project / '.prism/generated/model.hpp').read_text()
        cpp = (project / '.prism/generated/model.cpp').read_text()
        assert 'left = static_cast<int_t<> >(0)' in header
        assert 'right = (-static_cast<int_t<> >(2))' in header
        signature = next(line for line in cpp.splitlines() if 'Numbers::combine(' in line)
        assert '=' not in signature, signature
        (out / 'model.hpp').write_text(header); (out / 'model.cpp').write_text(cpp)
        # Required first parameter remains required; structural conversion does not enforce call arity.
        (source / 'main.php').write_text('<?php\necho Numbers::combine();\n')
        r.run('missing-required-convert', command)
        r.run('missing-required-build', ['php', cli, 'build', '--no-stan'], project, expected=1,
              timeout=120, cli_reason='Native missing-argument rejection exits the process')
        diagnostic = (out / 'logs/missing-required-build.stdout').read_text() + (out / 'logs/missing-required-build.stderr').read_text()
        assert 'error:' in diagnostic and ('argument' in diagnostic or 'matching' in diagnostic), diagnostic
        def regression(name):
            r.run(name, ['python3', ROOT / 'tests/portability' / (name + '.py'), '--results', out / name], timeout=300)
        run_tasks([Task(name, lambda name=name: regression(name)) for name in
                   ['signed_integer_fields', 'method_signatures', 'nullable_parameters']], jobs=12)
        r.run('toolchain', ['clang++-18', '--version'])
    (out / 'summary.json').write_text(json.dumps(dict(passed=True, literal_cases=len(forms),
        structural_rejections=len(bad_cases), native_required_argument_rejection=True,
        virtual_default_mismatch=dict(php=9, native=4),
        source_hashes={p.name: hashlib.sha256(text.encode()).hexdigest() for p, text in
                       [(source / 'model.php', model), (source / 'main.php', main_source)]},
        legacy_signature_regressions='PHP and conversion; standalone fixture proves current native forms',
        stan='skipped'), indent=2) + '\n')
    print('Integer method defaults: PASS (virtual-default mismatch boundary recorded)')


if __name__ == '__main__':
    main()
