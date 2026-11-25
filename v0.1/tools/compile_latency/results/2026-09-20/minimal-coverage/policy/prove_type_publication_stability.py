#!/usr/bin/env python3
"""Regression proof: stale forwards must not destabilize absent-type output."""
import argparse,json,tempfile
from pathlib import Path
from callable_surface import CLASS
import private_composition_type as module
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);a=p.parse_args()
source=a.app.resolve()/'.prism/expanded/generated'
cls=next(m[0] for m in CLASS.finditer((source/module.TYPE_HEADER).read_text()) if m[1]==module.TYPE)
root=Path(tempfile.mkdtemp(prefix='scpp-type-absence-'));(root/'manifest.json').write_text(json.dumps({'source':'/never-modify-source'}));app=root/'app'
results=[]
for present in (False,True):
 gen=app/'.prism'/str(present)/'generated';owner=gen/module.OWNER;owner.parent.mkdir(parents=True)
 owner.write_text('#pragma once\nnamespace scpp {\n'+(cls+'\n\n' if present else '')+'class Neighbor {};\n}\n')
 (gen/'unrelated.hpp').write_text('#pragma once\nnamespace scpp {\nclass '+module.TYPE+';\nclass Unrelated {};\n}\n')
 module.prepare(app,gen,'')
 results.append({str(p.relative_to(gen)):p.read_text() for p in gen.rglob('*.hpp') if str(p.relative_to(gen))!=module.TYPE_HEADER})
assert results[0]==results[1],results
print('type_present_or_absent_shared_headers=identical')
