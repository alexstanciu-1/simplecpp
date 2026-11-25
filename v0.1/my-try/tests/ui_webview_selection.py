"""Actual GUI SDK selection transitions through one publication and build cache."""
import argparse
import json
import os
from pathlib import Path
import shlex
import shutil
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
(source / 'main.phs').write_text('$app ui_app=null;$error error;if(take($app,$error,ui_app_create())){echo "on";ui_app_exit($app);}else{echo "off";}')
(source / 'prism.json').write_text(json.dumps(dict(entrypoint='main.phs',
    runtime=dict(languages=dict(php=dict(profile='strict')), modules=['ui']))))
shim = out / 'disabled-sdk-bin'
shim.mkdir()
pkg = shim / 'pkg-config'
pkg.write_text('#!/bin/sh\ncase "$*" in *gtk+-3.0*|*webkit2gtk*) exit 1;; esac\nexec '
    + shlex.quote(shutil.which('pkg-config')) + ' "$@"\n')
pkg.chmod(0o755)
observations = []
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def check():
        first = None
        for stage, enabled in [('enabled', True), ('disabled', False), ('restored', True)]:
            command = ['php', APP / 'main.php', '--project', source, '--output', out / 'published']
            if not enabled:
                command = ['env', 'PATH=' + str(shim) + ':' + os.environ['PATH'], *command]
            runner.run(stage + '-publish', command)
            manifest = json.loads((out / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == 'forbidden'
            requirements = manifest['runtime_build']
            assert ('SCPP_HAS_UI=1' if enabled else 'SCPP_HAS_UI=0') in requirements['definitions']
            if stage == 'enabled':
                first = requirements
            elif stage == 'restored':
                assert requirements == first
            else:
                assert requirements != first
            for phase in ['cold', 'warm']:
                label = stage + '-' + phase
                build = json.loads(runner.run(label + '-build', ['php', APP / 'tools/build_cpp.php',
                    out / 'published', out / 'cache'], timeout=600))
                actual = runner.run(label + '-run', ['xvfb-run', '-a', 'env', 'GDK_BACKEND=x11', build['executable']])
                assert actual == (b'on' if enabled else b'off'), actual
                if phase == 'warm':
                    assert build['compiled'] == 0 and not build['linked'], build
                observations.append(dict(stage=stage, phase=phase, build=build, requirements=requirements))
        (out / 'summary.json').write_text(json.dumps(dict(passed=True, observations=observations), indent=2) + '\n')
    run_tasks([Task('sdk-selection', check)])
