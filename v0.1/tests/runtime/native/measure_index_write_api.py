"""Compile-only cost for staged mixed-key writes through both boxed families."""
from pathlib import Path
import json
import subprocess
import tempfile
import time
root=Path(__file__).resolve().parents[3]
sources={
    'public': '#include "scpp/access/require_write_key_mixed.hpp"\n#include "scpp/dynamic.hpp"\nvoid check(const scpp::mixed &k) { scpp::require_write_key(k); }\nvoid store(scpp::mixed &r, const scpp::mixed &k, const scpp::mixed &v) { r.set(k,v); }\nvoid store(scpp::dynamic &r, const scpp::mixed &k, const scpp::dynamic &v) { r.set(k,v); }\n',
    'native': '#include "scpp/index_write_path.hpp"\nvoid check(const scpp::mixed_t &k) { scpp::require_write_key(k); }\nvoid store(scpp::mixed_t &r, const scpp::mixed_t &k, const scpp::mixed_t &v) { const auto key=k; scpp::index_path_check_store(r,key,v); if (&r==&v) { const auto snapshot=v; scpp::index_store(r,key,snapshot); } else scpp::index_store(r,key,v); }\nvoid store(scpp::detail::dynamic_value &r, const scpp::mixed_t &k, const scpp::detail::dynamic_value &v) { const auto key=k; scpp::index_path_check_store(r,key,v); if (&r==&v) { const auto snapshot=v; scpp::index_store(r,key,snapshot); } else scpp::index_store(r,key,v); }\n'
}
rows=[]
with tempfile.TemporaryDirectory(prefix='scpp-index-write-cost-') as d:
    stage=Path(d)
    for name,source in sources.items():
        p=stage/(name+'.cpp');p.write_text(source)
        start=time.perf_counter()
        subprocess.run(['clang++','-std=c++23','-O2','-I'+str(root/'runtime'/('public' if name=='public' else 'include')),'-c',str(p),'-o',str(stage/(name+'.o'))],check=True)
        rows.append({'case':name,'compile_seconds':time.perf_counter()-start})
print(json.dumps(rows,indent=2))
