#!/usr/bin/env python3
"""Serial native edit benchmark; hand-emitted fixtures, real depfile/content checks."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import random
import shlex
import statistics
import subprocess
import tempfile
import time
from fixtures import emit as emit_runtime
from concrete_fixtures import emit as emit_concrete
def emit(root, variant, stage):
    return (emit_concrete if variant == "concrete" else emit_runtime)(root,variant,stage)
HERE=Path(__file__).resolve().parent
ROOT=HERE.parents[3]
def digest(p): return hashlib.sha256(p.read_bytes()).hexdigest()
def run(cmd, cwd=None):
    start=time.perf_counter()
    p=subprocess.run(list(map(str,cmd)),cwd=cwd,capture_output=True,text=True)
    if p.returncode: raise RuntimeError(f'{cmd}\n{p.stdout}\n{p.stderr}')
    return (time.perf_counter()-start)*1000,p.stdout
p=argparse.ArgumentParser()
p.add_argument('--repeats',type=int,default=3)
p.add_argument('--opts',nargs='+',default=['O0','O2'])
p.add_argument('--variants',nargs='+',default=['exposed','adapter','lowered'])
p.add_argument('--output',type=Path,default=HERE/'results.json')
args=p.parse_args()
report={'repeats':args.repeats,'clang':run(['clang++','--version'])[1], 'load_start':os.getloadavg(),
        'sources':{f.name:digest(f) for f in HERE.glob('*.py')},'results':{},'commands':[]}
library=ROOT/'examples/accounting_workload/.prism/runtime/project/php-strict/116def5680c631b9/libruntime.so'
report['runtime_sha256']=digest(library)
with tempfile.TemporaryDirectory(prefix='vector-specialization-') as temp:
    work=Path(temp)
    for opt in args.opts:
        cpp=['clang++','-std=c++23','-fPIC','-g','-pipe','-'+opt,
             '-I'+str(ROOT/'runtime/include'),'-DSCPP_LANGUAGE_TARGET_PHP=1','-DSCPP_ENABLE_CALL_DEPTH_GUARD=0']
        (work/'runtime.hpp').write_text('#include <scpp/lang/php.hpp>\n')
        pch=work/(opt+'.pch')
        pch_ms=0
        if any(v != 'concrete' for v in args.variants):
            pch_ms,_=run(cpp+['-x','c++-header',work/'runtime.hpp','-o',pch])
        def retained_state():
            return ((digest(pch),pch.stat().st_mtime_ns) if pch.exists() else None, digest(library),library.stat().st_mtime_ns)
        before=retained_state()
        samples={v:{s:[] for s in ['base','body','layout','specialization','unchanged']} for v in args.variants}
        for trial in range(args.repeats):
            order=list(samples);random.Random(41+trial).shuffle(order)
            for variant in order:
                directory=work/f'{opt}-{trial}-{variant}'
                retained={}
                for stage in samples[variant]:
                    spec=emit(directory,variant,'specialization' if stage=='unchanged' else stage)
                    actions=[]
                    for source in spec['sources']:
                        previous=retained.get(source)
                        if previous and all(f.exists() and digest(f)==h for f,h in previous['dependencies'].items()) and digest(directory/(source+'.o'))==previous['object_hash']:
                            continue
                        flags=cpp[:]
                        if variant != 'concrete' and (source in ['Row.cpp','Other.cpp'] or variant=='exposed' or (variant=='adapter' and source.endswith('Vector.cpp'))):
                            flags+=['-include-pch',pch]
                        command=flags+['-MMD','-MF',source+'.d','-c',source,'-o',source+'.o']
                        ms,_=run(command,directory)
                        report['commands'].append({'opt':opt,'trial':trial,'variant':variant,'stage':stage,'command':list(map(str,command)),'ms':ms})
                        dep=(directory/(source+'.d')).read_text().replace('\\\n',' ')
                        deps=[Path(x) if Path(x).is_absolute() else directory/x for x in shlex.split(dep.split(':',1)[1])]
                        retained[source]={'dependencies':{f:digest(f) for f in deps},'object_hash':digest(directory/(source+'.o'))}
                        actions.append({'source':source,'ms':ms})
                    link_ms=0
                    if actions:
                        link_ms,_=run(['clang++']+[directory/(s+'.o') for s in spec['sources']]+[library,'-Wl,-rpath,'+str(library.parent),'-o',directory/'app'])
                    _,out=run([directory/'app'])
                    assert out==spec['expected'],(variant,stage,out)
                    if stage=='unchanged': assert not actions
                    if stage=='body': assert [a['source'] for a in actions]==['app.cpp']
                    if stage=='layout': assert [a['source'] for a in actions]==(['Row.cpp','app.cpp'] if variant=='exposed' else ['Row.cpp'])
                    if stage=='specialization': assert [a['source'] for a in actions]==(['Other.cpp','app.cpp'] if variant=='exposed' else ['Other.cpp','OtherVector.cpp','app.cpp'])
                    item={'actions':actions,'compile_ms':sum(a['ms'] for a in actions),'link_ms':link_ms,
                          'total_ms':sum(a['ms'] for a in actions)+link_ms,'behavior_passed':True}
                    samples[variant][stage].append(item)
                    print(opt,trial+1,variant,stage,round(item['total_ms'],1),[a['source'] for a in actions],flush=True)
        assert before==retained_state()
        report['results'][opt]={'pch_setup_ms':pch_ms,'retained_runtime_unchanged':True,'samples':samples,
             'medians':{v:{s:{key:statistics.median(x[key] for x in data) for key in ['compile_ms','link_ms','total_ms']} for s,data in stages.items()} for v,stages in samples.items()}}
        args.output.write_text(json.dumps(report,indent=2)+'\n')
        # Keep final emitted sources as inspectable evidence, without build products.
        for variant in samples:
            emit(HERE/'fixtures'/variant,variant,'specialization')
report['load_end']=os.getloadavg()
args.output.write_text(json.dumps(report,indent=2)+'\n')
print(args.output)
