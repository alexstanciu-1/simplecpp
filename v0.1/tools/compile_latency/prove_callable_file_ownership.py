#!/usr/bin/env python3
"""Executable proof: two extracted owners plus a preserved carrier in one file.

Synthetic ownership regression fixture, not a compile-latency measurement.
Run separately from native timing trials.
"""
import json
import re
import subprocess
import tempfile
from pathlib import Path
from callable_surface import prepare


def proof():
    with tempfile.TemporaryDirectory(prefix='scpp-callable-ownership-') as temporary:
        root = Path(temporary)
        app = root / 'app'
        generated = app / '.prism/generated'
        build = app / '.prism/build'
        generated.mkdir(parents=True)
        build.mkdir(parents=True)
        (root / 'manifest.json').write_text(json.dumps({'source': str(root / 'unmodified-input')}))
        (app / 'pair.phs').write_text('// Generated-C++ ownership fixture; no source-name collisions.\n')
        identity = '\tstatic const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }\n\tstatic bool_t __scpp_static_accepts(const void* __scpp_token);\n'
        header = '#pragma once\nnamespace scpp {\nusing bool_t = bool;\n'
        for cls in ['Alpha', 'Payload', 'Beta']:
            member = '\tint value = 5;\n' if cls == 'Payload' else '\tstatic int value();\n'
            header += f'class {cls} {{\npublic:\n' + identity + member + '};\n'
        header += '}\n'
        (generated / 'pair.hpp').write_text(header)
        cpp = '#include "pair.hpp"\nnamespace scpp {\n\tusing namespace ::scpp;\n'
        for cls in ['Alpha', 'Payload', 'Beta']:
            cpp += f'bool_t {cls}::__scpp_static_accepts(const void* token) {{\n return token == __scpp_static_token();\n}}\n'
        cpp += 'int Alpha::value() {\n return Beta::value() + 1;\n}\nint Beta::value() {\n return 6;\n}\n}\n'
        (generated / 'pair.cpp').write_text(cpp)
        (generated / 'main.cpp').write_text('''#include "pair.hpp"
int main() {
    scpp::Payload payload;
    return scpp::Alpha::value() == 7 && scpp::Beta::value() == 6 && payload.value == 5
        && scpp::Payload::__scpp_static_accepts(scpp::Payload::__scpp_static_token())
        && !scpp::Payload::__scpp_static_accepts(scpp::Alpha::__scpp_static_token()) ? 0 : 1;
}
''')
        (build / 'app_pch.hpp').write_text('// Fixture uses built-in C++ types.\n')
        (build / 'callables_runtime_pch.hpp.gch').touch()
        (build / 'runtime_signature.txt').touch()
        (build / 'latency-baseline.ninja').write_text('''rule compile
  command = clang++ -std=c++23 -I../generated $more_cxxflags -c $in -o $out
rule link
  command = clang++ $in -o $out
build pair.o: compile ../generated/pair.cpp
  more_cxxflags = -O0
build main.o: compile ../generated/main.cpp
  more_cxxflags = -O0
build main: link pair.o main.o
default main
''')
        prepare(app, targets={'Alpha': 'pair', 'Beta': 'pair'})
        graph = (build / 'latency-callables.ninja').read_text()
        for obj in re.findall(r'^build (\S+): compile ', graph, re.M):
            (build / obj).parent.mkdir(parents=True, exist_ok=True)
        subprocess.run(['ninja', '-f', 'latency-callables.ninja', '-j1'], cwd=build, check=True)
        subprocess.run([str(build / 'main')], check=True)
        print('PASS: two helper owners, cross-owner call, preserved carrier data and class identity, no depth guards')


if __name__ == '__main__':
    proof()
