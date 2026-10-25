from pathlib import Path
import sys
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try';out=Path('/tmp/compiler-native-transport-01');out.mkdir(exist_ok=False)
boot=(app/'boot.php').read_text().replace('dirname(__DIR__, 2)',repr(str(root))).replace('__DIR__',repr(str(app)))
s=(app/'06_native/cpp_build.php').read_text()
s=s.replace("$handle = process_spawn('/usr/bin/env', $args, '', 120000, $this->root);", "file_put_contents('/tmp/compiler-native-transport-01/active.json', json_encode(['executable'=>$executable,'arguments'=>$args]));\n\t\t$handle = process_spawn('/usr/bin/env', $args, '', 600000, $this->root);")
s=s.replace('$output = process_output($handle);', "$output = process_output($handle);\n\t\t\tfile_put_contents('/tmp/compiler-native-transport-01/outcome.json', json_encode(['exit'=>$output->exit_code,'timed_out'=>$output->timed_out,'stopped'=>$output->stopped,'signal'=>$output->signal,'stdout_bytes'=>strlen($output->stdout_text),'stderr_bytes'=>strlen($output->stderr_text)]));")
(out/'cpp_build.php').write_text(s)
boot=boot.replace(str(app)+"' . '/06_native/cpp_build.php",str(out)+"' . '/cpp_build.php")
(out/'boot.php').write_text(boot)
driver=(app/'tools/build_cpp.php').read_text().replace("dirname(__DIR__) . '/boot.php'",repr(str(out/'boot.php'))).replace('dirname(__DIR__, 3)',repr(str(root)))
(out/'build.php').write_text(driver)
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root) as r:
 run_tasks([Task('audit',lambda:r.run('audit',['/usr/bin/time','-v','-o',out/'build.time','php8.5',out/'build.php','/tmp/compiler-bootstrap-native-transport-01/published','/tmp/compiler-native-layout-01/cache','-O0'],timeout=3600))],jobs=1)
