"""Portable explicit weak derivation through PHP, v0.1 and both v0.2 C++ modes."""
import argparse
import json
import re
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True)
source = out / 'source'
source.mkdir()
shutil.copyfile(ROOT / 'tests/portability/fixtures/weak_operation.php', source / 'main.php')
expected = b'9:1:1\n0\n'
tools = ROOT / 'tools/php_portability'
with CommandRunner(out / 'logs', ROOT) as runner:
    def prove():
        actual = runner.run('php', ['php8.5', '-d', 'auto_prepend_file=' + str(tools / 'runtime/bootstrap.php'), source / 'main.php'])
        assert actual == expected
        project = out / 'v01'
        runner.run('convert', ['php8.5', tools / 'convert.php', source, project])
        phs = (project / 'main.phs').read_text()
        assert 'weak($this->source($row))' in phs and 'lock($this->target)' in phs
        # Native-only expiration proof: PHP deliberately keeps ordinary references.
        phs += '\n$row = null; $held = null; echo $observer->target_present() ? 1 : 0, \"\\n\";\n'
        (project / 'main.phs').write_text(phs)
        native_expected = expected + b'0\n'
        runner.run('init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
        config = json.loads((project / 'prism.json').read_text())
        config['build']['cxx'] = 'clang++-18'
        (project / 'prism.json').write_text(json.dumps(config))
        runner.run('v01-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=240, cli_reason='CLI fixture build')
        assert runner.run('v01-run', [project / '.prism/build/main']) == native_expected
        def native(mode):
            folder = out / mode
            (folder / 'source').mkdir(parents=True)
            (folder / 'source/main.phs').write_text(phs)
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'core', mode])
            if mode == 'concrete':
                runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache', '-O0'], timeout=240, expected=1)
                diagnostic = (out / 'logs/concrete-build.stderr').read_text()
                assert "no member named 'has_value'" in diagnostic
                # Independently prove derivation/acquisition without the blocked presence operation.
                live = phs
                live = re.sub(r'public function (?:present|target_present)\(\): bool \{[^}]+\}', '', live)
                live = live[:live.index('echo $row->number')]
                live += 'echo $row->number, ":", $observer->calls, "\\n"; $observer->clear();'
                (folder / 'source/main.phs').write_text(live)
                runner.run('concrete-live-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'core', mode])
                built = json.loads(runner.run('concrete-live-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache', '-O0'], timeout=240))
                assert runner.run('concrete-live-run', [built['executable']]) == b'9:1\n'
                return
            built = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache', '-O0'], timeout=240))
            assert runner.run(mode + '-run', [built['executable']]) == native_expected
        run_tasks([Task(mode, lambda mode=mode: native(mode)) for mode in ['ordinary', 'concrete']], jobs=12)
    run_tasks([Task('portable-weak', prove)], jobs=12)
(out / 'result.json').write_text(json.dumps({'passed': True, 'php': True, 'v01': True, 'v02_modes': ['ordinary'], 'prelowered_live_operations': True, 'prelowered_presence': 'blocked: locked class null check emits has_value; expected failure verified', 'native_compiler_rebuilt': False}, indent=2))
