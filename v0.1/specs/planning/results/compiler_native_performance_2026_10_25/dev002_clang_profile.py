from pathlib import Path
import json,sys,time
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try'
status=base/'results-03/status.json'
while not status.exists() or json.loads(status.read_text())['stage']!='complete':time.sleep(5)
out=base/'clang-profile';out.mkdir(exist_ok=False);(out/'actions').mkdir()
boot=(app/'boot.php').read_text().replace('dirname(__DIR__, 2)',repr(str(root))).replace('__DIR__',repr(str(app)))
s=(app/'06_native/cpp_build.php').read_text()
needle="\t\t$args /** vector<string> */ = Native_CPP_Commands::environment($executable);"
replacement="""\t\t$profile = 'PROFILE/' . hrtime(true);
\t\tif (($executable === '/usr/bin/clang++') && in_array('-c', $arguments, true)) { $arguments[] = '-ftime-trace=' . $profile . '.trace.json'; }
\t\tfile_put_contents($profile . '.command.json', json_encode(['executable'=>$executable, 'arguments'=>$arguments]));
""".replace('PROFILE',str(out/'actions'))+needle
assert needle in s;s=s.replace(needle,replacement)
s=s.replace("$handle = process_spawn('/usr/bin/env', $args, '', 120000, $this->root);", "$handle = process_spawn('/usr/bin/time', array_merge(['-v', '-o', $profile . '.time', '/usr/bin/env'], $args), '', 1800000, $this->root);")
(out/'cpp_build.php').write_text(s);(out/'boot.php').write_text(boot.replace(str(app)+"' . '/06_native/cpp_build.php",str(out)+"' . '/cpp_build.php"))
driver=(app/'tools/build_cpp.php').read_text().replace("dirname(__DIR__) . '/boot.php'",repr(str(out/'boot.php'))).replace('dirname(__DIR__, 3)',repr(str(root)));(out/'build.php').write_text(driver)
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root) as r:
 run_tasks([Task('clang-profile',lambda:r.run('clang-profile',['/usr/bin/time','-v','-o',out/'build.time','php8.5',out/'build.php',base/'results-03/published',out/'cache','-O2'],timeout=7200))],jobs=1)
