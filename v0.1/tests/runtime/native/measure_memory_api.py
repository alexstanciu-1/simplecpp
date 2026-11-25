"""Compile cost of public/native process-memory observations; excludes runtime rebuilding."""
import json
from pathlib import Path
import subprocess
import tempfile
import time

root = Path(__file__).resolve().parents[3]
sources = {
    'public': '''#include "scpp/memory/memory_get_usage.hpp"
#include "scpp/memory/memory_get_peak_usage_real.hpp"
scpp::int64 current() { return scpp::process_memory_usage(); }
scpp::int64 peak() { return scpp::process_peak_memory_usage(scpp::boolean(true)); }
''',
    'native': '''#include "operators/memory_usage/memory_usage.hpp"
scpp::int_t<> current() { return scpp::memory_get_usage(); }
scpp::int_t<> peak() { return scpp::memory_get_peak_usage(scpp::bool_t(true)); }
'''
}
rows = []
with tempfile.TemporaryDirectory(prefix='scpp-memory-cost-') as directory:
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
