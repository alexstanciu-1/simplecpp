"""Compile-only cost for keyed probes through both boxed families."""
from pathlib import Path
import json
import subprocess
import tempfile
import time
root=Path(__file__).resolve().parents[3]
sources={
    'public': '#include "scpp/access/keyed_probe_mixed.hpp"\n#include "scpp/access/keyed_probe_dynamic.hpp"\nscpp::boolean probe(const scpp::mixed &r, const scpp::mixed &k) { return scpp::keyed_probe(r,k,scpp::boolean(false)); }\nscpp::boolean probe(const scpp::dynamic &r, const scpp::mixed &k) { return scpp::keyed_probe(r,k,scpp::boolean(false)); }\n',
    'native': '#include "scpp/keyed_path.hpp"\nscpp::bool_t probe(const scpp::mixed_t &r, const scpp::mixed_t &k) { return scpp::keyed_probe(&r,k,false); }\nscpp::bool_t probe(const scpp::detail::dynamic_value &r, const scpp::mixed_t &k) { return scpp::keyed_probe(&r,k,false); }\n'
}
rows=[]
with tempfile.TemporaryDirectory(prefix='scpp-keyed-probe-cost-') as d:
    stage=Path(d)
    for name,source in sources.items():
        p=stage/(name+'.cpp');p.write_text(source)
        start=time.perf_counter()
        subprocess.run(['clang++','-std=c++23','-O2','-I'+str(root/'runtime'/('public' if name=='public' else 'include')),'-c',str(p),'-o',str(stage/(name+'.o'))],check=True)
        rows.append({'case':name,'compile_seconds':time.perf_counter()-start})
print(json.dumps(rows,indent=2))
