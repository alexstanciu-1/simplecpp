<?php
declare(strict_types=1);
require __DIR__.'/../../tools/php_portability/runtime/bootstrap.php';
function verify_os(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }
function completed(scpp\Process_Handle $process): scpp\Process_Output {
    $deadline = hrtime(true)+5000000000;
    while (!scpp\process_poll($process)) {
        if (hrtime(true)>$deadline) { scpp\process_close($process); throw new RuntimeException('Test deadline exceeded'); }
        usleep(1000);
    }
    return scpp\process_output($process);
}
function fails_os(Closure $operation): void {
    try { $operation(); } catch (RuntimeException) { return; }
    throw new LogicException('Expected operational failure');
}
if (($argv[1] ?? '') === 'lock-child') {
    $lock = scpp\lock_empty();
    exit(scpp\lock_try($lock,$argv[2],($argv[3]??'')==='shared') ? 0 : 2);
}
$directory = '/tmp/scpp-os-proof-'.bin2hex(random_bytes(8));mkdir($directory,0700);
$path = $directory.'/lock';file_put_contents($path,'preserve');$inode=fileinode($path);
function contender(string $path, bool $shared = false): int {
    $p=scpp\process_spawn(PHP_BINARY,[__FILE__,'lock-child',$path,$shared?'shared':'exclusive'],'',1000,'');
    try { return completed($p)->exit_code; } finally { scpp\process_close($p); }
}
try {
    $lock=scpp\lock_empty();verify_os(scpp\lock_try($lock,$path,false),'writer acquisition');
    $alias=$lock;verify_os(contender($path)===2 && contender($path,true)===2,'independent contention');
    fails_os(static fn()=>scpp\lock_try($lock,$path,false));
    $moved=scpp\lock_transfer($lock);scpp\lock_release($alias);
    verify_os(contender($path)===2,'transfer retains lock and invalidates aliases');
    scpp\lock_release($moved);scpp\lock_release($moved);verify_os(contender($path)===0,'reacquire');
    verify_os(file_get_contents($path)==='preserve' && fileinode($path)===$inode,'stable identity/nontruncation');
    $reader=scpp\lock_empty();verify_os(scpp\lock_try($reader,$path,true),'shared');
    verify_os(contender($path,true)===0 && contender($path)===2,'shared coexistence');scpp\lock_release($reader);
    fails_os(static function()use($directory){$h=scpp\lock_empty();scpp\lock_try($h,$directory.'/missing',true);});
    fails_os(static function()use($directory){$h=scpp\lock_empty();scpp\lock_try($h,$directory,false);});
    posix_mkfifo($directory.'/fifo',0600);
    fails_os(static function()use($directory){$h=scpp\lock_empty();scpp\lock_try($h,$directory.'/fifo',true);});
    unlink($directory.'/fifo');
    $h=scpp\lock_empty();scpp\lock_try($h,$path,false);unset($h);verify_os(contender($path)===0,'destructor release');
    // Inherited tokens cannot explicitly unlock. Parent unlock works before child exit.
    $h=scpp\lock_empty();scpp\lock_try($h,$path,false);
    $pair=stream_socket_pair(STREAM_PF_UNIX,STREAM_SOCK_STREAM,0);$pid=pcntl_fork();
    if($pid===0){fclose($pair[0]);try{scpp\lock_release($h);fwrite($pair[1],'bad');}catch(RuntimeException){fwrite($pair[1],'ok');}fread($pair[1],1);unset($h);exit(0);}
    fclose($pair[1]);verify_os(fread($pair[0],2)==='ok','inherited release rejected');
    scpp\lock_release($h);verify_os(contender($path)===0,'parent unlock with inherited fd');
    fwrite($pair[0],'G');fclose($pair[0]);pcntl_waitpid($pid,$status);
    echo "locks: contention, transfer, inheritance, identity and cleanup passed\n";

    $input="a\0\xff";
    $p=scpp\process_spawn('/bin/sh',['-c','cat; printf error >&2; exit 7'],$input,1000,'');$alias=$p;
    $out=completed($p);verify_os($out->stdout_text===$input && $out->stderr_text==='error' && $out->exit_code===7,'binary streams/nonzero');
    $out->stdout_text='edited';verify_os(scpp\process_output($p)->stdout_text===$input,'independent output snapshot');
    scpp\process_close($alias);scpp\process_close($p);fails_os(static fn()=>scpp\process_poll($p));
    $p=scpp\process_spawn('/bin/sh',['-c','exit 127'],'',0,'');verify_os(completed($p)->exit_code===127,'127 is legitimate');scpp\process_close($p);
    fails_os(static fn()=>scpp\process_spawn('/not/a/program',[],'',0,''));
    fails_os(static fn()=>scpp\process_spawn('/bin/true',[],'',0,'/not/a/directory'));
    fails_os(static fn()=>scpp\process_spawn('relative',[],'',0,''));
    $payload=str_repeat('x',200000);
    $p=scpp\process_spawn(PHP_BINARY,['-r','echo str_repeat("y",200000); fwrite(STDERR,str_repeat("z",200000)); echo stream_get_contents(STDIN);'],$payload,2000,'');
    $out=completed($p);verify_os($out->stdout_text===str_repeat('y',200000).$payload && strlen($out->stderr_text)===200000,'large output before input');scpp\process_close($p);
    $literal='hello;$(false)';$p=scpp\process_spawn(PHP_BINARY,['-r','echo $argv[1],"|",getcwd();',$literal],'',1000,$directory);
    verify_os(completed($p)->stdout_text===$literal.'|'.$directory,'literal argv/cwd');scpp\process_close($p);
    $p=scpp\process_spawn('/bin/sleep',['10'],'',30,'');fails_os(static fn()=>scpp\process_output($p));$out=completed($p);
    verify_os($out->timed_out && !$out->stopped && $out->signal===SIGKILL,'timeout cause');scpp\process_close($p);
    $p=scpp\process_spawn('/bin/sleep',['10'],'',0,'');scpp\process_stop($p);scpp\process_stop($p);$out=completed($p);
    verify_os(!$out->timed_out && $out->stopped && $out->signal===SIGKILL,'stop cause');scpp\process_close($p);
    $p=scpp\process_spawn('/bin/true',[],'',1,'');usleep(30000);$out=completed($p);
    verify_os(!$out->timed_out && $out->exit_code===0,'exit wins over late poll');scpp\process_close($p);
    // Target cannot retain the private launch FIFO (including after exec).
    $p=scpp\process_spawn(PHP_BINARY,['-r','foreach(glob("/proc/self/fd/*") as $f){$v=@readlink($f);if(is_string($v)&&(int)basename($f)>2&&(str_contains($v,"scpp-launch-")||str_contains($v,"process_launcher.php")||str_contains($v,"scpp-process-")))echo "leak";}'],'',1000,'');
    verify_os(completed($p)->stdout_text==='','control descriptor closes on exec');scpp\process_close($p);
    for($i=0;$i<5;$i++){$p=scpp\process_spawn('/bin/sleep',['10'],'',0,'');scpp\process_close($p);scpp\process_close($p);}
    foreach (['normal','timeout','stop','close','destruct'] as $mode) {
        $marker=$directory.'/descendant';
        $program='$pid=pcntl_fork();if($pid===0){sleep(20);exit(0);}file_put_contents($argv[1],(string)$pid);if($argv[2]!=="normal")sleep(20);';
        $child=scpp\process_spawn(PHP_BINARY,['-r',$program,$marker,$mode],'',$mode==='timeout'?100:0,'');
        $until=hrtime(true)+2000000000;
        while (!is_file($marker) && hrtime(true)<$until) { usleep(1000); }
        verify_os(is_file($marker),'descendant ready');$descendant=(int)file_get_contents($marker);
        if ($mode==='stop') { scpp\process_stop($child); }
        if ($mode==='close') { scpp\process_close($child); }
        elseif ($mode==='destruct') { unset($child); }
        else { completed($child);scpp\process_close($child); }
        $until=hrtime(true)+2000000000;
        do {
            $stat=@file_get_contents('/proc/'.$descendant.'/stat');
            $gone=$stat===false || preg_match('/\) [ZX] /',$stat)===1;
            if (!$gone) { usleep(1000); }
        } while (!$gone && hrtime(true)<$until);
        verify_os($gone,'descendant cleanup: '.$mode);unlink($marker);
    }
    $p=scpp\process_spawn('/bin/sleep',['10'],'',0,'');
    $pid=pcntl_fork();
    if($pid===0){try{scpp\process_poll($p);exit(3);}catch(RuntimeException){unset($p);exit(0);}}
    pcntl_waitpid($pid,$status);verify_os(pcntl_wexitstatus($status)===0 && !scpp\process_poll($p),'inherited process token cannot operate or kill parent child');scpp\process_close($p);
    pcntl_signal(SIGCHLD,SIG_IGN);
    try { fails_os(static fn()=>scpp\process_spawn('/bin/true',[],'',0,'')); }
    finally { pcntl_signal(SIGCHLD,SIG_DFL); }
    $before=count(scandir('/proc/self/fd'));
    for($i=0;$i<12;$i++){
        fails_os(static fn()=>scpp\process_spawn('/not/a/program',[],'',0,''));
        $p=scpp\process_spawn('/bin/true',[],'',0,'');completed($p);scpp\process_close($p);
    }
    verify_os(count(scandir('/proc/self/fd'))===$before,'descriptor stability');
    echo "processes: descendant cleanup, inherited tokens, SIGCHLD and descriptor stability passed\n";
    echo "processes: handshake, exec errors, binary IO, snapshots, timeout, stop and cleanup passed\n";
} finally { unlink($path);rmdir($directory); }
