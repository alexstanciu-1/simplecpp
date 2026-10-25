from pathlib import Path
root=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=root/'compiler/my-try';instrument=Path('/tmp/receiver-bootstrap-instrument-01');instrument.mkdir(exist_ok=True)
boot=(app/'boot.php').read_text().replace('dirname(__DIR__, 2)',repr(str(root))).replace('__DIR__',repr(str(app)))
worker=(app/'04_analyze/prepare/worker.php').read_text();marker='\t\t$this->attempt_failed[$owner] = true;'
assert worker.count(marker)==1
logging='''        static $reported = [];
        $decl = $owner->declaration();
        $row = ['kind'=>get_class($owner), 'file'=>$owner->source->source_file()->path, 'name'=>$decl?->qualified_name(), 'token'=>$decl?->token_index, 'message'=>$message];
        $key = json_encode($row);
        if (!isset($reported[$key])) {
            $reported[$key] = true;
            file_put_contents('/tmp/receiver-bootstrap-instrument-01/failures.jsonl', $key . "\\n", FILE_APPEND);
        }
'''
(instrument/'worker.php').write_text(worker.replace(marker,logging+marker))
boot=boot.replace(str(app)+"' . '/04_analyze/prepare/worker.php",str(instrument)+"' . '/worker.php")
(instrument/'boot.php').write_text(boot)
driver=(app/'tools/bootstrap_probe.php').read_text().replace("require $argv[1] . '/boot.php';", "require '/tmp/receiver-bootstrap-instrument-01/boot.php';")
(instrument/'driver.php').write_text(driver)
script=(app/'tools/bootstrap_probe.py').read_text().replace('from test_runner import CommandRunner, Task, run_tasks',"import sys\nsys.path.insert(0, "+repr(str(app/'tools'))+")\nfrom test_runner import CommandRunner, Task, run_tasks")
script=script.replace('APP = Path(__file__).resolve().parents[1]', 'APP = Path('+repr(str(app))+')').replace("APP / 'tools/bootstrap_probe.php'", "Path('/tmp/receiver-bootstrap-instrument-01/driver.php')")
(instrument/'probe.py').write_text(script)
