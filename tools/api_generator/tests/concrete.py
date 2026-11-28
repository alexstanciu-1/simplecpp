"""General concrete instantiation, non-default/throwing elements and real runtime allocation failure cleanup."""
import json
from pathlib import Path
import subprocess
import tempfile
import argparse

ROOT = Path(__file__).resolve().parents[3]

def run(command, env=None):
    p = subprocess.run(list(map(str, command)), capture_output=True, text=True, env=env)
    assert p.returncode == 0, (command, p.stdout, p.stderr)
    return p.stdout

def prove(work, sanitize, quick=False):
    source = work / 'probe.cpp'
    run(['php', ROOT/'tools/api_generator/tests/concrete.php', source])
    generated = ROOT / 'runtime/generated'
    if not quick:
        # Compile the same generated allocation probe with each compiler's own standard library.
        request = json.loads((ROOT / 'tests/generator_parity/numeric_publication.json').read_text())
        request['string']['functions'] = ['explode', 'implode']
        profile = work / 'vector-publication.json'
        profile.write_text(json.dumps(request))
        generated = work / 'generated'
        layout = ROOT / 'build/clang/native-layout'
        run(['php', ROOT / 'tools/api_generator/generate.php', 'emit-runtime', ROOT / 'runtime/metadata/runtime.json',
             profile, layout / 'facts.json', layout / 'context.json', generated])
    for compiler in (['clang++'] if quick else ['clang++', 'g++']):
        flags = ['-std=c++23', '-O0' if quick else '-O1', '-g0', '-Wall', '-Wextra', '-Werror', '-I'+str(generated/'public'), '-I'+str(ROOT/'runtime/src/sequence'), '-I'+str(ROOT/'runtime/src/hash')]
        if sanitize and compiler == 'clang++': flags += ['-fsanitize=address,undefined', '-fno-omit-frame-pointer']
        run([compiler, *flags, source, '-o', work/'probe'])
        run([work/'probe'])
        invalid = work / 'throwing-move.cpp'
        invalid.write_text(source.read_text().replace('Probe(Probe&& other) noexcept', 'Probe(Probe&& other) noexcept(false)'))
        rejected = subprocess.run([compiler, *flags, '-fsyntax-only', str(invalid)], capture_output=True, text=True)
        assert rejected.returncode != 0 and 'nothrow_move_constructible' in rejected.stderr, rejected.stderr
        runtime_inputs = (['-stdlib=libc++', ROOT/'build/clang/libscpp_runtime.a'] if quick else
                          ['-I'+str(ROOT/'runtime/src/numeric'), generated/'src/runtime.cpp'])
        run([compiler, *flags, ROOT/'tests/vector_failure.cpp', *runtime_inputs, '-o', work/'failure'])
        run([work/'failure'])
        print(compiler + ': generic recipe, vector/hash aligned non-default elements, holes, copy failure prefixes and runtime allocation failures passed', flush=True)

if __name__ == '__main__':
    p = argparse.ArgumentParser(description=__doc__); p.add_argument('--sanitize', action='store_true'); p.add_argument("--quick", action="store_true"); args = p.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-concrete-') as directory: prove(Path(directory), args.sanitize, args.quick)
