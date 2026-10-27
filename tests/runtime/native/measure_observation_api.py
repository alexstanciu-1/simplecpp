"""Single compile-only observation cost, without rebuilding the runtime."""
import json
from pathlib import Path
import subprocess
import tempfile
import time
root=Path(__file__).resolve().parents[3]
sources={
'public':'''#include "scpp/observations/isset_dynamic.hpp"
#include "scpp/observations/empty_mixed.hpp"
#include "scpp/observations/count_dynamic.hpp"
scpp::int64 cardinality(const scpp::dynamic &v) { return scpp::count(v); }
scpp::boolean present(const scpp::dynamic &v) { return scpp::isset(v); }
scpp::boolean vacant(const scpp::mixed &v) { return scpp::empty(v); }
''',
'native':'''#include "operators/isset/isset.hpp"
#include "operators/empty/empty.hpp"
#include "operators/count/count.hpp"
scpp::int_t<> cardinality(const scpp::detail::dynamic_value &v) { return scpp::count(v); }
scpp::bool_t present(const scpp::detail::dynamic_value &v) { return scpp::isset_one(v); }
scpp::bool_t vacant(const scpp::mixed_t &v) { return scpp::empty(v); }
'''}
rows=[]
with tempfile.TemporaryDirectory(prefix='scpp-observe-cost-') as d:
    stage=Path(d)
    for name,source in sources.items():
        p=stage/(name+'.cpp'); p.write_text(source)
        start=time.perf_counter()
        subprocess.run(['clang++','-std=c++23','-O2','-I'+str(root/'runtime'/('public' if name=='public' else 'include')),
                        '-c',str(p),'-o',str(stage/(name+'.o'))],check=True)
        rows.append({'case':name,'compile_seconds':time.perf_counter()-start})
print(json.dumps(rows,indent=2))
