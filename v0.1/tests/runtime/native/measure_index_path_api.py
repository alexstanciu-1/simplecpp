"""Single-run compilation cost for homogeneous nested store and append callers."""
from pathlib import Path
import json
import subprocess
import tempfile
import time
root = Path(__file__).resolve().parents[3]
sources = {'public': '', 'native': '#include "scpp/index_write_path.hpp"\n'}
for kind, native in [('mixed', 'scpp::mixed_t'), ('dynamic', 'scpp::detail::dynamic_value')]:
    for operation, append in [('store', 'false'), ('append', 'true')]:
        sources['public'] += f'#include "scpp/access/index_path_{operation}_{kind}.hpp"\n'
        sources['public'] += f'void {operation}(scpp::{kind} &r, const scpp::vector_mixed &k, const scpp::{kind} &v) {{ scpp::index_path_{operation}(r,k,v); }}\n'
        sources['native'] += f'void {operation}({native} &r, const scpp::vector_t<scpp::mixed_t> &k, const {native} &v) {{ scpp::index_path_write_adapted(r,k.size(),[&](std::size_t i)->const scpp::mixed_t& {{ return k.at(i); }},v,{append}); }}\n'
sources['public_existing'] = ''
sources['native_existing'] = '#include "scpp/index_existing.hpp"\n'
for kind, native in [('mixed', 'scpp::mixed_t'), ('dynamic', 'scpp::detail::dynamic_value')]:
    for operation in ['read', 'store']:
        sources['public_existing'] += f'#include "scpp/access/index_existing_{operation}_{kind}.hpp"\n'
    sources['public_existing'] += f'scpp::{kind} read(const scpp::{kind} &r, const scpp::vector_mixed &k) {{ return scpp::index_existing_read(r,k); }}\n'
    sources['public_existing'] += f'void store(scpp::{kind} &r, const scpp::vector_mixed &k, const scpp::{kind} &v) {{ scpp::index_existing_store(r,k,v); }}\n'
    adapter = '[&](std::size_t i)->const scpp::mixed_t& { return k.at(i); }'
    sources['native_existing'] += f'{native} read(const {native} &r, const scpp::vector_t<scpp::mixed_t> &k) {{ return scpp::index_existing_read_adapted(r,k.size(),{adapter}); }}\n'
    sources['native_existing'] += f'void store({native} &r, const scpp::vector_t<scpp::mixed_t> &k, const {native} &v) {{ scpp::index_existing_store_adapted(r,k.size(),{adapter},v); }}\n'
rows = []
with tempfile.TemporaryDirectory(prefix='scpp-index-path-cost-') as directory:
    stage = Path(directory)
    for name, source in sources.items():
        path = stage/(name+'.cpp'); path.write_text(source)
        start = time.perf_counter()
        subprocess.run(['clang++','-std=c++23','-O2','-I'+str(root/'runtime'/('public' if name.startswith('public') else 'include')),
                        '-c',str(path),'-o',str(stage/(name+'.o'))],check=True)
        rows.append({'case':name,'compile_seconds':time.perf_counter()-start})
print(json.dumps(rows,indent=2))
