from pathlib import Path
import sys,json,time,shutil
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try';out=base/'concurrency-output-proof08';out.mkdir();published=out/'published'
fixture=out/'input';shutil.copytree(base/'input',fixture)
entry=fixture/'main.phs';entry.write_text(entry.read_text().replace("'/home/alexv/__AI/simple_cpp/simple_cpp_01/runtime/metadata/modules'", "\\scpp_portability_fs_read_text('runtime.txt')"))
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
host=out/'generate';host.mkdir()
for key,value in {'request':fixture,'output':published,'runtime':root/'runtime/metadata/modules','jobs':32,'stages':host/'stages'}.items():(host/(key+'.txt')).write_text(str(value))
with CommandRunner(out/'generate-logs',root) as runner:
 run_tasks([Task('generate',lambda:runner.run('generate',[base/'concurrency-07/compiler-release'],cwd=host,timeout=600))],jobs=1)
manifest=json.loads((published/'.scpp-output.manifest').read_text());build=manifest['runtime_build'];runtime=root/'runtime/include'
flags=['-std=c++23','-O2','-DSCPP_LANGUAGE_TARGET_PHP=1','-pthread','-I',str(runtime),*['-D'+d for d in build['definitions']],*build['compile_options']];links=[*['-l'+l for l in build['libraries']],*build['link_options']];layouts=[]
with CommandRunner(out/'logs',root,jobs=32) as runner:
 for i,f in enumerate(f for f in manifest['files'] if f['kind']=='layout_probe'):
  exe=out/('layout-'+str(i));header=out/('layout-'+str(i)+'.hpp')
  runner.run('layout-build-'+str(i),['clang++',*flags,'-I',published,*layouts,published/f['path'],*links,'-fuse-ld=lld','-o',exe],timeout=180)
  header.write_bytes(runner.run('layout-run-'+str(i),[exe]));layouts+=['-include',str(header)]
 units=[(runtime/s,False) for s in build['sources']]+[(published/f['path'],True) for f in manifest['files'] if f['kind']=='translation_unit'];objects=out/'objects';objects.mkdir()
 def compile_one(i,s,g):
  args=['clang++',*flags]
  if g:args+=['-I',str(published),*layouts]
  runner.run('compile-'+str(i),[*args,'-c',s,'-o',objects/(str(i)+'.o')],timeout=300)
 run_tasks([Task('compile-'+str(i),lambda i=i,s=s,g=g:compile_one(i,s,g)) for i,(s,g) in enumerate(units)],jobs=32)
 binary=out/'compiler';runner.run('link',['clang++',*flags,'-fuse-ld=lld',*[objects/(str(i)+'.o') for i in range(len(units))],*links,'-o',binary],timeout=300)
 folder=out/'run';folder.mkdir()
 for key,value in {'request':base/'concurrency-smoke-input','output':folder/'published','runtime':root/'runtime/metadata/modules','jobs':32,'stages':folder/'stages'}.items():(folder/(key+'.txt')).write_text(str(value))
 def smoke():
  assert runner.run('compiler-smoke',[binary],cwd=folder,timeout=180)==b'OK'
  built=json.loads(runner.run('program-build',['php8.5',app/'tools/build_cpp.php',folder/'published',folder/'cache','-O2'],timeout=600))
  assert runner.run('program-run',[built['executable']])==b'7:10'
 run_tasks([Task('output-execution',smoke)],jobs=1)
 (out/'result.json').write_text(json.dumps({'passed':True,'units':len(units),'expected':'7:10'})+'\n')
