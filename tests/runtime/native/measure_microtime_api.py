"""Single compile-only public/native wall-clock caller cost; no runtime rebuild."""
import json
from pathlib import Path
import subprocess
import tempfile
import time

root = Path(__file__).resolve().parents[3]
sources = {
    'public': '''#include "scpp/time/microtime_string.hpp"
#include "scpp/time/microtime_float.hpp"
scpp::string text() { return scpp::microtime(); }
scpp::float64 number() { return scpp::microtime(scpp::boolean(true)); }
''',
    'native': '''#include "scpp/detail/microtime.hpp"
scpp::string_t text() { return scpp::php::microtime(); }
scpp::float_t number() { return scpp::php::microtime(scpp::bool_t(true)); }
'''
}
rows = []
with tempfile.TemporaryDirectory(prefix='scpp-microtime-cost-') as directory:
    stage = Path(directory)
    for name, source in sources.items():
        path = stage/(name+'.cpp')
        path.write_text(source)
        start = time.perf_counter()
        subprocess.run(['clang++', '-std=c++23', '-O2',
                        '-I'+str(root/'runtime'/('public' if name == 'public' else 'include')),
                        '-c', str(path), '-o', str(stage/(name+'.o'))], check=True)
        rows.append({'case':name, 'compile_seconds':time.perf_counter()-start})
print(json.dumps(rows, indent=2))
