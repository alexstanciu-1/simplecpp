"""Mapped global facade identity across namespaces, routes and conversion caches."""
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
(out / 'source/main.php').write_text('''<?php
namespace example;
echo q_strlen("abc"), ":", q_substr("abcd", 1), ":", q_substr("abcd", 1, 2);
''')
# Controlled lookup fixture. Actual full facade dependencies are exercised by the
# compiler bootstrap; these definitions isolate binding identity from body blockers.
helpers = '''function scpp_portability_strlen(string $text): int { return string_utf8_codepoint_count($text); }
function scpp_portability_substr_to_end(string $text, int $offset): string { return substr($text, $offset); }
function scpp_portability_substr(string $text, int $offset, int $length): string { return substr($text, $offset, $length); }
'''
expected = b'3:bcd:bc'
with CommandRunner(out / 'logs', ROOT) as runner:
    runner.run('imports', ['php8.5', TOOLS / 'sync_imports.php', out / 'source'])
    assert runner.run('host', ['php8.5', '-r', 'require $argv[1]; require $argv[2];',
                             TOOLS / 'runtime/bootstrap.php', out / 'source/main.php']) == expected
    old = out / 'old-converter'
    old.mkdir()
    shutil.copytree(TOOLS / 'src', old / 'src')
    shutil.copyfile(TOOLS / 'convert.php', old / 'convert.php')
    # Reproduce the previous unrooted map without requiring repository history.
    (old / 'function_map.php').write_text((TOOLS / 'function_map.php').read_text().replace(r"'\\scpp_portability_", "'scpp_portability_"))
    def convert(route):
        folder = out / route
        runner.run(route + '-old', ['php8.5', old / 'convert.php', out / 'source', folder, '--target=' + route])
        before = (folder / 'main.phs').read_text()
        assert 'echo scpp_portability_strlen(' in before
        result = json.loads(runner.run(route + '-new', ['php8.5', TOOLS / 'convert.php', out / 'source', folder, '--target=' + route]))
        assert result['converted'] == 1, result
        text = (folder / 'main.phs').read_text()
        for name in ['strlen', 'substr_to_end', 'substr']:
            assert '\\scpp_portability_' + name + '(' in text
        result = json.loads(runner.run(route + '-warm', ['php8.5', TOOLS / 'convert.php', out / 'source', folder, '--target=' + route]))
        assert result['reused'] == 1 and result['converted'] == 0, result
        (folder / 'helpers.phs').write_text(helpers)
        (folder / 'shadow.phs').write_text('namespace example; function scpp_portability_strlen(string $text): int { return 999; }')
    run_tasks([Task(route, lambda route=route: convert(route)) for route in ['v0.1', 'v0.2']])
    def execute(mode):
        if mode == 'legacy':
            project = out / 'v0.1'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['build']['cxx'] = 'clang++-18'
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            folder = out / mode
            (folder / 'source').mkdir(parents=True)
            for path in (out / 'v0.2').glob('*.phs'):
                shutil.copyfile(path, folder / 'source' / path.name)
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'strings', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: execute(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps({'scope':'lookup fixture, not full facade body admission',
    'php':'passed', 'cache':'old map invalidated; warm reuse verified for both targets',
    'native':[{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results]}, indent=2) + '\n')
