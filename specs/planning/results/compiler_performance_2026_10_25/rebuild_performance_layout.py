from pathlib import Path
import sys,json,shutil,time,hashlib
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try';out=base/'performance-layout01';out.mkdir(exist_ok=True);source=out/'source';source.mkdir(exist_ok=True)
for stage in ['01_prepare_inputs','02_tokenize','03_parse','04_analyze','05_backend','06_native','compiler']:shutil.copytree(app/stage,source/stage,dirs_exist_ok=True)
from instrument_performance import instrument
instrument(source)
driver=(base/'concurrency-01/driver-source/main.php').read_text().replace('new cpp_lowering_options(true)','new cpp_lowering_options(!fs_is_file(\'ordinary.txt\'))')
driver=driver.replace('} finally {', '} catch (\\Throwable $error) { echo $error->getMessage(), \"\\n\"; throw $error; } finally {')
(source/'main.php').write_text(driver)
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root,jobs=32) as runner:
 runner.run('convert',['php8.5',root/'tools/php_portability/convert.php',source,out/'input','--target=v0.2'],timeout=300)
 shutil.copytree(base/'concurrency-01/input/scpp_framework',out/'input/scpp_framework',dirs_exist_ok=True)
 for name in ['prism.json','.scpp-native-runtime.json']:shutil.copyfile(base/'concurrency-01/input'/name,out/'input'/name)
 host=out/'host';host.mkdir(exist_ok=True)
 for name,value in {'request':out/'input','output':out/'published','runtime':root/'runtime/metadata/modules','jobs':32,'stages':host/'stages'}.items():(host/(name+'.txt')).write_text(str(value))
 runner.run('host-publish',['/usr/bin/time','-v','-o',out/'publish.time','php8.5','-d','memory_limit=4G','-r','require $argv[1]; require $argv[2];',app/'boot.php',source/'main.php'],cwd=host,timeout=900)
 published=out/'published';manifest=json.loads((published/'.scpp-output.manifest').read_text());build=manifest['runtime_build'];runtime=root/'runtime/include'
 for f in manifest['files']:assert hashlib.sha256((published/f['path']).read_bytes()).hexdigest()==f['sha256']
 flags=['-std=c++23','-O2','-DSCPP_LANGUAGE_TARGET_PHP=1','-pthread','-I',str(runtime),*['-D'+d for d in build['definitions']],*build['compile_options']]
 links=[*['-l'+l for l in build['libraries']],*build['link_options']];layouts=[]
 for i,f in enumerate(f for f in manifest['files'] if f['kind']=='layout_probe'):
  exe=out/('layout-'+str(i));header=out/('layout-'+str(i)+'.hpp')
  runner.run('layout-build-'+str(i),['clang++',*flags,'-I',published,*layouts,published/f['path'],*links,'-fuse-ld=lld','-o',exe],timeout=180)
  header.write_bytes(runner.run('layout-run-'+str(i),[exe]));layouts+=['-include',str(header)]
 units=[(runtime/s,False) for s in build['sources']]+[(published/f['path'],True) for f in manifest['files'] if f['kind']=='translation_unit']
 (out/'times').mkdir()
 previous=base/'performance-manifest01';old_manifest=json.loads((previous/'published/.scpp-output.manifest').read_text())
 header_map=lambda m:{f['path']:f['sha256'] for f in m['files'] if f['kind']=='header'}
 reusable=header_map(manifest)==header_map(old_manifest)
 old_units=[(runtime/x,False) for x in build['sources']]+[(previous/'published'/f['path'],True) for f in old_manifest['files'] if f['kind']=='translation_unit']
 old_indexes={(str(p.relative_to(previous/'published')) if g else str(p)):i for i,(p,g) in enumerate(old_units)}
 print('identical_headers',reusable,flush=True)
 for mode in ['release']:
  objects=out/(mode+'-objects');objects.mkdir()
  mode_flags=flags if mode=='release' else [('-O1' if x=='-O2' else x) for x in flags]+['-fsanitize=thread','-gline-tables-only','-fno-omit-frame-pointer']
  def compile_one(i,s,g):
   key=str(s.relative_to(published)) if g else str(s)
   prior=old_indexes.get(key)
   if reusable and prior is not None:
    old_source=old_units[prior][0]
    if old_source.read_bytes()==s.read_bytes():
     (objects/(str(i)+'.o')).symlink_to(previous/(mode+'-objects')/(str(prior)+'.o'));return
   args=['clang++',*mode_flags]
   if g:args+=['-I',str(published),*layouts]
   runner.run(mode+'-compile-'+str(i),['/usr/bin/time','-v','-o',out/'times'/(mode+'-'+str(i)+'.time'),*args,'-c',s,'-o',objects/(str(i)+'.o')],timeout=300)
  start=time.monotonic();run_tasks([Task(mode+'-'+str(i),lambda i=i,s=s,g=g:compile_one(i,s,g)) for i,(s,g) in enumerate(units)],jobs=32)
  runner.run(mode+'-link',['clang++',*mode_flags,'-fuse-ld=lld',*[objects/(str(i)+'.o') for i in range(len(units))],*links,'-o',out/('compiler-'+mode)],timeout=300)
  print(mode,'build_seconds',time.monotonic()-start,flush=True)
 (out/'build.json').write_text(json.dumps({'executable':str(out/'compiler-release'),'tsan':str(out/'compiler-tsan'),'units':len(units)},indent=2))
