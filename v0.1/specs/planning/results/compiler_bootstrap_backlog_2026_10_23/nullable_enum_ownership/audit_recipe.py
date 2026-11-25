from pathlib import Path
import sys
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try';out=Path('/tmp/bootstrap-enum-owners-01');out.mkdir(exist_ok=True)
boot=(app/'boot.php').read_text().replace('dirname(__DIR__, 2)',repr(str(root))).replace('__DIR__',repr(str(app)))
worker=(app/'04_analyze/prepare/worker.php').read_text();marker='\t\t$this->attempt_failed[$owner] = true;'
worker=worker.replace(marker,'''\t\t$decl = $owner->declaration();
        file_put_contents('/tmp/bootstrap-enum-owners-01/failures.jsonl', json_encode(['kind'=>get_class($owner), 'file'=>$owner->source->source_file()->path, 'name'=>$decl?->qualified_name(), 'token'=>$decl?->token_index, 'message'=>$message]) . "\\n", FILE_APPEND);
'''+marker)
(out/'worker.php').write_text(worker)
boot=boot.replace(str(app) + "' . '/04_analyze/prepare/worker.php",str(out) + "' . '/worker.php")
(out/'boot.php').write_text(boot)
driver=(app/'tools/bootstrap_probe.php').read_text().replace("require $argv[1] . '/boot.php';", "require '/tmp/bootstrap-enum-owners-01/boot.php';")
(out/'driver.php').write_text(driver)
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root) as runner:
 run_tasks([Task('audit',lambda:runner.run('audit',['php8.5','-d','memory_limit=4G',out/'driver.php',app,'/tmp/compiler-bootstrap-nullable-enum-source-02/phpp',out/'bootstrap.json'],expected=1,timeout=900))],jobs=1)
