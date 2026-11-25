import json,sys,hashlib
from pathlib import Path
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try'
sys.path.insert(0,str(app/'tools'))
from test_runner import CommandRunner,Task,run_tasks
out=Path('/tmp/compiler-native-o2-self-01');out.mkdir(exist_ok=False)
binary=json.loads(Path('/tmp/compiler-native-o2-01/logs/build-o2.stdout').read_text())['executable']
(out/'request.txt').write_text('/tmp/compiler-bootstrap-native-transport-01/phpp')
(out/'output.txt').write_text(str(out/'published'))
with CommandRunner(out/'logs',root) as runner:
 def check():
  result=runner.run('self-compile',['/usr/bin/time','-v','-o',out/'compile.time',binary],cwd=out,timeout=1800)
  assert result==b'OK',result
  def inventory(folder):return {str(p.relative_to(folder)):hashlib.sha256(p.read_bytes()).hexdigest() for p in folder.rglob('*') if p.suffix in ['.hpp','.cpp']}
  reference=inventory(Path('/tmp/compiler-native-generation2-01/published'));actual=inventory(out/'published')
  report={'reference_count':len(reference),'actual_count':len(actual),'differing_paths':[k for k in sorted(reference.keys()|actual.keys()) if reference.get(k)!=actual.get(k)]}
  (out/'comparison.json').write_text(json.dumps(report,indent=2)+'\n')
  assert not report['differing_paths'],report
 run_tasks([Task('self-compile',check)],jobs=1)
