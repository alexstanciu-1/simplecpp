"""Exact JSON scalar spelling and large-input native portability regression."""
import argparse
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    results = args.results.resolve()
    results.mkdir(parents=True, exist_ok=False)
    source = results / 'source'
    source.mkdir()
    project = results / 'phpp'
    tools = ROOT / 'tools/php_portability'
    lines = ['<?php']
    expected = []

    def literal(value):
        return "'" + value.replace('\\', '\\\\').replace("'", "\\'") + "'"

    def quote(expression, value):
        lines.append('echo json_quote(' + expression + '), "\\n";')
        expected.append(json.dumps(value, ensure_ascii=True).replace('/', '\\/').replace('\\u007f', '\x7f'))

    for value in ['', 'ASCII with $dollar / slash " and \\', 'é中😀',
                  '\u007f\u0080\u07ff\u0800\ud7ff\ue000\uffff\U00010000\U0010ffff',
                  'before\u2028\u2029after']:
        quote(literal(value), value)
    controls = ''.join(chr(code) for code in range(32))
    quote(' . '.join(f'string_byte_from_int({code})' for code in range(32)), controls)
    for codes in [[128], [192, 128], [224, 128, 128], [237, 160, 128],
                  [244, 144, 128, 128], [240, 159, 152], [255]]:
        expression = ' . '.join(f'string_byte_from_int({code})' for code in codes)
        lines.append('try { echo json_quote(' + expression + '); } '
                     'catch (\\JsonException $error) { echo $error->getCode(), ":", $error->getMessage(), "\\n"; }')
        expected.append('5:Malformed UTF-8 characters, possibly incorrectly encoded')
    # Doubling setup is cheap; time must be spent on quoting, not fixture building.
    for seed in ['generated_cpp_identifier_0123456789;', '/"\\é中😀']:
        lines.append('$large = ' + literal(seed) + ';')
        lines.append('for ($i = 0; $i < 15; $i++) { $large = $large . $large; }')
        quote('$large', seed * (2 ** 15))
    (source / 'main.php').write_text('\n'.join(lines) + '\n')
    expected_bytes = ('\n'.join(expected) + '\n').encode()
    with CommandRunner(results / 'logs', ROOT) as runner:
        runner.run('imports', ['php', tools / 'sync_imports.php', source])
        actual = runner.run('php', ['php', '-d', 'auto_prepend_file=' + str(tools / 'runtime/bootstrap.php'), source / 'main.php'])
        assert actual == expected_bytes, 'PHP oracle differs from independent spelling'
        runner.run('convert', ['php', tools / 'convert.php', source, project])
        runner.run('framework', ['php', tools / 'install_native_runtime.php', project])
        cli = ROOT / 'bin/scpp.php'
        runner.run('init', ['php', cli, 'init', '--php-profile=strict'], project)
        config_path = project / 'prism.json'
        config = json.loads(config_path.read_text())
        config['build']['cxx'] = 'clang++-18'
        config['runtime']['modules'] = []
        config_path.write_text(json.dumps(config, indent=2) + '\n')
        runner.run('build', ['php', cli, 'build', '--build-runtime', '--no-stan'], project, timeout=300)
        actual = runner.run('native', [project / '.prism/build/main'], project, timeout=30)
        assert actual == expected_bytes, 'Native JSON spelling mismatch'
    (results / 'summary.json').write_text(json.dumps({
        'passed': True, 'cases': len(expected), 'output_bytes': len(expected_bytes),
        'native_timeout_seconds': 30, 'stan': False,
    }, indent=2) + '\n')
    print(f'JSON quote PHP/native parity PASS ({len(expected)} cases, {len(expected_bytes)} bytes).')


if __name__ == '__main__':
    main()
