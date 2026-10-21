"""Project composition/policy acceptance through the production CLI, default prelowering."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1]; ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks

def manifest(entry='main.phs',dependencies=(),modules=(),**extra):
    return dict(entrypoint=entry,dependencies=list(dependencies),runtime=dict(languages={'php':{'profile':'strict'}},modules=list(modules)),**extra)
def write(root,files,config):
    root.mkdir(parents=True,exist_ok=True)
    for name,source in files.items():
        path=root/name;path.parent.mkdir(parents=True,exist_ok=True);path.write_text(source)
    (root/'prism.json').write_text(json.dumps(config))

def main():
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',type=Path,required=True)
    parser.add_argument('--only',nargs='+');options=parser.parse_args()
    out=options.results.resolve();out.mkdir(parents=True,exist_ok=False)
    with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
        def compile(name,root,output=None,code=0):
            args=['php',APP/'main.php','--project',root]
            if output:
                output.parent.mkdir(parents=True,exist_ok=True)
                args+=['--output',output]
            return runner.run(name,args,expected=code,cli_reason='Production project CLI')
        def build(name,published,cache):
            return json.loads(runner.run(name,['php',APP/'tests/fixtures/native_cache_driver.php',published,cache,ROOT/'runtime/include','/usr/bin/clang++','--no-pch'],timeout=300,cli_reason='Project native cache'))
        def diamond():
            base=out/'diamond';app=base/'app';lib=app/'vendor/lib';left=base/'left';right=base/'right'
            write(lib,{'value.phs':'function shared_value():int{return 40;}'},manifest())
            write(left,{'left.phs':'function left_value():int{return shared_value()+1;}'},manifest(dependencies=['../app/vendor/lib']))
            write(right,{'right.phs':'function right_value():int{return shared_value()+2;}'},manifest(dependencies=['../app/vendor/lib/prism.json']))
            write(app,{'main.phs':'echo left_value(),":",right_value(),"\\n";','.prism/bogus.phs':'invalid syntax'},manifest(dependencies=['../left','../right','vendor/lib']))
            published=base/'published';cache=base/'cache';compile('diamond-publish',app,published)
            for phase in ['cold','warm']:
                result=build('diamond-'+phase,published,cache)
                if phase=='warm': assert result['compiled']==result['linked']==0,result
                assert runner.run('diamond-run-'+phase,[result['executable']])==b'41:42\n'
            (lib/'value.phs').write_text('function shared_value():int{return 50;}')
            compile('diamond-edit',app,published);result=build('diamond-edited-build',published,cache)
            assert runner.run('diamond-edited-run',[result['executable']])==b'51:52\n'
            (lib/'value.phs').unlink();compile('diamond-missing',app,published,1)
            runner.run('diamond-stale-block',['php',APP/'tests/fixtures/native_cache_driver.php',published,cache,ROOT/'runtime/include','/usr/bin/clang++','--no-pch'],expected=1,cli_reason='Failed project cannot use stale publication')
            (lib/'value.phs').write_text('function shared_value():int{return 60;}')
            compile('diamond-recovery',app,published);result=build('diamond-recovered-build',published,cache)
            assert runner.run('diamond-recovered-run',[result['executable']])==b'61:62\n'
            before={row['path'] for row in json.loads((published/'.scpp-output.manifest').read_text())['files']}
            config=json.loads((app/'prism.json').read_text());config['dependencies']=['../left','vendor/lib']
            (app/'prism.json').write_text(json.dumps(config));(app/'main.phs').write_text('echo left_value(),"\\n";')
            compile('diamond-remove-dependency',app,published)
            after={row['path'] for row in json.loads((published/'.scpp-output.manifest').read_text())['files']}
            assert before-after and all(not (published/path).exists() for path in before-after)
            result=build('diamond-pruned-build',published,cache)
            assert runner.run('diamond-pruned-run',[result['executable']])==b'61\n'

        def policy(mode,kind):
            root=out/(kind+'-'+mode)
            exports=[] if kind=='unconstrained' else ['answer' if kind!='unknown' else 'absent']
            call='answer' if kind in ['allowed','unknown','dependency'] else 'hidden'
            config=manifest(project_modules=[dict(name='app',sources=['main.phs'],dependencies=[] if kind=='dependency' else ['lib']),dict(name='lib',sources=['lib.phs'],public_exports=exports)],project_module_public_policy=mode,project_module_dependency_policy=mode)
            write(root,{'main.phs':f'echo {call}(),"\\n";','lib.phs':'function answer():int{return 42;}function hidden():int{return 7;}'},config)
            rejected=mode=='fail' and kind in ['private','unknown','dependency']
            compile(root.name,root,code=1 if rejected else 0)
            stderr=(out/'logs'/(root.name+'.stderr')).read_text()
            if rejected: assert 'validation failed' in stderr,stderr
            else:
                report=json.loads(stderr.splitlines()[-1]);p=report['public_api_validation'];d=report['dependency_validation']
                assert p['private_dependency_count']==int(kind in ['private','unknown']),p
                assert p['unknown_public_export_count']==int(kind=='unknown'),p
                assert d['undeclared_dependency_count']==int(kind=='dependency'),d
                if mode=='warn' and kind in ['private','unknown','dependency']: assert 'warning:' in stderr,stderr
        def invalid(kind):
            root=out/('invalid-'+kind);config=manifest();files={'main.phs':'echo 1;'}
            if kind=='cycle': config['dependencies']=['dep'];write(root/'dep',{'lib.phs':'function unused():int{return 0;}'},manifest(dependencies=['..']))
            elif kind=='missing':config['dependencies']=['missing']
            elif kind=='self':config['dependencies']=['.']
            elif kind=='collision':files['lib.phs']='function f():int{return 1;}';files['main.phs']='function f():int{return 2;} echo f();'
            elif kind=='entry':config['entrypoint']='missing.phs'
            write(root,files,config);compile(root.name,root,code=1)
            diagnostic=(out/'logs'/(root.name+'.stderr')).read_text()
            expected={'cycle':'cycle','missing':'not found','self':'same project root','collision':'duplicate','entry':'not found'}[kind]
            assert expected in diagnostic.lower(),diagnostic
        def identities():
            root=out/'identities';lib=root/'lib';decoy=root/'decoy'
            write(lib,{'same.phs':'namespace API; const N=7; class Box {public int $value=7; public function read():int{return $this->value;}} function chosen(int $x):int{return $x;}'},manifest(project_modules=[dict(name='lib',sources=['same.phs'],public_exports=['API\\N','API\\Box','API\\chosen'])]))
            write(decoy,{'same.phs':'namespace Decoy; function chosen(int $x):int{return 0;} function unrelated():int{return 0;}'},manifest(project_modules=[dict(name='decoy',sources=['same.phs'],public_exports=['Decoy\\unrelated'])]))
            config=manifest(dependencies=['lib','decoy'],project_modules=[dict(name='app',sources=['main.phs'],dependencies=['lib'])],project_module_public_policy='fail',project_module_dependency_policy='fail')
            write(root,{'main.phs':'$box = new API\\Box(); echo API\\chosen(API\\N),":",$box->read(),"\\n";'},config)
            compile('identities',root,root/'.prism/published')
            report=json.loads((out/'logs/identities.stderr').read_text().splitlines()[-1])
            assert report['public_api_validation']['resolved_public_export_count']==4,report
            assert [(d['module'],d['dependency']) for d in report['dependency_validation']['inferred_dependencies']]==[('app','lib')],report
            result=build('identities-build',root/'.prism/published',root/'.prism/cache')
            assert runner.run('identities-run',[result['executable']])==b'7:7\n'
            config=json.loads((lib/'prism.json').read_text());exports=config['project_modules'][0]['public_exports'][:]
            for index,target in enumerate(exports):
                config['project_modules'][0]['public_exports']=[name for name in exports if name!=target]
                (lib/'prism.json').write_text(json.dumps(config))
                compile('identities-private-'+str(index),root,code=1)
                diagnostic=(out/'logs'/('identities-private-'+str(index)+'.stderr')).read_text()
                assert 'private declaration `'+target+'`' in diagnostic,diagnostic

        def optional_runtime():
            root=out/'optional';config=manifest(modules=['regex'])
            write(root,{'main.phs':'echo regex_quote("a.b"),"\\n";'},config)
            compile('optional-publish',root,root/'.prism/published')
            result=build('optional-build',root/'.prism/published',root/'.prism/cache')
            assert runner.run('optional-run',[result['executable']])==b'a\\.b\n'
            config['runtime']['modules']=[];(root/'prism.json').write_text(json.dumps(config))
            compile('optional-disabled',root,root/'.prism/published',1)
            assert 'regex_quote' in (out/'logs/optional-disabled.stderr').read_text()
        def startup():
            root=out/'startup'
            runner.run('startup-snapshot',['php',APP/'tests/startup_metadata.php',root])
            runner.run('startup-fresh',['php',APP/'tests/startup_metadata.php',root,'fresh'])
            for phase,expected in [('initial',b'1\n'),('edited',b'2\n')]:
                result=build('startup-'+phase,root/phase,root/('cache-'+phase))
                assert runner.run('startup-run-'+phase,[result['executable']])==expected
        def original_cli():
            root=out/'original-cli';write(root,{'main.phs':'echo 42,"\\n";'},manifest())
            (root/'.prism').mkdir()
            runner.run('original-cli',['php',APP/'main.php','--s2s',root,'--entry','main.phs','--output',root/'.prism/published'],cli_reason='Existing single-directory CLI')
            result=build('original-build',root/'.prism/published',root/'.prism/cache')
            assert runner.run('original-run',[result['executable']])==b'42\n'
        tasks=[Task('diamond',diamond),Task('identities',identities),Task('optional',optional_runtime),Task('startup',startup),Task('original_cli',original_cli)]+[Task(k+'-'+m,lambda m=m,k=k:policy(m,k)) for m in ['report','warn','fail'] for k in ['allowed','private','unknown','unconstrained','dependency']]+[Task('invalid-'+k,lambda k=k:invalid(k)) for k in ['cycle','missing','self','collision','entry']]
        if options.only: tasks=[t for t in tasks if t.name in options.only]
        run_tasks(tasks)
    (out/'summary.json').write_text(json.dumps(dict(passed=True,tasks=[t.name for t in tasks]),indent=2)+'\n')
if __name__=='__main__':main()
