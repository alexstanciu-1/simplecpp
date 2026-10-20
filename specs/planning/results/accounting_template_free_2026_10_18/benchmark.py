#!/usr/bin/env python3
"""Whole accounting native compilation comparison. Lowering is not timed."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import random
import statistics
import subprocess
import tempfile
import time
HERE=Path(__file__).resolve().parent
ROOT=HERE.parents[3]
ORIGINAL=ROOT/'examples/accounting_workload/.prism/generated'
CONCRETE=HERE/'converted'
LIBRARY=ROOT/'examples/accounting_workload/.prism/runtime/project/php-strict/116def5680c631b9/libruntime.so'
MANIFEST=json.loads((HERE/'manifest.json').read_text())
EXPECTED='accounting workload: invoice/payment/trial-balance checks passed\nCHECKS passed=998 failed=0\nACCOUNTING_WORKLOAD_PASS\n'
parser=argparse.ArgumentParser()
parser.add_argument('--repeats',type=int,default=3)
parser.add_argument('--opts',nargs='+',default=['O0','O2'])
parser.add_argument('--variants',nargs='+',default=['original','concrete'])
parser.add_argument('--validate-only',action='store_true')
parser.add_argument('--native-debug',action='store_true',help='Opt in to Clang debug information')
parser.add_argument('--output',type=Path,default=HERE/'no_debug_results.json')
args=parser.parse_args()
def digest(path):return hashlib.sha256(path.read_bytes()).hexdigest()
report={'clang':subprocess.check_output(['clang++','--version'],text=True),'repeats':args.repeats,'native_debug':args.native_debug,'load_start':os.getloadavg(),
        'original_sha256':MANIFEST['original_files'],'converted_sha256':{str(f.relative_to(CONCRETE)):digest(f) for f in CONCRETE.rglob('*') if f.is_file()},
        'runtime_sha256':digest(LIBRARY),'commands':[],'results':{}}
def run(cmd,label):
 start=time.perf_counter();p=subprocess.run(list(map(str,cmd)),capture_output=True,text=True)
 elapsed=time.perf_counter()-start
 report['commands'].append({'label':label,'command':list(map(str,cmd)),'seconds':elapsed,'returncode':p.returncode,'stderr':p.stderr})
 if p.returncode:raise RuntimeError(f'{label}\n{p.stderr[:12000]}')
 return elapsed,p.stdout
try:
 with tempfile.TemporaryDirectory(prefix='accounting-template-free-') as temp:
  work=Path(temp)
  for opt in args.opts:
   base=['clang++','-std=c++23','-fPIC','-g' if args.native_debug else '-g0','-pipe','-'+opt]
   cpp=base+['-I'+str(ROOT/'runtime/include'),'-I'+str(ORIGINAL),'-DSCPP_LANGUAGE_TARGET_PHP=1','-DSCPP_ENABLE_CALL_DEPTH_GUARD=0']
   pchs={};pch_times={}
   (work/'runtime.hpp').write_text('#include <scpp/lang/php.hpp>\n')
   for v in args.variants:
    if v=='concrete':continue
    pch=work/(opt+'-'+v+'.pch');pchs[v]=pch
    pch_times[v],_=run(cpp+(['-fpch-instantiate-templates'] if v=='eager' else [])+['-x','c++-header',work/'runtime.hpp','-o',pch],opt+'-'+v+'-PCH')
   result={'pch_setup_s':pch_times,'variants':{name:[] for name in args.variants}}
   def build(variant,trial):
    objects=[];units=[];setup=[]
    directory=work/(opt+'-'+variant);directory.mkdir(exist_ok=True)
    sources=(MANIFEST['support_sources'] if variant=='concrete' else [])+MANIFEST['application_cpp']
    for source in sources:
     obj=directory/(source.replace('/','_')+'.o');objects.append(obj)
     if variant!='concrete':
      flags=cpp+(['-fpch-instantiate-templates'] if variant=='eager' else [])+['-include-pch',pchs[variant],'-include',next(ORIGINAL.glob('__project_units/*.hpp'))]
      src=ORIGINAL/source
     else:
      flags=base+['-I'+str(CONCRETE)];src=CONCRETE/source
      if source in MANIFEST['application_cpp']:flags+=['-include',CONCRETE/'all.hpp']
     seconds,_=run(flags+['-c',src,'-o',obj],f'{opt}-{trial}-{variant}-{source}')
     item={'source':source,'seconds':seconds,'object_bytes':obj.stat().st_size}
     (units if source in MANIFEST['application_cpp'] else setup).append(item)
    link=['clang++',*objects]
    if variant!='concrete':link += [LIBRARY,'-Wl,-rpath,'+str(LIBRARY.parent)]
    executable=directory/'app';link+=['-o',executable]
    link_s,_=run(link,f'{opt}-{trial}-{variant}-link')
    _,output=run([executable],f'{opt}-{trial}-{variant}-execute')
    assert output==EXPECTED,(variant,output)
    app_s=sum(x['seconds'] for x in units);support_s=sum(x['seconds'] for x in setup)
    item={'units':units,'support':setup,'application_compile_s':app_s,'support_compile_s':support_s,
          'link_s':link_s,'total_native_s':app_s+support_s+link_s,'output':output,'behavior_passed':True}
    result['variants'][variant].append(item)
    print(opt,trial,variant,'app',round(app_s,3),'support',round(support_s,3),'link',round(link_s,3),'998 checks passed',flush=True)
   count=1 if args.validate_only else args.repeats
   for trial in range(count):
    variants=list(args.variants);random.Random(100+trial).shuffle(variants)
    for variant in variants:build(variant,trial)
   result['medians']={v:{key:statistics.median(x[key] for x in rows) for key in ['application_compile_s','support_compile_s','link_s','total_native_s']} for v,rows in result['variants'].items()}
   report['results'][opt]=result
   args.output.write_text(json.dumps(report,indent=2)+'\n')
 report['load_end']=os.getloadavg()
finally:
 args.output.write_text(json.dumps(report,indent=2)+'\n')
