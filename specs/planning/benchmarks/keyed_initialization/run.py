"""Focused generated keyed-table costs; no baseline or construction-throughput claim."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import statistics
import subprocess
import time

ROOT = Path(__file__).resolve().parents[4]
PHP = r'''
require $argv[1].'/compiler/boot.php';
use scpp\compiler\{Source_Frontend,source_file,flavor,entry_target,Cpp_Emitter};
$f=flavor::from($argv[2]);$n=(int)$argv[3];$kind=$argv[4];
$entries=[];
for($i=0;$i<$n;$i++) $entries[]=($kind==='int' ? (string)$i : '"key'.$i.'"').' => '.($kind==='int' ? (string)$i : '"heap-backed value for entry '.$i.'"');
$literal='['.implode(',',$entries).']';$type='hash<'.$kind.','.$kind.'>';
$source=match($f) {flavor::php=>'$h '.$type.' = '.$literal.';', flavor::cpp=>$type.' h = '.$literal.';',flavor::js=>'let h:'.$type.' = '.$literal.';'};
$var=$f===flavor::php?'$h':'h';
$source.='echo count('.$var.');return 8;';
$start=hrtime(true);$entry=(new Source_Frontend())->prepare(new source_file('timing',$source,$f),new entry_target(31));$middle=hrtime(true);
$cpp=(new Cpp_Emitter())->emit($entry);$end=hrtime(true);
echo json_encode(['prepare_ms'=>($middle-$start)/1e6,'emit_ms'=>($end-$middle)/1e6,'source_bytes'=>strlen($source),'cpp'=>$cpp],JSON_THROW_ON_ERROR);
'''


def call(command, expected=0):
    start=time.perf_counter()
    result=subprocess.run(list(map(str,command)),capture_output=True,env={**os.environ,'XDEBUG_MODE':'off'})
    elapsed=(time.perf_counter()-start)*1000
    assert result.returncode==expected,(command,result.stdout,result.stderr)
    return elapsed,result.stdout


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--out',type=Path,required=True)
    parser.add_argument('--objects',type=Path,required=True,help='Parent of native-clang and native-gcc proof objects')
    parser.add_argument('--php',default='php8.5')
    args=parser.parse_args();args.out.mkdir(parents=True,exist_ok=True)
    results={'host':os.uname().nodename,'flags':'C++23 O2 g0, libstdc++, default linker, sequential, no LTO/PCH/ccache, proof runtime objects reused',
             'runtime_sha256':{p:hashlib.sha256((ROOT/'runtime/generated/src'/p).read_bytes()).hexdigest() for p in ('runtime.cpp','hash.cpp')},'cases':[]}
    for cc,folder in [('clang++','native-clang'),('g++','native-gcc')]:
        results[cc]=call([cc,'--version'])[1].decode().splitlines()[0]
        objects=[args.objects/folder/(name+'.o') for name in ('runtime','hash','output','arithmetic')]
        assert all(p.exists() for p in objects)
        for flavor in ('cpp','php','js'):
            for kind,count in [('int',3),('int',64),('int',65),('string',16)]:
                row={'compiler':cc,'flavor':flavor,'kind':kind,'count':count,'samples':[]}
                work=args.out/(folder+'-'+flavor+'-'+kind+str(count));work.mkdir(exist_ok=True)
                unit,obj,exe=work/'table.cpp',work/'table.o',work/'table'
                flags=['-std=c++23','-O2','-g0','-Wall','-Wextra','-Werror','-I',ROOT/'runtime/generated/public']
                for repeat in range(3):
                    php_wall,data=call([args.php,'-r',PHP,ROOT,flavor,count,kind]);payload=json.loads(data)
                    unit.write_text(payload.pop('cpp'));row['cpp_bytes']=unit.stat().st_size
                    compile_ms,_=call([cc,*flags,'-c',unit,'-o',obj]);link_ms,_=call([cc,obj,*objects,'-o',exe])
                    execute_ms,output=call([exe],8);assert output==str(count).encode()
                    row['samples'].append(payload|{'php_wall_ms':php_wall,'compile_ms':compile_ms,'link_ms':link_ms,'native_ms':compile_ms+link_ms,'execute_process_ms':execute_ms})
                if cc=='clang++':
                    call([cc,*flags,'-ftime-trace','-c',unit,'-o',work/'profile.o'])
                    trace=json.loads((work/'profile.json').read_text())
                    row['profile_ms']={e['name']:e.get('dur',0)/1000 for e in trace['traceEvents'] if e['name'] in ('Total Frontend','Total Backend','Total Optimizer','Total CodeGenPasses')}
                results['cases'].append(row)
    (args.out/'results.json').write_text(json.dumps(results,indent=2)+'\n')
    print('PASS 72 clean compile/link/executable samples plus 12 separate Clang profiles')


if __name__=='__main__':main()
