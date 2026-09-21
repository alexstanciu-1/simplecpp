#!/usr/bin/env python3
"""Thirty-two ready callable units: scheduler stress, not a representative edit."""
import argparse,json,re
from pathlib import Path
from experiment import measure,require_scratch
from run_corpus import warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);args=p.parse_args()
app=args.app.resolve();require_scratch(app);build=app/'.prism/build';config='latency-layout.ninja'
out=app.parent/'wide-scheduling-results';out.mkdir(exist_ok=True)
corpus=json.loads((app.parent/'corpus.json').read_text());warm(app,config)
edges=re.findall(r'^build (layout_parts/__callable/\S+\.o): compile_callable_part (\S+)',(build/config).read_text(),re.M)
# Smallest callable bodies expose more ready jobs than the machine has CPUs.
chosen=sorted(edges,key=lambda item:(build/item[1]).stat().st_size)[:32];assert len(chosen)==32
expected={obj for obj,_ in chosen}|{'main'}
(out/'manifest.json').write_text(json.dumps({'purpose':'Synthetic scheduling stress; unchanged bodies forced to recompile, not an edit-latency claim','objects':[a for a,b in chosen],'inputs':[b for a,b in chosen],'cache':'disabled'},indent=2))
for trial in range(2):
    jobs=[8,12,16,24,32]
    if trial:jobs.reverse()
    for n in jobs:
        for obj,source in chosen:(build/source).touch()
        label=f'wide-r{trial}-j{n}';measure(app,config,label,out,[],jobs=n)
        row=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1])
        assert {s['output'] for s in row['native_steps']}==expected
        verify(app,corpus,out,label)
