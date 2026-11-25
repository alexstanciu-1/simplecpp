from pathlib import Path
import sys,json,time,statistics
ROOT=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');OUT=Path('/tmp/accounting-header-impact');PUB=Path('/tmp/accounting-timing/prepared')
sys.path.insert(0,str(ROOT/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
raw=json.loads(Path('/tmp/accounting-timing/results.json').read_text())['builds']['prepared-O0']['actions']
compiles=[r for r in raw if r['phase']=='object_compile' and not any('/specializations/layout' in a for a in r['arguments'])]
link=[r for r in raw if r['phase']=='link'][-1]['arguments'];objects=[x for x in link if x.endswith('.o')];assert len(objects)==len(compiles)
app=[(i,r) for i,r in enumerate(compiles) if r['arguments'][r['arguments'].index('-c')+1].startswith(str(PUB)) and '/specializations/' not in r['arguments'][r['arguments'].index('-c')+1]]
assert len(app)==16
(OUT/'index.hpp').write_text('#pragma once\n#include <scpp/string_t.hpp>\nnamespace scpp { inline void require_write_key(const string_t &) {} }\n')
(OUT/'output.hpp').write_text('#pragma once\n#include <scpp/string_t.hpp>\nnamespace probe { void echo_string(const scpp::string_t &); }\n')
(OUT/'output.cpp').write_text('#include <lang/php/support/php_string.hpp>\nnamespace probe { void echo_string(const scpp::string_t &value) { scpp::php::echo_one(value); } }\n')
variants=['baseline','index','output','both'];results={v:[] for v in variants}
for variant in variants:
 folder=OUT/variant;folder.mkdir(exist_ok=True)
 for i,row in app:
  src=Path(row['arguments'][row['arguments'].index('-c')+1]);text=src.read_text()
  if variant in ['index','both']:text=text.replace('#include "scpp/keyed_path.hpp"','#include "'+str(OUT/'index.hpp')+'"')
  if variant in ['output','both']:text=text.replace('#include "lang/php/support/php_string.hpp"','#include "'+str(OUT/'output.hpp')+'"').replace('::scpp::php::echo_one(', '::probe::echo_string(')
  (folder/f'unit{i}.cpp').write_text(text)
with CommandRunner(OUT/'logs',ROOT,jobs=12) as runner:
 def run(name,args):
  start=time.monotonic();runner.run(name,['/usr/bin/env','-i','PATH=/usr/bin:/bin','LC_ALL=C','TZ=UTC','SOURCE_DATE_EPOCH=0','/usr/bin/clang++',*args],timeout=180);return time.monotonic()-start
 def benchmark():
  args=app[0][1]['arguments'].copy();args[args.index('-c')+1]=str(OUT/'output.cpp');args[args.index('-o')+1]=str(OUT/'output.o');support=run('output-support',args)
  for trial in range(3):
   order=variants if trial==0 else list(reversed(variants)) if trial==1 else ['output','baseline','both','index']
   for variant in order:
    replacements={};units=[]
    for i,row in app:
     args=row['arguments'].copy();src=OUT/variant/f'unit{i}.cpp';obj=OUT/variant/f'unit{i}.o';args[args.index('-c')+1]=str(src);args[args.index('-o')+1]=str(obj)
     units.append({'source':row['arguments'][row['arguments'].index('-c')+1],'seconds':run(f'{variant}-{trial}-{i}',args)});replacements[objects[i]]=str(obj)
    args=[replacements.get(x,x) for x in link];binary=OUT/variant/'app';args[args.index('-o')+1]=str(binary)
    if variant in ['output','both']:args.append(str(OUT/'output.o'))
    seconds=run(f'{variant}-{trial}-link',args);stdout=runner.run(f'{variant}-{trial}-run',[binary]);assert b'CHECKS passed=998 failed=0' in stdout,stdout
    results[variant].append({'compile':sum(u['seconds'] for u in units),'link':seconds,'units':units});print(variant,trial,results[variant][-1]['compile'],flush=True)
    (OUT/'results.json').write_text(json.dumps({'support_compile':support,'variants':results},indent=2)+'\n')
 run_tasks([Task('O0-header-impact',benchmark)])
