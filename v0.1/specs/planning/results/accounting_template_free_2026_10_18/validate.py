#!/usr/bin/env python3
"""Extra behavioral, sanitizer and template-exposure checks; outside benchmark timings."""
import json
import os
from pathlib import Path
import subprocess
import tempfile
HERE=Path(__file__).resolve().parent
ROOT=HERE.parents[3]
ORIGINAL=ROOT/'examples/accounting_workload/.prism/generated'
OUT=HERE/'converted'
MANIFEST=json.loads((HERE/'manifest.json').read_text())
report={'commands':[]}
def run(cmd,label,env=None):
 p=subprocess.run(list(map(str,cmd)),capture_output=True,text=True,env=env)
 report['commands'].append({'label':label,'command':list(map(str,cmd)),'exit_code':p.returncode,'stdout':p.stdout if label.endswith('execute') else '', 'stderr':p.stderr[:15000]})
 if p.returncode:raise RuntimeError(label+'\n'+p.stderr[:15000])
 return p.stdout
try:
 with tempfile.TemporaryDirectory(prefix='accounting-concrete-validation-') as tmp:
  work=Path(tmp)
  # A standalone original-runtime reference for the same container probes.
  cpp=['clang++','-std=c++23','-O0','-g','-I'+str(ROOT/'runtime/include'),'-I'+str(ORIGINAL),'-DSCPP_LANGUAGE_TARGET_PHP=1','-DSCPP_ENABLE_CALL_DEPTH_GUARD=0','-include',next(ORIGINAL.glob('__project_units/*.hpp'))]
  library=ROOT/'examples/accounting_workload/.prism/runtime/project/php-strict/116def5680c631b9/libruntime.so'
  run(cpp+['-DPROBE_MAIN=1',HERE/'probes.cpp',ORIGINAL/'src/core.cpp',library,'-Wl,-rpath,'+str(library.parent),'-o',work/'original-probes'],'original-probes-compile')
  assert run([work/'original-probes'],'original-probes-execute')=='CONTAINER_PROBES_PASS\n'
  # Template-free app and all support, instrumented together.
  flags=['clang++','-std=c++23','-O1','-g','-fsanitize=address,undefined','-fno-omit-frame-pointer','-DSCPP_OWNER_AUDIT=1','-I'+str(OUT)]
  objects=[]
  for source in MANIFEST['support_sources']+MANIFEST['application_cpp']:
   obj=work/(source.replace('/','_')+'.o');objects.append(obj)
   cmd=flags[:]
   if source in MANIFEST['application_cpp']:cmd+=['-include',OUT/'all.hpp']
   if source=='main.cpp':cmd+=['-Dmain=accounting_original_entry']
   run(cmd+['-c',OUT/source,'-o',obj],source+'-sanitizer-compile')
  run(flags+['-DCONCRETE_PROBE=1','-include',OUT/'all.hpp','-c',HERE/'probes.cpp','-o',work/'probes.o'],'concrete-probes-compile')
  driver=work/'driver.cpp'
  driver.write_text('#include "all.hpp"\nint concrete_probes();\nint main(int argc,char** argv) {concrete_probes();if(scpp::outstanding_owners()!=0) return 90;scpp::php::set_cli_args(argc,argv);int result=scpp::__scpp_main();if(scpp::outstanding_owners()!=0) return 91;return result;}\n')
  run(flags+[driver,*objects,work/'probes.o','-o',work/'app'],'sanitizer-link')
  env=dict(os.environ,ASAN_OPTIONS='detect_leaks=0',UBSAN_OPTIONS='halt_on_error=1')
  output=run([work/'app'],'sanitizer-execute',env)
  assert 'CHECKS passed=998 failed=0\n' in output and 'ACCOUNTING_WORKLOAD_PASS' in output
  report['asan_ubsan_passed']=True;report['leak_detection']=False;report['owner_balance_passed']=True
  # Audit every native input, including support, after preprocessing. No template
  # declarations may be introduced indirectly by a standard-library header.
  import re
  audits={}
  for source in MANIFEST['support_sources']+MANIFEST['application_cpp']:
   cmd=['clang++','-std=c++23','-I'+str(OUT)]
   if source in MANIFEST['application_cpp']:cmd+=['-include',OUT/'all.hpp']
   text=run(cmd+['-E',OUT/source],source+'-preprocess')
   count=len(re.findall(r'\btemplate\s*<',text))
   audits[source]={'template_declarations':count,'preprocessed_bytes':len(text.encode())}
   assert count==0,(source,count)
  report['template_audit']=audits
finally:
 (HERE/'validation_extra.json').write_text(json.dumps(report,indent=2)+'\n')
print('Original and concrete container probes, ASan/UBSan, and template audit passed')
