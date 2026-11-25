"""Nullable diagnostic output through the actual JSON binding in both compiler routes."""
import argparse
import json
import shutil
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
out = parser.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = r'''function proof(): int {
$diagnostic ?json_parse_error = null;
$document json_document;
$failure error;
if (!take($document, $failure, json_document_parse("null", $diagnostic))) { return 1; }
if ($diagnostic !== null) { return 2; }
echo "success:";
if (take($document, $failure, json_document_parse('{"x":1,"x":2}', $diagnostic))) { return 3; }
$details json_parse_error = $diagnostic;
if ($diagnostic === null) { return 4; }
if ($details->byte_offset !== 7) { return 5; }
echo $details->category, ":";
$diagnostic = $details;
if (!take($document, $failure, json_document_parse("[]", $diagnostic))) { return 6; }
if ($diagnostic !== null) { return 7; }
echo "cleared:", $details->category;
return 0;
}
echo ":", proof();
'''
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        if mode == 'legacy':
            project = folder / 'source'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['runtime']['modules'] = ['json']
            config['build']['cxx'] = 'clang++-18'
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                ROOT / 'runtime/metadata/modules', 'json', mode])
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == b':success:duplicate_key:cleared:duplicate_key0'
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'seconds': r.seconds, 'passed': r.error is None} for r in results], indent=2) + '\n')
