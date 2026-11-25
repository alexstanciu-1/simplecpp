from pathlib import Path
import sys,json
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try';out=Path('/tmp/compiler-native-o2-heap-raw-01');out.mkdir(exist_ok=False)
binary=json.loads(Path('/tmp/compiler-native-o2-01/logs/build-o2.stdout').read_text())['executable']
(out/'request.txt').write_text('/tmp/compiler-bootstrap-native-transport-01/phpp');(out/'output.txt').write_text(str(out/'published'))
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root) as runner:
 run_tasks([Task('heap',lambda:runner.run('heap',['heaptrack','--raw','--record-only','-o',out/'heap',binary],cwd=out,timeout=1800))],jobs=1)
