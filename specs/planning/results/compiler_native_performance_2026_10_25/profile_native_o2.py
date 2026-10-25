from pathlib import Path
import sys
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try';out=Path('/tmp/compiler-native-o2-01');out.mkdir(exist_ok=False)
# Reuse the audited host-only deadline adjustment; source compiler policy is unchanged.
boot=(app/'boot.php').read_text().replace('dirname(__DIR__, 2)',repr(str(root))).replace('__DIR__',repr(str(app)))
s=(app/'06_native/cpp_build.php').read_text()
s=s.replace("$handle = process_spawn('/usr/bin/env', $args, '', 120000, $this->root);", "file_put_contents('/tmp/compiler-native-o2-01/active.json', json_encode(['executable'=>$executable,'arguments'=>$args]));\n\t\t$handle = process_spawn('/usr/bin/env', $args, '', 1800000, $this->root);")
s=s.replace('$output = process_output($handle);', "$output = process_output($handle);\n\t\t\tfile_put_contents('/tmp/compiler-native-o2-01/outcome.json', json_encode(['exit'=>$output->exit_code,'timed_out'=>$output->timed_out,'stopped'=>$output->stopped,'signal'=>$output->signal,'stdout_bytes'=>strlen($output->stdout_text),'stderr_bytes'=>strlen($output->stderr_text)]));")
(out/'cpp_build.php').write_text(s)
boot=boot.replace(str(app)+"' . '/06_native/cpp_build.php",str(out)+"' . '/cpp_build.php")
(out/'boot.php').write_text(boot)
driver=(app/'tools/build_cpp.php').read_text().replace("dirname(__DIR__) . '/boot.php'",repr(str(out/'boot.php'))).replace('dirname(__DIR__, 3)',repr(str(root)))
(out/'build.php').write_text(driver)
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root) as r:
 run_tasks([Task('build-o2',lambda:r.run('build-o2',['/usr/bin/time','-v','-o',out/'build.time','php8.5',out/'build.php','/tmp/compiler-bootstrap-native-transport-01/published',out/'cache','-O2'],timeout=7200))],jobs=1)
