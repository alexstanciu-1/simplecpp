"""Strict null presence across concrete/runtime/source handles without payload truth conversion."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def check_function(name, type_name):
    return f'''function {name}({type_name} $v,bool $present):bool{{
        return (($v!==null)===$present)&&((null!==$v)===$present)
            &&(($v===null)===!$present)&&((null===$v)===!$present);
    }}\n'''


CONTROLS = check_function('optional_ok', '?int') + check_function('owner_ok', 'Box') + '''
class Box { public int $value=0; }
function once(int &$count):?int{$count++;return 0;}
function make_box(int &$count):Box{$count++;return new Box();}
$n ?int=null;if(!optional_ok($n,false)){return 1;}
$n=0;if(!optional_ok($n,true)){return 2;}
$count=0;if(null===once($count)||$count!==1){return 3;}
if(once($count)===null||$count!==2){return 4;}
$box Box=null;if(!owner_ok($box,false)){return 5;}
$box=new Box();$copy=$box;$box=null;
if(!owner_ok($box,false)||!owner_ok($copy,true)){return 6;}
if(make_box($count)===null||$count!==3){return 7;}
$unique unique<Box> = new Box();
if($unique===null||null===$unique||!($unique!==null)||!(null!==$unique)){return 8;}
$moved=move($unique);
if($unique!==null||null!==$unique||!($unique===null)||!(null===$unique)){return 9;}
if($moved===null){return 10;}
echo "controls-ok";
'''
IO = check_function('handle_ok', 'resource_handle') + '''
function clear_handle(resource_handle &$h,int &$count):resource_handle{$saved=$h;$h=null;$count++;return $saved;}
$h resource_handle=null;if(!handle_ok($h,false)){return 1;}
if(!take($h,io_open("input.txt","rb"))){return 2;}
$copy=$h;if(!handle_ok($h,true)){return 3;}
$count=0;if(clear_handle($h,$count)===null||$count!==1||!handle_ok($h,false)){return 4;}
$h=$copy;if(null===clear_handle($h,$count)||$count!==2){return 5;}
if(!io_close($copy)||!handle_ok($copy,true)){return 6;}
$copy=null;if(!handle_ok($copy,false)){return 8;}
echo "io-ok";
'''
GUI = ''.join(check_function('check_' + kind, kind) for kind in ['ui_app', 'ui_window', 'ui_event', 'webview']) + '''
function clear_app(ui_app &$app,int &$count):ui_app{$saved=$app;$app=null;$count++;return $saved;}
$app ui_app=null;$window ui_window=null;$view webview=null;$event ui_event=null;
$error error;$value=false;
if(!check_ui_app($app,false)||!check_ui_window($window,false)||!check_webview($view,false)||!check_ui_event($event,false)){return 1;}
if(!take($app,$error,ui_app_create())){return 2;}
$saved_app=$app;
try {
 if(!take($window,$error,ui_window_create($app,"presence",200,100))){return 3;}
 if(!take($view,$error,webview_create($window))){return 4;}
 if(!check_ui_app($app,true)||!check_ui_window($window,true)||!check_webview($view,true)){return 5;}
 for($turn=0;$turn<20;$turn++){ui_app_poll($app);$event=ui_app_next_event($app);if($event!==null){break;}dt_sleep_ms(10);}
 if(!check_ui_event($event,true)){return 6;}
 $event_copy=$event;$event=null;if(!check_ui_event($event,false)||!check_ui_event($event_copy,true)){return 7;}
 $count=0;if(clear_app($app,$count)===null||$count!==1||!check_ui_app($app,false)){return 8;}
 $app=$saved_app;if(null===clear_app($app,$count)||$count!==2){return 9;}
 $view_copy=$view;$view=null;if(!check_webview($view,false)||!check_webview($view_copy,true)){return 10;}
 webview_close($view_copy);if(!check_webview($view_copy,true)){return 11;}
 if(!take($value,$error,ui_window_close($window))||!check_ui_window($window,true)){return 12;}
 ui_app_exit($saved_app);if(!check_ui_app($saved_app,true)){return 13;}
 $view_copy=null;$window=null;$saved_app=null;
 if(!check_webview($view_copy,false)||!check_ui_window($window,false)||!check_ui_app($saved_app,false)){return 14;}
 echo "gui-ok";
} finally {webview_close($view);ui_window_close($window);ui_app_exit($saved_app);}
'''

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
parser.add_argument('--case', action='append', dest='selected')
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def check(name, program, modules, expected):
        folder = out / name
        source = folder / 'source'
        source.mkdir(parents=True)
        (source / 'main.phs').write_text(program)
        (source / 'prism.json').write_text(json.dumps(dict(entrypoint='main.phs',
            runtime=dict(languages=dict(php=dict(profile='strict')), modules=modules))))
        (folder / 'input.txt').write_text('payload')
        published = folder / 'published'
        runner.run(name + '-publish', ['php', APP / 'main.php', '--project', source, '--output', published])
        assert json.loads((published / '.scpp-output.manifest').read_text())['application_templates'] == 'forbidden'
        for phase in ['cold', 'warm']:
            label = name + '-' + phase
            build = json.loads(runner.run(label + '-build', ['php', APP / 'tools/build_cpp.php', published, folder / 'cache'], timeout=600))
            if name == 'gui':
                capture = 'import subprocess,sys; a=open(sys.argv[2],"wb"); b=open(sys.argv[3],"wb"); sys.exit(subprocess.run([sys.argv[1]],stdout=a,stderr=b).returncode)'
                runner.run(label + '-run', ['xvfb-run', '-a', 'env', 'GDK_BACKEND=x11',
                    'python3', '-c', capture, build['executable'], folder / (phase + '.stdout'),
                    folder / (phase + '.stderr')], cwd=folder, timeout=45)
                actual = (folder / (phase + '.stdout')).read_bytes()
            else:
                actual = runner.run(label + '-run', [build['executable']], cwd=folder)
            assert actual == expected.encode(), actual
            if phase == 'warm':
                assert build['compiled'] == 0 and not build['linked'], build
    run_tasks([Task(name, lambda n=name, s=program, m=modules, e=expected: check(n,s,m,e))
        for name,program,modules,expected in [('controls',CONTROLS,[],'controls-ok'),
            ('io',IO,['io'],'io-ok'),('gui',GUI,['webview','datetime'],'gui-ok')]
        if not args.selected or name in args.selected])
(out / 'summary.json').write_text(json.dumps(dict(passed=True, cases=args.selected or ['controls','io','gui'],default_prelowering=True))+'\n')
