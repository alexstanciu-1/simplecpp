"""Single-run public/native boxed ordering caller compilation cost."""
from pathlib import Path
import json
import subprocess
import tempfile
import time
root = Path(__file__).resolve().parents[3]
sources = {
    'public': '#include "scpp/operators/less_mixed.hpp"\n#include "scpp/operators/less_dynamic.hpp"\nscpp::boolean same(const scpp::mixed &a,const scpp::mixed &b) { return scpp::numeric_less(a,b); }\nscpp::boolean same(const scpp::dynamic &a,const scpp::dynamic &b) { return scpp::numeric_less(a,b); }\n',
    'native': '#include "operators/ordering/relational.hpp"\nscpp::bool_t same(const scpp::mixed_t &a,const scpp::mixed_t &b) { return scpp::detail::relational_less(a,b); }\nscpp::bool_t same(const scpp::detail::dynamic_value &a,const scpp::detail::dynamic_value &b) { return scpp::detail::relational_less(a,b); }\n'
}
rows = []
with tempfile.TemporaryDirectory(prefix='scpp-ordering-cost-') as directory:
    stage = Path(directory)
    for name, source in sources.items():
        path = stage/(name+'.cpp'); path.write_text(source)
        start = time.perf_counter()
        subprocess.run(['clang++','-std=c++23','-O2','-I'+str(root/'runtime'/('public' if name=='public' else 'include')),
                        '-c',str(path),'-o',str(stage/(name+'.o'))],check=True)
        rows.append({'case':name,'compile_seconds':time.perf_counter()-start})
print(json.dumps(rows,indent=2))
