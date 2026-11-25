from pathlib import Path
import sys,json
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try';out=base/'partition-native-acceptance';out.mkdir();source=out/'input';source.mkdir();(source/'main.phs').write_bytes((base/'partition-proof-main.phs').read_bytes());binary=base/'partition-profile-candidate04/compiler-release'
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root,jobs=2) as runner:
 def check(mode):
  folder=out/mode;folder.mkdir()
  for key,value in {'request':source,'output':folder/'published','runtime':root/'runtime/metadata/modules','jobs':32,'stages':folder/'stages'}.items():(folder/(key+'.txt')).write_text(str(value))
  if mode=='ordinary':(folder/'ordinary.txt').write_text('')
  assert runner.run(mode+'-publish',[binary],cwd=folder,timeout=180)==b'OK'
  units=list((folder/'published/units').rglob('*.cpp'));assert len(units)>2;assert max(x.stat().st_size for x in units)<200000
  built=json.loads(runner.run(mode+'-build',['php8.5',app/'tools/build_cpp.php',folder/'published',folder/'cache','-O2'],timeout=600))
  assert runner.run(mode+'-execute',[built['executable']])==b'71'
 run_tasks([Task(mode,lambda mode=mode:check(mode)) for mode in ['ordinary','concrete']],jobs=2)
(out/'result.json').write_text(json.dumps({'passed':True,'expected':'71','compiler_workers':32})+'\n')
