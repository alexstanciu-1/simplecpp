from pathlib import Path
import json,sys,shutil,os
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try';out=base/'results-03';out.mkdir(exist_ok=False)
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks

def status(value): (out/'status.json').write_text(json.dumps({'stage':value})+'\n')
with CommandRunner(out/'logs',root) as runner:
 def benchmark():
  status('convert-driver')
  runner.run('convert-driver',['php8.5',root/'tools/php_portability/convert.php',base/'driver-source',base/'driver-phs','--target=v0.2'])
  shutil.copytree(base/'input',base/'compiler-input',dirs_exist_ok=True)
  shutil.copyfile(base/'driver-phs/main.phs',base/'compiler-input/main.phs')
  status('publish')
  host=out/'host';host.mkdir()
  (host/'request.txt').write_text(str(base/'compiler-input'));(host/'output.txt').write_text(str(out/'published'))
  (host/'runtime.txt').write_text(str(root/'runtime/metadata/modules'));(host/'jobs.txt').write_text(str(len(os.sched_getaffinity(0))))
  runner.run('publish',['/usr/bin/time','-v','-o',out/'publish.time','php8.5','-d','memory_limit=4G','-r','require $argv[1]; require $argv[2];',app/'boot.php',base/'driver-source/main.php'],cwd=host,timeout=900)
  boot=(app/'boot.php').read_text().replace('dirname(__DIR__, 2)',repr(str(root))).replace('__DIR__',repr(str(app)))
  build=(app/'06_native/cpp_build.php').read_text().replace("$handle = process_spawn('/usr/bin/env', $args, '', 120000, $this->root);", "file_put_contents('"+str(out/'active.json')+"', json_encode(['executable'=>$executable,'arguments'=>$args]));\n\t\t$handle = process_spawn('/usr/bin/env', $args, '', 1800000, $this->root);")
  (out/'cpp_build.php').write_text(build)
  (out/'boot.php').write_text(boot.replace(str(app)+"' . '/06_native/cpp_build.php",str(out)+"' . '/cpp_build.php"))
  driver=(app/'tools/build_cpp.php').read_text().replace("dirname(__DIR__) . '/boot.php'",repr(str(out/'boot.php'))).replace('dirname(__DIR__, 3)',repr(str(root)))
  (out/'build.php').write_text(driver)
  status('build-o2')
  build=json.loads(runner.run('build-o2',['/usr/bin/time','-v','-o',out/'build.time','php8.5',out/'build.php',out/'published',base/'cache','-O2'],timeout=7200))
  (out/'build.json').write_text(json.dumps(build,indent=2)+'\n')
  binary=build['executable'];cpus=len(os.sched_getaffinity(0))
  (out/'machine.json').write_text(json.dumps({'cpus':cpus,'affinity':sorted(os.sched_getaffinity(0)),'cpu_quota':Path('/sys/fs/cgroup/cpu.max').read_text() if Path('/sys/fs/cgroup/cpu.max').exists() else None},indent=2)+'\n')
  for jobs in [cpus,1]:
   folder=out/('jobs-'+str(jobs));folder.mkdir()
   (folder/'request.txt').write_text(str(base/'input'));(folder/'output.txt').write_text(str(folder/'published'))
   (folder/'runtime.txt').write_text(str(root/'runtime/metadata/modules'));(folder/'jobs.txt').write_text(str(jobs))
   status('self-compile-'+str(jobs))
   result=runner.run('self-'+str(jobs),['/usr/bin/time','-v','-o',folder/'compile.time','python3',base/'monitor.py',binary,folder/'threads.json'],cwd=folder,timeout=1800)
   assert result==b'OK',result
  import hashlib
  def inventory(folder):return {str(p.relative_to(folder)):hashlib.sha256(p.read_bytes()).hexdigest() for p in folder.rglob('*') if p.suffix in ['.hpp','.cpp']}
  many=inventory(out/('jobs-'+str(cpus))/'published');one=inventory(out/'jobs-1/published')
  comparison={'one_count':len(one),'many_count':len(many),'different':[k for k in sorted(one.keys()|many.keys()) if one.get(k)!=many.get(k)]}
  (out/'comparison.json').write_text(json.dumps(comparison,indent=2)+'\n')
  status('complete')
 run_tasks([Task('dev002-o2',benchmark)],jobs=1)
