"""Typed byte-preserving join: host contract, structural conversion and native parity."""
import argparse
import hashlib
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks


def literal(value):
    return "'" + value.replace('\\', '\\\\').replace("'", "\\'") + "'"


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', required=True, type=Path)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'
    source.mkdir()
    project = out / 'phpp'
    tools = ROOT / 'tools/php_portability'
    cases = [(',', [], b''), (',', ['one'], b'one'), (', ', ['a', 'b', 'c'], b'a, b, c'),
             ('|', ['', 'a', ''], b'|a|'), ('', ['a', '', 'b'], b'ab'),
             ('é', ['中', '😀'], '中é😀'.encode()), ('', [], b'')]
    lines = ['<?php']
    expected = b''
    for index, (separator, pieces, result) in enumerate(cases):
        lines.append(f'$pieces_{index} /** vector<string> */ = [];')
        for piece in pieces:
            lines.append(f'$pieces_{index}[] = {literal(piece)};')
        lines.append(f'echo q_implode({literal(separator)}, $pieces_{index}), "\n";')
        expected += result + b'\n'
    lines += ['$binary /** vector<string> */ = [];',
              '$binary[] = string_byte_from_int(0);', '$binary[] = string_byte_from_int(255);',
              'echo q_implode(string_byte_from_int(128), $binary), "\n";',
              'echo q_implode("", $binary), "\n";',
              'echo q_count($binary), ":", string_byte_at($binary[0], 0), ":", string_byte_at($binary[1], 0), "\n";']
    expected += b'\x00\x80\xff\n\x00\xff\n2:0:255\n'
    (source / 'main.php').write_text('\n'.join(lines) + '\n')
    (out / 'expected.bin').write_bytes(expected)
    host = out / 'host.php'
    host.write_text('''<?php
require $argv[1];
$bad = [[1 => 'x'], ['key' => 'x'], [1], [true], [null], [[]], [new stdClass()]];
foreach ($bad as $index => $pieces) {
    try { q_implode(',', $pieces); throw new LogicException('accepted invalid input'); }
    catch (InvalidArgumentException $error) {
        $expected = $index < 2 ? 'q_implode requires a list of strings' : 'q_implode requires string elements';
        if ($error->getMessage() !== $expected) { throw new LogicException('wrong diagnostic'); }
    }
}
$pieces = ['a', '', 'b']; $snapshot = $pieces;
if (q_implode('|', $pieces) !== 'a||b' || $pieces !== $snapshot) { throw new LogicException('mutation or output'); }
$function = new ReflectionFunction('q_implode');
if ($function->getNumberOfRequiredParameters() !== 2 || $function->getNumberOfParameters() !== 2) { throw new LogicException('wrong facade'); }
echo "host PASS\\n";
''')
    with CommandRunner(out / 'logs', ROOT) as runner:
        def run_host():
            assert runner.run('host-contract', ['php', host, tools / 'runtime/bootstrap.php']) == b'host PASS\n'
        def facade():
            runner.run('facade', ['php', tools / 'generate_global_functions.php', '--check'])
        run_tasks([Task('host', run_host), Task('facade', facade)], jobs=12)
        actual = runner.run('php', ['php', '-d', 'auto_prepend_file=' + str(tools / 'runtime/bootstrap.php'), source / 'main.php'])
        assert actual == expected
        command = ['php', tools / 'convert.php', source, project]
        runner.run('convert', command)
        phs = project / 'main.phs'
        text = phs.read_text()
        assert 'q_implode' not in text and text.count('implode(') == 9
        assert "implode(', ', $pieces_2)" in text and '$pieces_2 vector<string>' in text
        stamp = phs.stat().st_mtime_ns
        report = json.loads(runner.run('reuse', command))
        assert report['reused'] == 1 and phs.stat().st_mtime_ns == stamp
        for index, arguments in enumerate(['', "','", "',', $pieces, $pieces"]):
            (source / 'bad.php').write_text('<?php\n$pieces /** vector<string> */ = [];\nq_implode(' + arguments + ');\n')
            runner.run(f'arity-{index}', command, expected=1, cli_reason='Converter rejection exits the process')
            diagnostic = (out / 'logs' / f'arity-{index}.stderr').read_text()
            assert 'bad.php:3:' in diagnostic and 'wrong argument count' in diagnostic
            assert phs.read_text() == text and phs.stat().st_mtime_ns == stamp
        (source / 'bad.php').unlink()
        runner.run('framework', ['php', tools / 'install_native_runtime.php', project])
        cli = ROOT / 'bin/scpp.php'
        runner.run('init', ['php', cli, 'init', '--php-profile=strict'], project, cli_reason='CLI project setup')
        config_path = project / 'prism.json'
        config = json.loads(config_path.read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = []
        config_path.write_text(json.dumps(config, indent=2) + '\n')
        runner.run('build', ['php', cli, 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI native build')
        assert runner.run('native', [project / '.prism/build/main'], project) == expected
        cpp = (project / '.prism/generated/main.cpp').read_text()
        assert 'php::implode(string_t(", "), pieces_2)' in cpp
        (out / 'valid-generated.cpp').write_text(cpp)
        (out / 'valid-generated.phs').write_text(text)
        # A wrong typed vector reaches the target compiler; structural conversion is not type checking.
        (source / 'main.php').write_text('<?php\n$pieces /** vector<int> */ = [];\n$pieces[] = 1;\necho q_implode(",", $pieces);\n')
        runner.run('wrong-type-convert', command)
        runner.run('wrong-type-build', ['php', cli, 'build', '--no-stan'], project, expected=1, timeout=120, cli_reason='Build rejection exits the process')
        diagnostics = (out / 'logs/wrong-type-build.stderr').read_text() + (out / 'logs/wrong-type-build.stdout').read_text()
        assert "no matching function for call to 'implode'" in diagnostics
    (out / 'summary.json').write_text(json.dumps(dict(passed=True, valid_join_cases=9,
        host_invalid_cases=7, arity_rejections=3, native_type_rejections=1,
        source_sha256=hashlib.sha256(('\n'.join(lines) + '\n').encode()).hexdigest(),
        stan='skipped', output_bytes=len(expected)), indent=2) + '\n')
    print('Typed string join: PASS')


if __name__ == '__main__':
    main()
