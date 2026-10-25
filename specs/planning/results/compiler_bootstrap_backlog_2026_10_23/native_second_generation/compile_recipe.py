import json,sys
from pathlib import Path
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try'
sys.path.insert(0,str(app/'tools'))
from test_runner import CommandRunner,Task,run_tasks
out=Path('/tmp/compiler-native-generation2-01');out.mkdir(exist_ok=False)
binary=json.loads(Path('/tmp/compiler-native-transport-01/logs/audit.stdout').read_text())['executable']
(out/'request.txt').write_text('/tmp/compiler-bootstrap-native-transport-01/phpp')
(out/'output.txt').write_text(str(out/'published'))
with CommandRunner(out/'logs',root) as runner:
 run_tasks([Task('generation2',lambda:runner.run('generation2',['/usr/bin/time','-v','-o',out/'compile.time',binary],cwd=out,timeout=900))],jobs=1)
