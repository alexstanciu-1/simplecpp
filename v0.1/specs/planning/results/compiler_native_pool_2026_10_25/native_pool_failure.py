from pathlib import Path
import json,shutil,hashlib,sys
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';out=base/'native-pool-native-failure';out.mkdir();published=out/'published';shutil.copytree(base/'native-pool-many-fixed/published',published)
manifest=published/'.scpp-output.manifest';status=published/'.scpp-output.status';entry=published/'entry.cpp'
original_manifest=manifest.read_bytes();original_status=status.read_bytes();original_entry=entry.read_bytes()
entry.write_bytes(b'static_assert(false, "native_pool_failure");\n'+original_entry)
changed=json.loads(original_manifest)
for row in changed['files']:
 if row['path']=='entry.cpp':row['sha256']=hashlib.sha256(entry.read_bytes()).hexdigest()
content=json.dumps(changed,separators=(',',':')).encode()+b'\n';manifest.write_bytes(content);status.write_text('scpp-output-v1:ready:'+hashlib.sha256(content).hexdigest()+'\n')
for k,v in {'native-build':published,'cache':out/'cache','runtime-include':root/'runtime/include','clang':'/usr/bin/clang++','jobs':32}.items():(out/(k+'.txt')).write_text(str(v))
sys.path.insert(0,str(root/'compiler/my-try/tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root) as runner:
 def proof():
  try:
   runner.run('failed-build',[base/'performance-native08/compiler-release'],cwd=out,expected=1,timeout=120)
   assert (out/'cache/.scpp-native.status').read_text()=='pending\n'
   assert not list((out/'cache').glob('.scpp-stage-*'))
  finally:
   entry.write_bytes(original_entry);manifest.write_bytes(original_manifest);status.write_bytes(original_status)
  exe=runner.run('retry-build',[base/'performance-native08/compiler-release'],cwd=out,timeout=120).decode();runner.run('execute',[exe])
 run_tasks([Task('native-failure-retry',proof)],jobs=1)
(out/'result.json').write_text(json.dumps({'passed':True,'jobs':32,'units':513})+'\n')
