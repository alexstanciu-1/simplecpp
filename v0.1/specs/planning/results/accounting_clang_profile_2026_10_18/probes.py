#!/usr/bin/env python3
"""Controlled header exposure and debug-info probes; no source-body changes."""
import json,random,re,statistics,subprocess,tempfile,time
from pathlib import Path
HERE=Path(__file__).resolve().parent
PREVIOUS=HERE.parent/'accounting_template_free_2026_10_18'
C=PREVIOUS/'converted'
m=json.loads((PREVIOUS/'manifest.json').read_text())
expected='accounting workload: invoice/payment/trial-balance checks passed\nCHECKS passed=998 failed=0\nACCOUNTING_WORKLOAD_PASS\n'
report={'headers':{},'runs':[],'commands':[]}
def run(cmd,check=True):
 start=time.perf_counter();p=subprocess.run(list(map(str,cmd)),capture_output=True,text=True);elapsed=time.perf_counter()-start
 if check and p.returncode:raise RuntimeError(p.stderr)
 return elapsed,p
with tempfile.TemporaryDirectory(prefix='accounting-profile-probes-') as tmp:
 w=Path(tmp)
 headers=re.findall(r'#include "([^"]+)"',(C/'all.hpp').read_text())
 for source in m['application_cpp']:
  selected=headers[:];wrapper=w/(source.replace('/','_')+'.hpp')
  def write():wrapper.write_text(''.join('#include "'+h+'"\n' for h in selected))
  for h in headers:
   selected.remove(h);write()
   _,p=run(['clang++','-std=c++23','-I'+str(C),'-include',wrapper,'-fsyntax-only',C/source],False)
   if p.returncode:selected.insert(headers.index(h) if headers.index(h)<len(selected) else len(selected),h)
  write()
  _,p=run(['clang++','-std=c++23','-I'+str(C),'-include',wrapper,'-fsyntax-only',C/source])
  report['headers'][source]=selected
 print('Header pruning complete',flush=True)
 for opt in ['O0','O2']:
  for trial in range(3):
   variants=['broad-g','narrow-g','broad-g0'];random.Random(trial+31).shuffle(variants)
   for variant in variants:
    units=[];objects=[]
    for source in m['application_cpp']+m['support_sources']:
     obj=w/(source.replace('/','_')+'.o');objects.append(obj)
     cmd=['clang++','-std=c++23','-fPIC','-pipe','-'+opt,'-g0' if variant.endswith('g0') else '-g','-I'+str(C)]
     if source in m['application_cpp']:cmd+=['-include',w/(source.replace('/','_')+'.hpp') if variant=='narrow-g' else C/'all.hpp']
     cmd+=['-c',C/source,'-o',obj]
     elapsed,_=run(cmd);units.append({'source':source,'seconds':elapsed,'object_bytes':obj.stat().st_size})
     report['commands'].append(list(map(str,cmd)))
    link,_=run(['clang++',*objects,'-o',w/'app']);_,p=run([w/'app']);assert p.stdout==expected
    row={'opt':opt,'trial':trial,'variant':variant,'units':units,'compile_s':sum(x['seconds'] for x in units),'app_s':sum(x['seconds'] for x in units if x['source'] in m['application_cpp']),'link_s':link,'checks':998}
    report['runs'].append(row)
    (HERE/'probes.json').write_text(json.dumps(report,indent=2)+'\n')
    print(opt,trial,variant,round(row['compile_s'],3),'998 checks passed',flush=True)
report['medians']={opt:{v:{k:statistics.median(r[k] for r in report['runs'] if r['opt']==opt and r['variant']==v) for k in ['compile_s','app_s','link_s']} for v in ['broad-g','narrow-g','broad-g0']} for opt in ['O0','O2']}
(HERE/'probes.json').write_text(json.dumps(report,indent=2)+'\n')
