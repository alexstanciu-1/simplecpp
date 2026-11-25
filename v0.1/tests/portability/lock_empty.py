"""Portable empty lock factory and token lifecycle across PHP and native routes."""
import argparse
import json
import shutil
import sys
from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
TOOLS = ROOT / 'tools/php_portability'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
(out / 'source').mkdir(parents=True, exist_ok=False)
source = r'''<?php
$handle = lock_empty();
$empty_alias = $handle;
lock_release($handle); lock_release($handle);
echo $handle === $empty_alias ? 'empty:' : 'bad:';
try { lock_try($handle, "", false); } catch (\RuntimeException $error) { echo 'path:'; }
echo $handle === $empty_alias ? 'preserved:' : 'bad:';
echo lock_try($handle, "__LOCK_PATH__", false) ? 'locked:' : 'bad:';
lock_release($empty_alias);
$alias = $handle;
$other = lock_empty();
echo lock_try($other, "__LOCK_PATH__", false) ? 'bad:' : 'contended:';
$moved = lock_transfer($handle);
lock_release($alias);
echo lock_try($other, "__LOCK_PATH__", true) ? 'bad:' : 'transfer-held:';
lock_release($moved); lock_release($moved);
echo lock_try($other, "__LOCK_PATH__", true) ? 'shared:' : 'bad:';
$reader = lock_empty();
echo lock_try($reader, "__LOCK_PATH__", true) ? 'pair:' : 'bad:';
lock_release($other); lock_release($reader);
try { lock_transfer($moved); } catch (\RuntimeException $error) { echo 'released'; }
'''
(out / 'source/main.php').write_text(source.replace('__LOCK_PATH__', str(out / 'host.lock')))
expected = b'empty:path:preserved:locked:contended:transfer-held:shared:pair:released'
with CommandRunner(out / 'logs', ROOT) as runner:
    assert runner.run('host', ['php8.5', '-r', 'require $argv[1]; require $argv[2];', TOOLS / 'runtime/bootstrap.php', out / 'source/main.php']) == expected
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-convert', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-framework', ['php8.5', TOOLS / 'install_native_runtime.php', out / route, '--os'])
    def check(mode):
        if mode == 'legacy':
            project = out / 'v0.1'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['runtime']['modules'] = ['filesystem', 'process']
            main = project / 'main.phs'
            main.write_text(main.read_text().replace(str(out / 'host.lock'), str(out / 'legacy.lock')))
            config['build']['cxx'] = 'clang++-18'
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            folder = out / mode
            (folder / 'source').mkdir(parents=True)
            (folder / 'source/main.phs').write_text((out / 'v0.2/main.phs').read_text().replace(str(out / 'host.lock'), str(out / (mode + '.lock'))))
            shutil.copyfile(out / 'v0.2/scpp_framework/file_locks.phs', folder / 'source/file_locks.phs')
            shutil.copyfile(out / 'v0.2/scpp_framework/exceptions.phs', folder / 'source/exceptions.phs')
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'filesystem', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
