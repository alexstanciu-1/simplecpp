"""Diagnostic only: profile a temporary compiler copy and export real LLVM for replay. Run from the repository workspace; see README.md."""

from pathlib import Path
import shutil,subprocess,tempfile,os,json
root=Path(__file__).resolve().parents[2]; out=root/'build/scalability/dispatch_probe'; ir=out/'ir';ir.mkdir(parents=True,exist_ok=True)
with tempfile.TemporaryDirectory(prefix='scpp_dispatch_probe_') as temp:
 work=Path(temp)
 for part in ('src','tool_process','language','tools'):
  shutil.copytree(root/part,work/part)
 configuration=work/'tools/backend.json'
 backend=json.loads(configuration.read_text());backend['compile_jobs']=20
 configuration.write_text(json.dumps(backend)+'\n')
 shutil.copyfile(root/'bootstrap.php',work/'bootstrap.php')
 bench=work/'benchmarks/scalability';bench.mkdir(parents=True)
 def replace(path,old,new):
  text=path.read_text();assert text.count(old)==1,(path,old,text.count(old));path.write_text(text.replace(old,new))
 shutil.copyfile(root/'benchmarks/scalability/measure.php',bench/'measure.php')
 with (work/'bootstrap.php').open('a') as f:
  f.write('''
class Dispatch_Probe {
 public static array $rows = [];
 public static function usage(): array { return [hrtime(true), getrusage(), getrusage(1)]; }
 public static function record(string $name, array $start): void {
  $end = self::usage(); $row = ['name'=>$name,'start_ns'=>$start[0],'end_ns'=>$end[0],'seconds'=>($end[0]-$start[0])/1e9];
  foreach ([1=>'php',2=>'children'] as $i=>$label) {
   foreach (['ru_utime','ru_stime'] as $field) { $row[$label.'_'.$field] = $end[$i][$field.'.tv_sec']-$start[$i][$field.'.tv_sec']+($end[$i][$field.'.tv_usec']-$start[$i][$field.'.tv_usec'])/1e6; }
   foreach (['ru_minflt','ru_majflt','ru_inblock','ru_oublock','ru_nvcsw','ru_nivcsw'] as $field) { $row[$label.'_'.$field]=$end[$i][$field]-$start[$i][$field]; }
  }
  self::$rows[]=$row;
 }
}
''')
 replace(bench/'measure.php','$start = hrtime(true);',r'\Dispatch_Probe::$rows = []; $start = hrtime(true);')
 replace(bench/'measure.php',"$rows = ['phase' => $phase,",r"$rows = ['dispatch_probe' => \Dispatch_Probe::$rows, 'phase' => $phase,")
 replace(bench/'measure.php',"$row = self::measure($session, $manifest, $output, $phase, $phase === 'body_edit' ? 43 : 42);", """$row = self::measure($session, $manifest, $output, $phase, $phase === 'body_edit' ? 43 : 42);
            if ($phase === 'cold') {
                foreach ($session->published->llvm->modules as $module) { file_put_contents(getenv('SCPP_PROBE_IR') . '/' . $module->source_file_id . '.ll', $module->ir); }
                file_put_contents(getenv('SCPP_PROBE_IR') . '/target.txt', $session->published->llvm->backend->configuration->target_triple);
            }""")
 path=work/'tool_process/process.php'
 replace(path,'$this->in = tmpfile();',r'$probe = \Dispatch_Probe::usage(); $this->in = tmpfile();')
 replace(path,"$this->process = proc_open([$launcher, '--', ...$command],",r"\Dispatch_Probe::record('streams', $probe); $probe = \Dispatch_Probe::usage();"+"\n            $this->process = proc_open([$launcher, '--', ...$command],")
 replace(path,"if (!is_resource($this->process))",r"\Dispatch_Probe::record('proc_open', $probe); if (!is_resource($this->process))")
 path=work/'src/05_generate_code/prepare_backend/tools/toolchain.php'
 replace(path,'$active = [];',r'$pool_probe = \Dispatch_Probe::usage(); $active = [];')
 replace(path,'foreach ($active as $process) { $process->close(); }',r"foreach ($active as $process) { $process->close(); } \Dispatch_Probe::record('object_pool', $pool_probe);")
 shutil.copytree(root/'examples/scalability/5mb',work/'project')
 case=next(x for x in json.loads((root/'examples/scalability/inventory.json').read_text()) if x['project']=='5mb')
 with (out/'pidstat.txt').open('w') as stats, (out/'vmstat.txt').open('w') as vm, (out/'iostat.txt').open('w') as io:
  monitors=[subprocess.Popen(['pidstat','-h','-u','-d','-w','-p','ALL','1'],stdout=stats),subprocess.Popen(['vmstat','-w','1'],stdout=vm),subprocess.Popen(['iostat','-xz','-y','1'],stdout=io)]
  try:
   result=subprocess.run(['php','-d','memory_limit=2048M','-d','opcache.enable_cli=0',str(bench/'measure.php'),str(work/'project/project.json'),str(work/'program'),str(work/'project'/case['edit_file'])],env=dict(os.environ,XDEBUG_MODE='off',SCPP_PROBE_IR=str(ir)),capture_output=True,text=True,timeout=180)
  finally:
   for p in monitors: p.terminate();p.wait()
 (out/'stderr.txt').write_text(result.stderr)
 result.check_returncode()
 rows=[json.loads(line) for line in result.stdout.splitlines()]
 (out/'capture.json').write_text(json.dumps({'method':'Temporary compiler copy; hrtime/getrusage around streams, proc_open and whole object pool; no forwarding Clang wrapper. pidstat/vmstat/iostat at 1 second; IR dumped after cold timer; 20 jobs.','measurements':rows},indent=2)+'\n')
 for m in rows:
  print(m['phase'],m['seconds'],flush=True)
  for name in ('streams','proc_open','object_pool'):
   jobs=[r for r in m['dispatch_probe'] if r['name']==name]
   print(name,len(jobs),{k:round(sum(r[k] for r in jobs),4) for k in ('seconds','php_ru_utime','php_ru_stime','children_ru_utime','children_ru_stime','children_ru_minflt','children_ru_majflt','children_ru_inblock','children_ru_oublock')},flush=True)
