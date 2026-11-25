"""Project IO opt-in, defaults, rejection/recovery and same-cache selection checks."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
out = parser.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = out / 'source'
source.mkdir()
work = out / 'work'
work.mkdir()
(work / 'input.txt').write_text('payload')
io_source = '$h resource_handle;if(!take($h,io_open("input.txt","rb"))){return 1;}$text="";if(!take($text,io_read($h,7))){return 2;}io_close($h);echo $text;'
published = out / 'published'
observations = []
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def config(modules, program):
        runtime = dict(languages=dict(php=dict(profile='strict')))
        if modules is not None:
            runtime['modules'] = modules
        (source / 'prism.json').write_text(json.dumps(dict(entrypoint='main.phs', runtime=runtime)))
        (source / 'main.phs').write_text(program)

    def publish(label, expected=0):
        return runner.run(label, ['php', APP / 'main.php', '--project', source, '--output', published], expected=expected)

    def positive(label, modules, program, expected):
        config(modules, program)
        publish(label + '-publish')
        manifest = json.loads((published / '.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == 'forbidden'
        providers = manifest['runtime_build']['sources']
        assert providers.count('core/runtime.cpp') == 1 and len(providers) == len(set(providers)), providers
        for phase in ['cold', 'warm']:
            name = label + '-' + phase
            build = json.loads(runner.run(name, ['php', APP / 'tools/build_cpp.php', published, out / 'cache'], timeout=600))
            assert runner.run(name + '-run', [build['executable']], cwd=work) == expected
            if phase == 'warm':
                assert build['compiled'] == 0 and not build['linked'], build
            observations.append(dict(stage=name, build=build, providers=providers))

    def check():
        defaults = json.loads(runner.run('default-config', ['php', '-r',
            'require $argv[1]; echo json_encode(resolve_runtime_build_config([]));', ROOT / 'bin/project_services.php']))
        assert defaults['modules'] == ['json', 'filesystem', 'datetime'], defaults
        positive('io', ['io'], io_source, b'payload')
        config([], io_source)
        publish('without-io', 1)
        assert 'resource_handle' in (out / 'logs/without-io.stderr').read_text()
        assert not (published / '.scpp-output.status').read_text().startswith('scpp-output-v1:ready:')
        runner.run('unready-build', ['php', APP / 'tools/build_cpp.php', published, out / 'cache'], expected=1)
        positive('empty', [], 'echo "empty";', b'empty')
        config(['not_a_runtime_module'], 'echo "invalid";')
        publish('unknown-module', 1)
        assert 'Unsupported runtime module' in (out / 'logs/unknown-module.stderr').read_text()
        positive('defaults', None, 'echo fs_exists("input.txt") ? "default" : "bad";', b'default')
        positive('restored-io', ['io'], io_source, b'payload')
        (out / 'summary.json').write_text(json.dumps(dict(passed=True, observations=observations), indent=2) + '\n')
    run_tasks([Task('project-io-selection', check)])
