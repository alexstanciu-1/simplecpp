"""Direct typed JSON ingestion: current-checkout runtime and portable PHP/native proof."""
import argparse
import json
from pathlib import Path
import shutil
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
expected = b'json:false:0:-2:9223372036854775807:-9223372036854775808\nreader-ok\n'
with CommandRunner(output / 'logs', ROOT) as commands:
    def runtime(name):
        executable = output / name
        commands.run(name + '-compile', ['clang++-18', '-std=c++23', '-pthread',
            '-I', ROOT / 'runtime/include', ROOT / f'tests/runtime/native/{name}.cpp',
            ROOT / 'runtime/include/modules/json/document.cpp',
            ROOT / 'runtime/include/core/runtime.cpp', '-o', executable])
        commands.run(name + '-execute', [executable])

    def portable():
        source = output / 'source'
        source.mkdir()
        shutil.copyfile(ROOT / 'tests/portability/fixtures/json_reader.php', source / 'main.php')
        commands.run('host', ['php', '-r', 'require $argv[1]; require $argv[2];',
            ROOT / 'tools/php_portability/runtime/bootstrap.php', source / 'main.php'])
        assert (output / 'logs/host.stdout').read_bytes() == expected
        project = output / 'phpp'
        commands.run('convert', ['php', ROOT / 'tools/php_portability/convert.php', source, project],
            cli_reason='Converter command exit contract')
        commands.run('framework', ['php', ROOT / 'tools/php_portability/install_native_runtime.php', project, '--json'],
            cli_reason='Framework installer command exit contract')
        (project / 'prism.json').write_text(json.dumps({
            'entrypoint': 'main.phs', 'build': {'mode': 'debug', 'cxx': 'clang++-18'},
            'runtime': {'modules': ['compiler', 'json'], 'languages': {'php': {'profile': 'strict'}}},
        }))
        commands.run('build', ['php', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'],
            cwd=project, timeout=300, cli_reason='Native CLI build and exit contract')
        commands.run('native', [project / '.prism/build/main'], cwd=project)
        assert (output / 'logs/native.stdout').read_bytes() == expected

    results = run_tasks([
        Task('document-regression', lambda: runtime('test_json_document')),
        Task('reader-protocol', lambda: runtime('test_json_pull_reader')),
        Task('typed-php-native', portable),
    ])
    (output / 'summary.json').write_text(json.dumps([
        dict(name=row.name, seconds=row.seconds, passed=row.error is None) for row in results
    ], indent=2))
