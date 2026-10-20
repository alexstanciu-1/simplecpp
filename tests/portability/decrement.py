"""Decrement token admission, value semantics, single evaluation and native checks."""
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
    out = args.results.resolve(); out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'; source.mkdir()
    project = out / 'phpp'; tools = ROOT / 'tools/php_portability'; cli = ROOT / 'bin/scpp.php'
    lines = ['<?php']
    for index, start in enumerate([3, 0, -3]):
        for name, operator, expected in [('prefix', '--$value', start - 1), ('postfix', '$value--', start)]:
            variable = f'${name}_{index}'
            result = f'${name}_result_{index}'
            lines += [f'{variable} = {start};', f'{result} = {operator.replace("$value", variable)};',
                      f'if ({result} !== {expected}) {{ throw new \\LogicException("{name} result"); }}',
                      f'if ({variable} !== {start - 1}) {{ throw new \\LogicException("{name} mutation"); }}']
    lines += ['''class Counter { public int $value = 7; }
class Maker {
    public static int $calls = 0;
    public static function make(): Counter { self::$calls++; return new Counter(); }
}
$counter = new Counter();
$old_field = $counter->value--;
if ($old_field !== 7) { throw new \\LogicException('postfix field result'); }
if ($counter->value !== 6) { throw new \\LogicException('postfix field mutation'); }
$new_field = --$counter->value;
if ($new_field !== 5) { throw new \\LogicException('prefix field result'); }
$fresh = new Counter();
if ($fresh->value !== 7) { throw new \\LogicException('fresh field'); }
$old_receiver = Maker::make()->value--;
if ($old_receiver !== 7) { throw new \\LogicException('receiver postfix result'); }
if (Maker::$calls !== 1) { throw new \\LogicException('receiver repeated'); }
$new_receiver = --Maker::make()->value;
if ($new_receiver !== 6) { throw new \\LogicException('receiver prefix result'); }
if (Maker::$calls !== 2) { throw new \\LogicException('prefix receiver repeated'); }
$discarded = 2;
--$discarded;
$discarded--;
if ($discarded !== 0) { throw new \\LogicException('discarded statements'); }
$sum = 0;
for ($index = 3; $index > 0; $index--) { $sum += $index; }
if ($sum !== 6) { throw new \\LogicException('loop update'); }
$negative = -1;
$subtraction = 3 - -1;
if ($negative !== -1) { throw new \\LogicException('negative literal'); }
if ($subtraction !== 4) { throw new \\LogicException('separate minus tokens'); }
echo "decrement PASS\\n";
''']
    code = '\n'.join(lines)
    (source / 'main.php').write_text(code)
    command = ['php', tools / 'convert.php', source, project]
    with CommandRunner(out / 'logs', ROOT) as r:
        assert r.run('php', ['php', source / 'main.php']) == b'decrement PASS\n'
        r.run('check', ['php', tools / 'check.php', source], cli_reason='Checker exits the process')
        r.run('convert', command)
        phs = (project / 'main.phs').read_text()
        for spelling in ['--$prefix_0', '$postfix_0--', '$counter->value--', '--$counter->value',
                         'Maker::make()->value--', '--Maker::make()->value', '$index--', '3 - -1']:
            assert spelling in phs, spelling
        before = snapshot(project)
        assert json.loads(r.run('reuse', command))['reused'] == 1
        assert snapshot(project) == before
        bad_cases = ['$x = --1;', '$x = 1--;', 'class Bad { public int $value = --1; }',
                     'class Bad { public ?int $value = --1; }']
        for index, body in enumerate(bad_cases):
            (source / 'bad.php').write_text('<?php\n' + body + '\n')
            r.run(f'reject-{index}', command, expected=1, cli_reason='Converter rejection exits the process')
            diagnostic = (out / 'logs' / f'reject-{index}.stderr').read_text()
            assert 'bad.php' in diagnostic and (':2:' in diagnostic or 'line 2' in diagnostic), diagnostic
            assert snapshot(project) == before
        (source / 'bad.php').unlink()
        r.run('framework', ['php', tools / 'install_native_runtime.php', project])
        r.run('init', ['php', cli, 'init', '--php-profile=strict'], project, cli_reason='CLI project setup')
        config = json.loads((project / 'prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'; config['runtime']['modules'] = []
        (project / 'prism.json').write_text(json.dumps(config, indent=2) + '\n')
        r.run('build', ['php', cli, 'build', '--build-runtime', '--no-stan'], project,
              timeout=300, cli_reason='CLI native build')
        assert r.run('native', [project / '.prism/build/main'], project) == b'decrement PASS\n'
        cpp = (project / '.prism/generated/main.cpp').read_text()
        for spelling in ['(--prefix_0)', '(postfix_0--)', '(counter->value--)', '(--counter->value)',
                         '(Maker::make()->value--)', '(--Maker::make()->value)']:
            assert spelling in cpp, spelling
        (out / 'valid-generated.cpp').write_text(cpp)
        (out / 'valid-generated.phs').write_text(phs)
        # The structural converter admits syntax; the native type system owns operand validity.
        (source / 'main.php').write_text('<?php\n$text = "word";\n--$text;\n')
        r.run('wrong-type-check', ['php', tools / 'check.php', source], cli_reason='Checker exits the process')
        r.run('wrong-type-convert', command)
        r.run('wrong-type-build', ['php', cli, 'build', '--no-stan'], project, expected=1,
              timeout=120, cli_reason='Native invalid operand build exits the process')
        diagnostic = (out / 'logs/wrong-type-build.stdout').read_text() + (out / 'logs/wrong-type-build.stderr').read_text()
        assert 'error:' in diagnostic and ('--' in diagnostic or 'decrement' in diagnostic), diagnostic
        run_tasks([Task('signed-fields', lambda: r.run('signed-fields', ['python3', ROOT / 'tests/portability/signed_integer_fields.py',
                   '--results', out / 'signed-fields'], timeout=300))], jobs=12)
        r.run('toolchain', ['clang++-18', '--version'])
    (out / 'summary.json').write_text(json.dumps(dict(passed=True, local_value_cases=6,
        source_sha256=hashlib.sha256(code.encode()).hexdigest(), structural_rejections=len(bad_cases),
        native_type_rejections=1, signed_field_regression=True, stan='skipped'), indent=2) + '\n')
    print('Decrement: PASS')


if __name__ == '__main__':
    main()
