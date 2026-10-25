from pathlib import Path
import json,sys,time
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try'
jobs=int(sys.argv[1]);label=sys.argv[2] if len(sys.argv)>2 else str(jobs);folder=base/('native-pool-'+label);folder.mkdir()
cache=base/('native-pool-cache-lines-self-'+str(jobs))
for key,value in {'native-build':base/'performance-native08/published','cache':cache,'runtime-include':root/'runtime/include','clang':base/'native-clang.py','jobs':jobs}.items():(folder/(key+'.txt')).write_text(str(value))
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(folder/'logs',root) as runner:
 def build():
  data=runner.run('build',['/usr/bin/time','-v','-o',folder/'build.time','python3',base/'native_tree_time.py',folder/'tree.json',base/'performance-native08/compiler-release'],cwd=folder,timeout=1800)
  (folder/'executable.txt').write_bytes(data)
 run_tasks([Task('build',build)],jobs=1)
(folder/'result.json').write_text(json.dumps({'jobs':jobs,'executable':(folder/'executable.txt').read_text(),'time':(folder/'build.time').read_text(),'tree':json.loads((folder/'tree.json').read_text())},indent=2)+'\n')
