from pathlib import Path
import sys,json,subprocess,time
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try'
out=base/'native-pool-lines-acceptance';out.mkdir();source=out/'input';source.mkdir();(source/'main.phs').write_bytes((base/'partition-proof-main.phs').read_bytes())
binary=Path((base/'native-pool-32-lines-self/executable.txt').read_text())
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root) as runner:
 def check(mode):
  folder=out/mode;folder.mkdir()
  for key,value in {'request':source,'output':folder/'published','runtime':root/'runtime/metadata/modules','jobs':32,'stages':folder/'stages'}.items():(folder/(key+'.txt')).write_text(str(value))
  if mode=='ordinary':(folder/'ordinary.txt').write_text('')
  assert runner.run(mode+'-publish',[binary],cwd=folder,timeout=180)==b'OK'
  for key,value in {'native-build':folder/'published','cache':folder/'cache','runtime-include':root/'runtime/include','clang':'/usr/bin/clang++','jobs':32}.items():(folder/(key+'.txt')).write_text(str(value))
  exe=runner.run(mode+'-build',[binary],cwd=folder,timeout=300).decode()
  assert runner.run(mode+'-execute',[exe])==b'71'
 run_tasks([Task(mode,lambda mode=mode:check(mode)) for mode in ['ordinary','concrete']],jobs=1)
(out/'result.json').write_text(json.dumps({'passed':True,'output':'71','compiler':str(binary),'native_builder':True})+'\n')
