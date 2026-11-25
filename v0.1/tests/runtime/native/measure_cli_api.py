"""Compile cost of public/native CLI queries; excludes runtime rebuilding."""
import json
from pathlib import Path
import subprocess
import tempfile
import time

root = Path(__file__).resolve().parents[3]
sources = {
    'public': '''#include "scpp/cli/cli_argc.hpp"
#include "scpp/cli/cli_argv.hpp"
#include "scpp/cli/cli_args.hpp"
scpp::int64 size() { return scpp::cli_argc(); }
scpp::mixed snapshot() { return scpp::cli_argv(); }
scpp::mixed alias() { return scpp::cli_args(); }
''',
    'native': '''#include "scpp/detail/cli_arguments.hpp"
scpp::int_t<> size() { return scpp::php::cli_argc(); }
scpp::mixed_t snapshot() { return scpp::php::cli_argv(); }
scpp::mixed_t alias() { return scpp::php::cli_args(); }
'''
}
rows = []
with tempfile.TemporaryDirectory(prefix='scpp-cli-cost-') as directory:
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
