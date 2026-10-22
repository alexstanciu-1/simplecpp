"""UI/WebView source exposure and disabled-provider proof with default prelowering."""
import argparse
import json
import shutil
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

CASES = {
    'ui-only': '''
$app ui_app = null; $window ui_window = null; $event ui_event = null;
ui_app_create();ui_window_create($app,"test",20,20);ui_window_show($window);ui_window_close($window);
ui_app_poll($app);ui_app_next_event($app);ui_app_exit($app);
ui_event_type($event);ui_event_window($event);ui_event_text($event);ui_event_message($event);ui_event_url($event);
echo "ui-only";
''',
    'snapshots': '''
function replace(ui_app &$app):int{$app=probe_app("new");return 1;}
$app=probe_app("old");
if(!probe_snapshots($app,replace($app),$app)){return 2;}
$copy=$app;$app=null;
if(probe_label($copy)!=="new"){return 3;}
$error error;
if(take($copy,$error,ui_app_create())){return 4;}
if(probe_label($copy)!=="new"){return 5;}
echo "snapshots";
''',
    'all-api': '''
$app ui_app = null; $window ui_window = null; $event ui_event = null; $view webview = null;
ui_app_create(); ui_window_create($app,"test",320,200);
ui_window_show($window); ui_window_close($window);
ui_app_poll($app); ui_app_next_event($app); ui_app_exit($app);
ui_event_type($event); ui_event_window($event); ui_event_text($event);
ui_event_webview($event); ui_event_message($event); ui_event_url($event);
webview_create($window); webview_load_url($view,"about:blank");
webview_load_html($view,"<p>local</p>"); webview_load_app($view,"missing");
webview_eval($view,"void 0"); webview_reply_ok($view,1,"null");
webview_reply_error($view,2,"failed","message");
webview_message_id($event); webview_message_command($event); webview_message_payload_json($event);
webview_close($view); echo "all-api";
''',
    'failure': '''
$app ui_app = null; $error error;
if(take($app,$error,ui_app_create())){return 2;}
if($error->get_message()===""){return 3;}
$window ui_window = null;
if(take($window,$error,ui_window_create($app,"test",10,10))){return 4;}
$view webview = null;
if(take($view,$error,webview_create($window))){return 5;}
echo "failure";
''',
}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    metadata = out / 'metadata'
    shutil.copytree(ROOT / 'runtime/metadata/modules', metadata)
    funcs = [('probe_app', 'app', [('label','string')], 'ui_app'),
             ('probe_label', 'label', [('app','ui_app')], 'string'),
             ('probe_snapshots', 'snapshots', [('left','ui_app'),('effect','int'),('right','ui_app')], 'bool')]
    (metadata / 'ui_probe.json').write_text(json.dumps(dict(version=1, module='ui_probe', dependencies=['webview'],
        functions=[dict(id=n, name=n, parameters=[dict(name=p,type=t,passing='value') for p,t in args],returns=r) for n,_,args,r in funcs],
        cpp=[dict(id=n,symbol='ui_webview_probe::'+symbol,header=str(APP/'tests/fixtures/ui_webview/handles.hpp'),native_payloads=True) for n,symbol,_,_ in funcs])))
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def check(name, source):
            case = out / name
            (case / 'source').mkdir(parents=True)
            (case / 'source/main.phs').write_text(source)
            (case / 'source/prism.json').write_text(json.dumps(dict(entrypoint='main.phs',
                runtime=dict(languages=dict(php=dict(profile='strict')), modules=['ui' if name == 'ui-only' else 'webview']))))
            if name == 'snapshots':
                runner.run(name + '-publish', ['php', APP / 'tests/ui_webview_publish.php', case, metadata, 'ui_probe'])
            else:
                runner.run(name + '-publish', ['php', APP / 'main.php', '--project', case / 'source',
                           '--output', case / 'published'])
            manifest = json.loads((case / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == 'forbidden'
            requirements = manifest['runtime_build']
            assert 'SCPP_HAS_UI=0' in requirements['definitions']
            if name != 'ui-only':
                assert 'SCPP_HAS_WEBVIEW=0' in requirements['definitions']
            for temperature in ('cold', 'warm'):
                data = runner.run(name + '-' + temperature,
                    ['php', APP / 'tools/build_cpp.php', case / 'published', case / 'cache'], timeout=600)
                build = json.loads(data)
                if temperature == 'warm':
                    assert build['compiled'] == 0 and not build['linked'], build
                actual = runner.run(name + '-' + temperature + '-run', [build['executable']])
                assert actual == name.encode(), (name, actual)
            if name == 'all-api':
                (case / 'source/main.phs').write_text('ui_window_show(1);')
                runner.run(name + '-invalid', ['php', APP / 'main.php', '--project', case / 'source',
                    '--output', case / 'published'], expected=1)
                assert not (case / 'published/.scpp-output.status').read_text().startswith('scpp-output-v1:ready:')
                runner.run(name + '-invalid-build', ['php', APP / 'tools/build_cpp.php',
                    case / 'published', case / 'cache'], expected=1)
                (case / 'source/main.phs').write_text(source)
                runner.run(name + '-recover', ['php', APP / 'main.php', '--project', case / 'source',
                    '--output', case / 'published'])
                build = json.loads(runner.run(name + '-recover-build', ['php', APP / 'tools/build_cpp.php',
                    case / 'published', case / 'cache'], timeout=600))
                assert runner.run(name + '-recover-run', [build['executable']]) == name.encode()

        run_tasks([Task(name, lambda n=name, s=source: check(n, s)) for name, source in CASES.items()])
        run_tasks([Task('build-contracts', lambda: runner.run('build-contracts', ['php', APP / 'tests/runtime_build_contract.php'])),
                   Task('sdk-contracts', lambda: runner.run('sdk-contracts', ['php', APP / 'tests/ui_webview_contracts.php']))])
    print('UI/WebView disabled-provider source proofs passed')


if __name__ == '__main__':
    main()
