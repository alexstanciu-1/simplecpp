"""Compile cost of public/native scalar debug calls; excludes runtime rebuilding."""
import json
from pathlib import Path
import subprocess
import tempfile
import time

root = Path(__file__).resolve().parents[3]
sources = {
    'public': '''#include "scpp/debug/flags.hpp"
#include "scpp/debug/dbg_value_int64.hpp"
#include "scpp/debug/dbg_labelled_string.hpp"
void integer(const scpp::int64 &value) { scpp::debug::dbg_at("sample.phs",scpp::int32(12),value); }
void text(const scpp::string &label,const scpp::string &value) { scpp::debug::dbg_at("sample.phs",scpp::int32(13),label,value,scpp::debug::DBG_DEFAULT); }
''',
    'native': '''#include "scpp/support/dbg.hpp"
void integer(const scpp::int_t<> &value) { scpp::php::dbg_at("sample.phs",12,value); }
void text(const scpp::string_t &label,const scpp::string_t &value) { scpp::php::dbg_at("sample.phs",13,label,value,scpp::DBG_DEFAULT); }
'''
}
rows = []
with tempfile.TemporaryDirectory(prefix='scpp-debug-cost-') as directory:
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
