#!/usr/bin/env python3
"""Optional handle representation preserves semantic keys and native ownership."""
import argparse
import json
from pathlib import Path
import sys
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            folder = out / 'generated'
            folder.mkdir()
            runner.run('bindings', ['php', APP / 'tests/nullable_handle_representation.php', folder])
            base = ['clang++', '-std=c++23', '-g0', '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder)]
            includes = []
            for index, source in enumerate(sorted((folder / 'specializations').glob('layout*.cpp'))):
                binary = out / ('layout-' + str(index))
                runner.run('layout-build-' + str(index), [*base, *includes, source, '-o', binary])
                header = out / ('layout-' + str(index) + '.hpp')
                header.write_bytes(runner.run('layout-run-' + str(index), [binary]))
                includes += ['-include', header]
            runner.run('application-tokens', [*base, *includes, '-fsyntax-only', '-Xclang', '-dump-tokens', folder / 'proof.cpp'])
            tokens = (out / 'logs/application-tokens.stderr').read_text()
            assert "template 'template'" not in tokens
            assert "eof ''" in tokens
            for label, flags in [('O0', ['-O0']), ('O2', ['-O2']),
                                 ('sanitized', ['-O1', '-fsanitize=address,undefined', '-fno-omit-frame-pointer'])]:
                binary = out / label
                runner.run(label + '-build', [*base, *includes, *flags,
                    folder / 'specializations/containers.cpp', folder / 'provider.cpp', folder / 'proof.cpp', '-o', binary])
                runner.run(label + '-run', ['env', 'ASAN_OPTIONS=detect_leaks=0', binary])
        run_tasks([Task('nullable-handle-representation', proof)])
    (out / 'summary.json').write_text(json.dumps({'passed': True, 'builds': ['O0', 'O2', 'ASan+UBSan'], 'application_template_free': True, 'source_nullability_enabled': False}, indent=2) + '\n')


if __name__ == '__main__':
    main()
