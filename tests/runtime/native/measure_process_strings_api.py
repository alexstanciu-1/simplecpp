"""Compile cost of public/native environment and shell results; excludes runtime rebuilding."""
import json
from pathlib import Path
import subprocess
import tempfile
import time

root = Path(__file__).resolve().parents[3]
sources = {
    'public': '''#include "scpp/process/getenv.hpp"
#include "scpp/process/shell_exec.hpp"
scpp::result_or_false_string environment(const scpp::string &name) { return scpp::getenv(name); }
scpp::result_or_false_string capture(const scpp::string &command) { return scpp::shell_exec(command); }
''',
    'native': '''#include "lang/php/php_process.hpp"
scpp::result_or_false<scpp::string_t> environment(const scpp::string_t &name) { return scpp::php::getenv(name); }
scpp::result_or_false<scpp::string_t> capture(const scpp::string_t &command) { return scpp::php::shell_exec(command); }
'''
}
rows = []
with tempfile.TemporaryDirectory(prefix='scpp-process-strings-cost-') as directory:
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
