"""Enabled GTK/WebKitGTK provider acceptance; isolated display and local content."""
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
parser.add_argument('--native-only', action='store_true')
parser.add_argument('--integration', action='store_true')
parser.add_argument('--retained-source', type=Path)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)

with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    packages = ['gtk+-3.0', 'webkit2gtk-4.1']
    versions = runner.run('sdk-versions', ['pkg-config', '--modversion', *packages]).decode()
    flags = shlex.split(runner.run('sdk-flags', ['pkg-config', '--cflags', '--libs', *packages]).decode())

    def execute(label, executable, folder, expected=b'enabled-ok\n'):
        capture = 'import subprocess,sys; a=open(sys.argv[2],"wb"); b=open(sys.argv[3],"wb"); sys.exit(subprocess.run([sys.argv[1]],stdout=a,stderr=b).returncode)'
        stdout = folder / (label + '.stdout')
        runner.run(label + '-run', ['xvfb-run', '-a', '--server-args=-screen 0 1024x768x24',
            'dbus-run-session', '--', 'env', 'GDK_BACKEND=x11', 'python3', '-c', capture,
            executable, stdout, folder / (label + '.stderr')], cwd=folder, timeout=45)
        actual = stdout.read_bytes()
        assert actual == expected, actual

    def retained(folder):
        assert runner.run('retained-publish', ['php', APP / 'tests/ui_webview_retained.php', folder]) == b'retained-ok\n'
        assert runner.run('fresh-publish', ['php', APP / 'tests/ui_webview_retained.php', folder, '--fresh']) == b'fresh-ok\n'
        for version in ['edited', 'restored', 'fresh']:
            for phase in ['cold', 'warm']:
                label = 'retained-' + version + '-' + phase
                build = json.loads(runner.run(label + '-build', ['php', APP / 'tools/build_cpp.php',
                    folder / ('retained-' + version), folder / ('retained-cache-' + version)], timeout=600))
                if phase == 'warm':
                    assert build['compiled'] == 0 and not build['linked'], build
                execute(label, build['executable'], folder,
                    b'enabled-edited\n' if version == 'edited' else b'enabled-ok\n')

    if args.retained_source:
        run_tasks([Task('retained-gui', lambda: retained(args.retained_source.resolve()))])
        (out / 'summary.json').write_text(json.dumps(dict(passed=True, retained_gui=True,
            source=str(args.retained_source.resolve()), sdk_versions=versions)) + '\n')
        sys.exit(0)

    def native():
        executable = out / 'native-smoke'
        runner.run('native-build', ['clang++', '-std=c++23', '-I', ROOT / 'runtime/include',
            '-DSCPP_HAS_UI=1', '-DSCPP_UI_BACKEND_GTK=1', '-DSCPP_HAS_WEBVIEW=1',
            '-DSCPP_WEBVIEW_BACKEND_WEBKITGTK=1', ROOT / 'tests/native/webview_smoke_desktop.cpp',
            ROOT / 'runtime/include/modules/ui/ui.cpp', ROOT / 'runtime/include/modules/webview/webview.cpp',
            *flags, '-o', executable], timeout=300)
        runner.run('native-run', ['xvfb-run', '-a', '--server-args=-screen 0 1024x768x24',
            'dbus-run-session', '--', 'env', 'GDK_BACKEND=x11', executable], timeout=60)

    run_tasks([Task('native-provider', native)])
    if not args.native_only:
        page = '''<!doctype html><html><head><title>V-R08</title></head><body>local acceptance<script>
window.scpp.invoke('ok',{probe:7}).then(function(value){
    return window.scpp.invoke(value.answer===42?'ack-ok':'bad',null);
});
window.scpp.invoke('error',null).then(function(){window.scpp.invoke('bad',null);},function(error){
    window.scpp.invoke(error.code==='expected'&&error.message==='expected failure'?'ack-error':'bad',null);
});
</script></body></html>'''
        template = (APP / 'tests/fixtures/ui_webview/enabled.phs.in').read_text()

        def source_case(name):
            folder = out / name
            source = folder / 'source'
            source.mkdir(parents=True)
            assets = folder / 'assets'
            assets.mkdir()
            (assets / 'index.html').write_text(page)
            if name == 'html':
                load = '$result=webview_load_html($view,clear_view($view,' + json.dumps(page) + '));'
                load += 'if(!take($value,$error,$result)){return 21;}'
                load += 'if(take($value,$error,webview_load_html($view,"absent"))){return 20;}'
            elif name == 'app':
                load = 'if(!take($value,$error,webview_load_app($view,' + json.dumps(str(assets)) + '))){return 22;}'
            else:
                target = assets / ('missing.html' if name == 'failure' else 'index.html')
                load = 'if(!take($value,$error,webview_load_url($view,' + json.dumps(target.as_uri()) + '))){return 23;}'
            condition = '$ready&&$failed' if name == 'failure' else '$ready&&$started&&$finished&&$ok_ack&&$error_ack&&$eval_ack'
            program = template.replace('@@LOAD@@', load).replace('@@DONE@@', 'if(' + condition + '){break;}')
            program = program.replace('@@ASSERT@@', 'if(!(' + condition + ')){echo "incomplete events\\n";return 13;}')
            (source / 'main.phs').write_text(program)
            (source / 'prism.json').write_text(json.dumps(dict(entrypoint='main.phs',
                runtime=dict(languages=dict(php=dict(profile='strict')), modules=['webview', 'datetime']))))
            published = folder / 'published'
            publish = ['php', APP / 'main.php', '--project', source, '--output', published]
            runner.run(name + '-publish', publish)
            manifest = json.loads((published / '.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == 'forbidden'
            definitions = manifest['runtime_build']['definitions']
            assert 'SCPP_HAS_UI=1' in definitions and 'SCPP_HAS_WEBVIEW=1' in definitions, definitions
            for phase in ['cold', 'warm']:
                label = name + '-' + phase
                build = json.loads(runner.run(label + '-build', ['php', APP / 'tools/build_cpp.php',
                    published, folder / 'cache'], timeout=600))
                if phase == 'warm':
                    assert build['compiled'] == 0 and not build['linked'], build
                execute(label, build['executable'], folder)
        run_tasks([Task(name, lambda n=name: source_case(n)) for name in ['html', 'app', 'url', 'failure']])
        if args.integration:
            def disabled():
                shim = out / 'disabled-sdk-bin'
                shim.mkdir()
                pkg = shim / 'pkg-config'
                # Simulate absent GUI SDKs only; preserve ordinary package lookup.
                pkg.write_text('#!/bin/sh\ncase "$*" in *gtk+-3.0*|*webkit2gtk*) exit 1;; esac\nexec '
                    + shlex.quote(shutil.which('pkg-config')) + ' "$@"\n')
                pkg.chmod(0o755)
                runner.run('disabled-providers', ['env', 'PATH=' + str(shim) + ':' + os.environ['PATH'],
                    'python3', APP / 'tests/ui_webview.py', '--results', out / 'disabled-providers'], timeout=600)

            run_tasks([Task('retained-gui', lambda: retained(out / 'html')), Task('disabled-gui', disabled),
                Task('provider-cache', lambda: runner.run('provider-cache', ['python3',
                    APP / 'tests/runtime_module_build.py', '--results', out / 'provider-cache'], timeout=600))])
(out / 'summary.json').write_text(json.dumps(dict(passed=True, sdk_versions=versions,
    native_provider=True, source_cases=[] if args.native_only else ['html', 'app', 'url', 'failure'],
    default_prelowering=not args.native_only, integration=args.integration), indent=2) + '\n')
