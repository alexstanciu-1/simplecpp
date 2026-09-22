"""Real compiler proof of bounded external jobs, reuse, cancellation and timeout cleanup."""
from pathlib import Path
import json, os, shutil, subprocess, sys, tempfile, time

ROOT = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix='scpp_parallel_') as tmp:
    work = Path(tmp)
    (work/'src').mkdir()
    (work/'project.json').write_text(json.dumps({'source_folders':['src'], 'entry':'src/main.phs'}))
    (work/'src/main.phs').write_text(''.join(f'f{i}();\n' for i in range(1,26))+'return 42;\n')
    for i in range(1,26): (work/f'src/f{i}.phs').write_text(f'function f{i}(): int {{ return {i}; }}\n')
    wrapper = work/'clang-wrapper'
    wrapper.write_text(f'#!{sys.executable}\n'+'''import fcntl,json,os,pathlib,subprocess,sys,time
root=pathlib.Path(__file__).parent
args=sys.argv[1:]
output=args[args.index('-o')+1] if '-o' in args else ''
is_object='-c' in args and pathlib.Path(output).name.startswith('scpp-object-')
if not is_object:
    raise SystemExit(subprocess.call([REAL,*args]))
source=sys.stdin.buffer.read()
settings=json.loads((root/'settings.json').read_text())
tag=settings['tag']; gate=root/(tag+'.gate'); state=root/(tag+'.json')
child=None
if settings.get('fail'):
    marker=root/(tag+'-late-'+str(os.getpid()))
    child=subprocess.Popen([sys.executable,'-c','import time,pathlib;time.sleep(1);pathlib.Path('+repr(str(marker))+').write_text("late")'])
with state.open('a+') as stream:
    fcntl.flock(stream,fcntl.LOCK_EX);stream.seek(0);text=stream.read()
    data=json.loads(text) if text else {'active':0,'peak':0,'jobs':[]}
    data['active']+=1;data['peak']=max(data['peak'],data['active'])
    data['jobs'].append({'pid':os.getpid(),'child':child.pid if child else None,'output':output})
    if data['active']>=settings['expected']:gate.touch()
    stream.seek(0);stream.truncate();json.dump(data,stream);stream.flush()
limit=time.monotonic()+5
while not gate.exists():
    if time.monotonic()>limit:raise SystemExit('Concurrency barrier timed out')
    time.sleep(.005)
if settings.get('fail'):
    if b'ret i64 999' in source:
        time.sleep(.1);sys.stderr.write('deliberate parallel failure');raise SystemExit(1)
    time.sleep(10)
result=subprocess.run([REAL,*args],input=source)
with state.open('r+') as stream:
    fcntl.flock(stream,fcntl.LOCK_EX);data=json.load(stream);data['active']-=1
    stream.seek(0);stream.truncate();json.dump(data,stream)
raise SystemExit(result.returncode)
'''.replace('REAL',repr(shutil.which('clang'))))
    wrapper.chmod(0o700)
    config=work/'backend.json'
    config.write_text(json.dumps({'clang':str(wrapper),'target':None,'compile_jobs':20}))
    driver=work/'proof.php'
    driver.write_text('''<?php
declare(strict_types=1);
require $argv[1] . '/bootstrap.php';
class Parallel_Test {
    public static function check(bool $ok, string $message): void { if (!$ok) { throw new Exception($message); } }
    public static function phase(string $tag, int $expected, bool $fail = false): void {
        file_put_contents('settings.json', json_encode(compact('tag','expected','fail'), JSON_THROW_ON_ERROR));
    }
    public static function edit(int $i, int $value): void {
        $path = "src/f$i.phs"; clearstatcache(true,$path);$mtime=filemtime($path);
        file_put_contents($path, "function f$i(): int { return $value; }\\n");touch($path,$mtime+2);
    }
    public static function run(): void {
        $session = new \\compile\\Compiler_Session(backend_toolchain_path:getcwd().'/backend.json');
        self::phase('full',20);
        $first=$session->compile('project.json',getcwd().'/program');
        self::check(count($first->native->objects)===26,'26 file objects');
        $tool=(new ReflectionProperty($session,'toolchain'))->getValue($session);
        $count=$tool->invocation_count();
        self::check($session->compile('project.json',getcwd().'/program')->native===$first->native
            && $tool->invocation_count()===$count,'Unchanged launches no tools');
        foreach ([1,2,3] as $i) { self::edit($i,70+$i); }
        self::phase('increment',3);
        $edited=$session->compile('project.json',getcwd().'/program');
        self::check(!$edited->inputs->context->full_rebuild && $tool->invocation_count()-$count===4,'Three parallel objects plus one link');
        $before=$session->published;$key=hash_file('sha256','program');
        for($i=1;$i<=20;++$i){self::edit($i,$i===1?999:100+$i);}
        self::phase('failure',20,true);
        try {$session->compile('project.json',getcwd().'/program');throw new Exception('Expected parallel failure');}
        catch(RuntimeException $error){self::check(str_contains($error->getMessage(),'deliberate parallel failure'),$error->getMessage());}
        self::check($session->published===$before && hash_file('sha256','program')===$key,'Failure preserves publication');
        foreach($before->native->objects as $object){self::check($object->is_current($object->module),'Retained object survives cancellation');}
        self::phase('repair',20);
        self::edit(1,101);
        $repaired=$session->compile('project.json',getcwd().'/program');
        self::check($repaired->completed,'Repair succeeds');
        // Execution concurrency is not an IR/ABI dependency.
        $configuration=json_decode(file_get_contents('backend.json'),true,512,JSON_THROW_ON_ERROR);
        $configuration['compile_jobs']=1;file_put_contents('backend.json',json_encode($configuration));
        $count=$tool->invocation_count();
        self::check($session->compile('project.json',getcwd().'/program')->native===$repaired->native
            && $tool->invocation_count()===$count,'Scheduling-only change reuses artifact');
        foreach([0,-1,1.5,'20',null] as $invalid){
            $configuration['compile_jobs']=$invalid;file_put_contents('backend.json',json_encode($configuration));
            try {$session->compile('project.json',getcwd().'/program');throw new Exception('Expected invalid limit');}
            catch(InvalidArgumentException $error){self::check(str_contains($error->getMessage(),'compile_jobs'),$error->getMessage());}
        }
    }
}
Parallel_Test::run();
''')
    result=subprocess.run(['php',str(driver),str(ROOT)],cwd=work,env=dict(os.environ,XDEBUG_MODE='off'),capture_output=True,text=True,timeout=40)
    assert result.returncode==0,result.stdout+result.stderr
    for tag,expected,count in [('full',20,26),('increment',3,3),('failure',20,20),('repair',20,20)]:
        state=json.loads((work/(tag+'.json')).read_text())
        assert state['peak']==expected,(tag,state['peak'])
        assert len(state['jobs'])==count,(tag,len(state['jobs']))
        if tag!='failure': assert state['active']==0
        for job in state['jobs']: assert not Path(job['output']).exists(),('leaked object',job)
    time.sleep(1.1)
    assert not list(work.glob('failure-late-*')),'cancelled descendants wrote after rollback'
    assert not list(work.glob('.scpp-native-*')),'leaked staging'
    assert subprocess.run([str(work/'program')]).returncode==42
    # Same process owner enforces timeout and stops a sleeping descendant.
    timeout=work/'timeout.php'
    child_code='import subprocess,sys,time;subprocess.Popen([sys.executable,"-c",'+repr('import time,pathlib;time.sleep(.7);pathlib.Path('+repr(str(work/'timeout-late'))+').touch()')+']);time.sleep(10)'
    timeout.write_text('''<?php
require $argv[1].'/bootstrap.php';
$p=new \\tool_process\\Tool_Process([$argv[2],'-c',$argv[3]],'', $argv[4], .15);
try {while(!$p->ready()){usleep(1000);}throw new Exception('Expected timeout');}
catch(RuntimeException $e){if(!str_contains($e->getMessage(),'timed out')){throw $e;}}
finally{$p->close();}
''')
    subprocess.run(['php',str(timeout),str(ROOT),sys.executable,child_code,shutil.which('setsid')],check=True,timeout=5)
    time.sleep(.8)
    assert not (work/'timeout-late').exists(),'timeout descendant survived'
print('Parallel native: 20-job bound/full overlap, three-file incremental overlap, cache reuse, failed-batch cancellation, descendants, timeout, repair and limit validation passed.')
