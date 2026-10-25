import json, sys
from pathlib import Path
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try'
sys.path.insert(0,str(app/'tools'))
from test_runner import CommandRunner,Task,run_tasks
binary=Path(sys.argv[1]);out=Path(sys.argv[2]);out.mkdir(parents=True,exist_ok=False)
cases=[('scalar','echo 6 * 7;',b'42'),('shared','class Node { public int $value = 7; } $node = new Node(); $same = $node; $same->value = 9; echo $node->value;',b'9'),('containers','echo implode("|", explode(",", "a,,b,"));',b'a||b|')]
with CommandRunner(out/'logs',root) as runner:
 def check(name,source,expected):
  folder=out/name;(folder/'source').mkdir(parents=True)
  (folder/'source/main.phs').write_text(source)
  (folder/'request.txt').write_text(str(folder/'source'))
  (folder/'output.txt').write_text(str(folder/'published'))
  result=runner.run(name+'-compile',['/usr/bin/time','-v','-o',folder/'compile.time',binary],cwd=folder,timeout=900)
  assert result==b'OK',result
  built=json.loads(runner.run(name+'-build',['php8.5',app/'tools/build_cpp.php',folder/'published',folder/'cache'],timeout=300))
  assert runner.run(name+'-run',[built['executable']])==expected
 run_tasks([Task(n,lambda n=n,s=s,e=e:check(n,s,e)) for n,s,e in cases])
