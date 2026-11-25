"""Typed full-separator splitting: byte expectations, conversion and native parity."""
import argparse
import hashlib
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler


def literal(value):
    return "'" + value.replace('\\', '\\\\').replace("'", "\\'") + "'"


def snapshot(folder):
    return {str(p.relative_to(folder)): (p.read_bytes(), p.stat().st_mtime_ns)
            for p in folder.rglob('*') if p.is_file()}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', required=True, type=Path)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'
    source.mkdir()
    project = out / 'phpp'
    tools = ROOT / 'tools/php_portability'
    cases = [(',', '', ['']), (',', 'abc', ['abc']), (',', 'a,b', ['a', 'b']),
             (',', ',a', ['', 'a']), (',', 'a,', ['a', '']), (',', 'a,,b', ['a', '', 'b']),
             (',', ',,', ['', '', '']), ('::', 'a::::b::', ['a', '', 'b', '']),
             ('aa', 'aaaaa', ['', '', 'a']), ('é', '中é😀é', ['中', '😀', ''])]
    expressions = [(literal(sep), literal(value), [p.encode() for p in parts]) for sep, value, parts in cases]
    expressions += [('string_byte_from_int(0)', 'string_byte_from_int(0) . string_byte_from_int(255) . string_byte_from_int(0)', [b'', b'\xff', b'']),
                    ('string_byte_from_int(128) . string_byte_from_int(0)', 'string_byte_from_int(255) . string_byte_from_int(128) . string_byte_from_int(0) . string_byte_from_int(128)', [b'\xff', b'\x80'])]
    lines = ['<?php']
    expected = b''
    for i, (separator, value, parts) in enumerate(expressions):
        lines += [f'$separator_{i} = {separator};', f'$input_{i} = {value};',
                  f'$pieces_{i} /** vector<string> */ = q_explode($separator_{i}, $input_{i});',
                  f'echo q_count($pieces_{i}), ":";',
                  f'foreach ($pieces_{i} as $part_{i}) {{ echo string_byte_len($part_{i}), ":", $part_{i}, ";"; }}',
                  'echo "\\n";',
                  f'if ($separator_{i} !== ({separator}) || $input_{i} !== ({value})) {{ throw new \\LogicException(\'Input changed\'); }}']
        expected += str(len(parts)).encode() + b':' + b''.join(str(len(p)).encode() + b':' + p + b';' for p in parts) + b'\n'
    text_source = '\n'.join(lines) + '\n'
    (source / 'main.php').write_text(text_source)
    (out / 'expected.bin').write_bytes(expected)
    host = out / 'host.php'
    host.write_text('''<?php
require $argv[1];
$function = new ReflectionFunction('q_explode');
if ($function->getNumberOfRequiredParameters() !== 2 || $function->getNumberOfParameters() !== 2
    || (string)$function->getReturnType() !== 'array') { throw new LogicException('Wrong facade'); }
foreach ($function->getParameters() as $parameter) {
    if ((string)$parameter->getType() !== 'string') { throw new LogicException('Wrong argument type'); }
}
foreach (['', 'abc'] as $input) {
    $failed = false;
    try { q_explode('', $input); }
    catch (ValueError $error) {
        if ($error->getMessage() !== 'explode(): Argument #1 ($separator) must not be empty') { throw new LogicException('Wrong PHP error'); }
        $failed = true;
    }
    if (!$failed) { throw new LogicException('Empty separator accepted'); }
}
echo "host PASS\\n";
''')
    with CommandRunner(out / 'logs', ROOT) as runner:
        def host_contract():
            assert runner.run('host-contract', ['php', host, tools / 'runtime/bootstrap.php']) == b'host PASS\n'
        run_tasks([Task('host-contract', host_contract),
                   Task('facade', lambda: runner.run('facade', ['php', tools / 'generate_global_functions.php', '--check']))], jobs=12)
        actual = runner.run('php', ['php', '-d', 'auto_prepend_file=' + str(tools / 'runtime/bootstrap.php'), source / 'main.php'])
        assert actual == expected
        original = snapshot(source)
        command = ['php', tools / 'convert.php', source, project]
        runner.run('convert', command)
        assert snapshot(source) == original
        phs = (project / 'main.phs').read_text()
        assert 'q_explode' not in phs and phs.count('explode(') == len(expressions)
        assert '$pieces_0 vector<string> = explode($separator_0, $input_0)' in phs
        before = snapshot(project)
        report = json.loads(runner.run('reuse', command))
        assert report['reused'] == 1 and snapshot(project) == before
        for i, arguments in enumerate(['', "','", "',', 'a,b', 2"]):
            (source / 'bad.php').write_text('<?php\nq_explode(' + arguments + ');\n')
            runner.run(f'arity-{i}', command, expected=1, cli_reason='Converter rejection exits the process')
            diagnostic = (out / 'logs' / f'arity-{i}.stderr').read_text()
            assert 'bad.php:2:' in diagnostic and 'wrong argument count' in diagnostic
            assert snapshot(project) == before
        (source / 'bad.php').unlink()
        runner.run('framework', ['php', tools / 'install_native_runtime.php', project])
        cli = ROOT / 'bin/scpp.php'
        runner.run('init', ['php', cli, 'init', '--php-profile=strict'], project, cli_reason='CLI project setup')
        config = json.loads((project / 'prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = []
        (project / 'prism.json').write_text(json.dumps(config, indent=2) + '\n')
        runner.run('build', ['php', cli, 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI native build')
        assert runner.run('native', [project / '.prism/build/main'], project) == expected
        cpp = (project / '.prism/generated/main.cpp').read_text()
        assert 'php::explode(separator_0, input_0)' in cpp and 'vector_t<string_t> pieces_0' in cpp
        (out / 'valid-generated.cpp').write_text(cpp)
        (out / 'valid-generated.phs').write_text(phs)
        compiler = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True, cxx='clang++-18')
        compiler.compile('native-empty-compile', ROOT / 'tests/portability/fixtures/string_split_error.cpp', out / 'empty-error')
        runner.run('native-empty-error', [out / 'empty-error'])
        (source / 'main.php').write_text('<?php\n$values /** vector<int> */ = [1];\n$parts /** vector<string> */ = q_explode(",", $values);\necho q_count($parts);\n')
        runner.run('wrong-type-convert', command)
        runner.run('wrong-type-build', ['php', cli, 'build', '--no-stan'], project, expected=1, timeout=120, cli_reason='Build rejection exits the process')
        diagnostics = (out / 'logs/wrong-type-build.stderr').read_text() + (out / 'logs/wrong-type-build.stdout').read_text()
        assert "no matching function for call to 'explode'" in diagnostics
    (out / 'summary.json').write_text(json.dumps(dict(passed=True, valid_cases=len(expressions),
        arity_rejections=3, native_type_rejections=1, empty_separator_cases_per_layer=2,
        portable_value_error_catch='not supported; separate host and raw-native probes',
        source_sha256=hashlib.sha256(text_source.encode()).hexdigest(), stan='skipped'), indent=2) + '\n')
    print('Typed string split: PASS')


if __name__ == '__main__':
    main()
