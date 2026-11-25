#!/usr/bin/env python3
"""Separate instrumented Clang phase profiles, not benchmark replacements."""
import gzip,json,subprocess,tempfile,time
from pathlib import Path
HERE=Path(__file__).resolve().parent
ROOT=HERE.parents[3]
PREVIOUS=HERE.parent/'accounting_template_free_2026_10_18'
CONCRETE=PREVIOUS/'converted'
ORIGINAL=ROOT/'examples/accounting_workload/.prism/generated'
manifest=json.loads((PREVIOUS/'manifest.json').read_text())
report={}
(HERE/'traces').mkdir(exist_ok=True)
with tempfile.TemporaryDirectory(prefix='accounting-clang-profile-') as tmp:
 work=Path(tmp);(work/'runtime.hpp').write_text('#include <scpp/lang/php.hpp>\n')
 for opt in ['O0','O2']:
  base=['clang++','-std=c++23','-fPIC','-g','-pipe','-'+opt]
  cpp=base+['-I'+str(ROOT/'runtime/include'),'-I'+str(ORIGINAL),'-DSCPP_LANGUAGE_TARGET_PHP=1','-DSCPP_ENABLE_CALL_DEPTH_GUARD=0','-fpch-instantiate-templates']
  pch=work/(opt+'.pch')
  subprocess.run(list(map(str,cpp+['-x','c++-header',work/'runtime.hpp','-o',pch])),check=True,capture_output=True)
  for variant in ['concrete','eager']:
   sources=manifest['application_cpp']+manifest['support_sources'] if variant=='concrete' else ['src/ledger.cpp','src/money.cpp','src/checks.cpp']
   for source in sources:
    label=opt+'-'+variant+'-'+source.replace('/','_');obj=work/(label+'.o')
    if variant=='concrete':
     cmd=base+['-I'+str(CONCRETE)]
     if source in manifest['application_cpp']:cmd+=['-include',CONCRETE/'all.hpp']
     src=CONCRETE/source
    else:
     cmd=cpp+['-include-pch',pch,'-include',ORIGINAL/'__project_units/f74b50e9c799e8a2.hpp'];src=ORIGINAL/source
    cmd+=['-ftime-trace','-ftime-trace-granularity=100','-c',src,'-o',obj]
    started=time.perf_counter();run=subprocess.run(list(map(str,cmd)),capture_output=True,text=True);wall=time.perf_counter()-started
    if run.returncode:raise RuntimeError(run.stderr)
    raw=obj.with_suffix('.json').read_bytes();events=json.loads(raw)['traceEvents']
    with gzip.open(HERE/'traces'/(label+'.json.gz'),'wb') as f:f.write(raw)
    totals={e['name'][6:]:{'ms':e['dur']/1000,'count':e.get('args',{}).get('count')} for e in events if e['name'].startswith('Total ')}
    interesting=['Source','ParseClass','InstantiateClass','InstantiateFunction','CodeGen Function','OptFunction','RunPass','DevirtSCCRepeatedPass','PassManager<Function>','DebugType']
    top={kind:sorted([{'detail':e.get('args',{}).get('detail',''),'ms':e['dur']/1000} for e in events if e['name']==kind],key=lambda e:-e['ms'])[:12] for kind in interesting}
    report[label]={'opt':opt,'variant':variant,'source':source,'wall_ms':wall*1000,'totals':totals,'top':top,'command':list(map(str,cmd))}
    print(label,round(wall*1000),'ms',flush=True)
    (HERE/'profile.json').write_text(json.dumps(report,indent=2)+'\n')
