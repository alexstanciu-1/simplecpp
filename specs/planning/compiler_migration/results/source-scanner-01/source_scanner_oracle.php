<?php
declare(strict_types=1);
require __DIR__.'/../../compiler/bootstrap.php';
require __DIR__.'/oracles/source_scanner_original.php';
function scanner_outcome(string $class, read_sources\source_scan_task $task): array {
    try {
        $result=$class::scan($task);
        if($result->task!==$task) {throw new LogicException('Scanner lost task identity');}
        return ['rows', array_map(fn($row)=>[$row->relative_path,$row->mtime,$row->size],$result->files),$result->directories];
    } catch(Exception $e) {return ['error',get_class($e),$e->getMessage()];}
}
$root=sys_get_temp_dir().'/scpp-scan-oracle-'.bin2hex(random_bytes(8));mkdir($root);mkdir($root.'/good');mkdir($root.'/good/sub');mkdir($root.'/bad');
$cases=0;
try {
    foreach(['a.phs'=>'a','z.phs'=>'','note.txt'=>'ignored',"\xff.phs"=>'bytes'] as $name=>$bytes) {file_put_contents($root.'/good/'.$name,$bytes);touch($root.'/good/'.$name,1700000000);}
    symlink($root.'/good',$root.'/linked');symlink($root.'/good/a.phs',$root.'/bad/link.txt');
    $tasks=[new read_sources\source_scan_task(1,0,$root,'good'),new read_sources\source_scan_task(2,0,$root,'good',['z.phs','note.txt','a.phs']),new read_sources\source_scan_task(3,0,$root,'missing',[]),new read_sources\source_scan_task(4,0,$root,'missing'),new read_sources\source_scan_task(5,0,$root,'linked'),new read_sources\source_scan_task(6,0,$root,'bad'),new read_sources\source_scan_task(7,0,$root,'good',['missing.phs']),new read_sources\source_scan_task(8,0,$root,'good',['sub']),new read_sources\source_scan_task(9,0,$root,'good',['a.phs','a.phs']),new read_sources\source_scan_task(10,0,$root,'good',['.','..'])];
    foreach($tasks as $task) {
        $before=serialize($task);
        if(scanner_outcome(read_sources\Baseline_Source_Scanner::class,$task)!==scanner_outcome(read_sources\Source_Scanner::class,$task) || serialize($task)!==$before) {throw new RuntimeException('Scanner mismatch: '.$task->index);}
        ++$cases;
    }
    $task=new read_sources\source_scan_task(11,0,$root,'good',['a.phs']);
    $before=read_sources\Source_Scanner::scan($task);
    file_put_contents($root.'/good/a.phs','replacement');touch($root.'/good/a.phs',1700000100);
    $after=read_sources\Source_Scanner::scan($task);
    if($after->files[0]->size!==11 || $after->files[0]->mtime!==1700000100 || $before->files[0]->size!==1 || $before->files[0]->mtime!==1700000000) {throw new RuntimeException('Stale PHP filesystem metadata or mutated result');}
    if(scanner_outcome(read_sources\Baseline_Source_Scanner::class,$task)!==scanner_outcome(read_sources\Source_Scanner::class,$task)) {throw new RuntimeException('Refresh mismatch');}
    ++$cases;
    echo $cases," scanner scenarios match original; refresh metadata, order, provenance and retained results proved\n";
} finally {
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        if($file->isDir() && !$file->isLink()) {rmdir($file->getPathname());}else{unlink($file->getPathname());}
    }
    rmdir($root);
}
