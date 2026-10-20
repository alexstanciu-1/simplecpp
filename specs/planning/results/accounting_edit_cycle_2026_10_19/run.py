from pathlib import Path
import sys,shutil,json
ROOT=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');OUT=Path('/tmp/accounting-edit-cycle')
sys.path.insert(0,str(ROOT/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
proxy=Path('/tmp/accounting-timing/timed_clang.py').read_text()
with CommandRunner(OUT/'logs',ROOT,jobs=12) as runner:
 for name,mode,linker in [('prepared-gnu','prepared','/usr/bin/ld'),('prepared-mold','prepared','/home/alexv/.local/bin/mold'),('ordinary-gnu','ordinary','/usr/bin/ld')]:
  folder=OUT/name;folder.mkdir(exist_ok=True)
  shutil.copytree(ROOT/'examples/accounting_workload',folder/'source',dirs_exist_ok=True)
  script=proxy.replace("args = sys.argv[1:]", "args = sys.argv[1:]\nargs = ['-fuse-ld="+linker+"' if a.startswith('-fuse-ld=') else a for a in args]")
  path=folder/'clang.py';path.write_text(script);path.chmod(0o755)
  def scenario():
   runner.run(name+'-session',['php',OUT/'session.php',folder,mode],timeout=420,cli_reason='Retained compiler benchmark with native subprocesses')
   rows=json.loads((folder/'results.json').read_text())
   for row in rows:
    output=runner.run(name+'-run'+str(row['revision']),[row['executable']]);assert b'CHECKS passed=998 failed=0' in output
   print(name,[(r['revision'],round(r['measured_total'],3),r['compiled']) for r in rows],flush=True)
  run_tasks([Task(name,scenario)])
