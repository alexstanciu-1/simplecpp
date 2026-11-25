"""Compile-only cost for checked mixed-key reads through both boxed families."""
from pathlib import Path
import json
import subprocess
import tempfile
import time
root=Path(__file__).resolve().parents[3]
sources={
    'public': '#include "scpp/mixed.hpp"\n#include "scpp/dynamic.hpp"\nscpp::mixed read(const scpp::mixed &r, const scpp::mixed &k) { return r.get(k); }\nscpp::dynamic read(const scpp::dynamic &r, const scpp::mixed &k) { return r.get(k); }\n',
    'native': '#include "scpp/index_read.hpp"\nscpp::mixed_t read(const scpp::mixed_t &r, const scpp::mixed_t &k) { return scpp::null_index_read(r,k); }\nscpp::detail::dynamic_value read(const scpp::detail::dynamic_value &r, const scpp::mixed_t &k) { return scpp::null_index_read(r,k); }\n'
}
rows=[]
with tempfile.TemporaryDirectory(prefix='scpp-index-read-cost-') as d:
    stage=Path(d)
    for name,source in sources.items():
        p=stage/(name+'.cpp');p.write_text(source)
        start=time.perf_counter()
        subprocess.run(['clang++','-std=c++23','-O2','-I'+str(root/'runtime'/('public' if name=='public' else 'include')),'-c',str(p),'-o',str(stage/(name+'.o'))],check=True)
        rows.append({'case':name,'compile_seconds':time.perf_counter()-start})
print(json.dumps(rows,indent=2))
