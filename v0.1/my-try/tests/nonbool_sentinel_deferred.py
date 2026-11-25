"""Observe deferred sentinel/plain equality instantiation without compiler admission."""
import argparse
import json
from pathlib import Path
import sys
APP=Path(__file__).resolve().parents[1]
ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner, Task, run_tasks
out=argparse.ArgumentParser(description=__doc__)
out.add_argument('--results',required=True,type=Path)
out=out.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False)
# The child records either compiler outcome; this is discovery, not an expected-pass proof.
observe='import subprocess,json,sys; p=subprocess.run(sys.argv[2:],capture_output=True,text=True); open(sys.argv[1],"w").write(json.dumps({"code":p.returncode,"stdout":p.stdout,"stderr":p.stderr},indent=2))'
records=[]
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
    def probe(carrier,payload,op,reverse):
        name=carrier+'-'+payload+'-'+('eq' if op=='==' else 'ne')+('-reverse' if reverse else '')
        v={'int':'scpp::int_t<>','float':'scpp::float_t','string':'scpp::string_t'}[payload]
        body='b '+op+' a' if reverse else 'a '+op+' b'
        source=out/(name+'.cpp')
        source.write_text('#include "operators/identity/strict_identity.hpp"\nusing V='+v+'; using W=scpp::'+carrier+'<V>;\nscpp::bool_t probe(const W& a,const V& b) { return '+body+'; }\n')
        result=out/(name+'.json')
        runner.run(name,[sys.executable,'-c',observe,result,'clang++','-std=c++20','-DSCPP_LANGUAGE_TARGET_PHP=1','-I',ROOT/'runtime/include','-fsyntax-only',source])
        data=json.loads(result.read_text())
        records.append({'carrier':carrier,'payload':payload,'operator':op,'reverse':reverse,'code':data['code'],'diagnostic':result.name})
    control=out/'control.cpp'
    control.write_text('#include "operators/identity/strict_identity.hpp"\nusing V=scpp::int_t<>; using W=scpp::result_or_false<V>; scpp::bool_t eq(const W& a,const W& b) { return a==b; } scpp::bool_t id(const W& a,const V& b) { return scpp::identical(a,b); }\n')
    runner.run('positive-control',['clang++','-std=c++20','-DSCPP_LANGUAGE_TARGET_PHP=1','-I',ROOT/'runtime/include','-fsyntax-only',control])
    run_tasks([Task(f'{c}-{p}-{op}-{rev}',lambda c=c,p=p,op=op,rev=rev:probe(c,p,op,rev)) for c in ['result_or_false','result_or_bool'] for p in ['int','float','string'] for op in ['==','!='] for rev in [False,True]])
assert len(records)==24
(out/'summary.json').write_text(json.dumps({'probes':sorted(records,key=lambda r:(r['carrier'],r['payload'],r['operator'],r['reverse'])),'admission_changed':False},indent=2)+'\n')
