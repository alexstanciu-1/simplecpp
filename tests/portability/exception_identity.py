"""Exception fields, rooted declarations and catch/rethrow identity across native boundaries."""
import argparse
import json
from pathlib import Path
import shutil
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    results = args.results.resolve()
    results.mkdir(parents=True, exist_ok=False)
    source = results / 'source'
    source.mkdir()
    shutil.copy2(ROOT / 'tests/portability/fixtures/exception_identity.php', source / 'main.php')
    runner = CommandRunner(results / 'logs', ROOT)
    run = runner.run
    project = results / 'phpp'
    tools = ROOT / 'tools/php_portability'
    expected = 'same:distinct:static:optional:rethrow'

    def proof():
        run('php', ['php', '-r', 'require $argv[1]; require $argv[2];',
                           tools / 'runtime/bootstrap.php', source / 'main.php'])
        run('convert', ['php', tools / 'convert.php', source, project])
        converted = (project / 'main.phs').read_text()
        assert 'private \\scpp_portability_runtime_exception $signal;' in converted
        assert 'public static \\scpp_portability_runtime_exception $published;' in converted
        run('framework', ['php', tools / 'install_native_runtime.php', project])
        cli = ROOT / 'bin/scpp.php'
        run('init', ['php', cli, 'init', '--php-profile=strict'], project)
        config_path = project / 'prism.json'
        config = json.loads(config_path.read_text())
        config['runtime']['modules'] = ['compiler']
        config['build']['cxx'] = 'clang++-18'
        config_path.write_text(json.dumps(config, indent=2))
        run('build', ['php', cli, 'build', '--build-runtime', '--no-stan'], project, timeout=300)
        run('native', [project / '.prism/build/main'], project)
        assert (results / 'logs/php.stdout').read_text() == expected
        assert (results / 'logs/native.stdout').read_text() == expected
        (results / 'summary.json').write_text(json.dumps(dict(passed=True, expected=expected,
                                                            stan=False), indent=2))

    with runner:
        run_tasks([Task('exception_identity', proof)])


if __name__ == '__main__':
    main()
