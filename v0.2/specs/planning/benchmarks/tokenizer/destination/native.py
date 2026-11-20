"""Compile the trial bundles, check scanner output against PHP, and time presized operators."""
from pathlib import Path
import argparse
import json
import statistics
import subprocess
import time

HERE = Path(__file__).resolve().parent


def call(args):
    return subprocess.check_output(args)


def native(work, runtime, oracle):
    results = {}
    cc = 'clang++'
    flags = [cc, '-std=c++23', '-O2', '-g0', '-I', runtime / 'generated/public']
    rt = work / (cc + '.o')
    call([*flags, '-I', runtime / 'src/sequence', '-I', runtime / 'src/numeric',
          '-I', runtime / 'generated/private', '-c', runtime / 'generated/src/runtime.cpp', '-o', rt])
    for f in ('cpp', 'php', 'js'):
        programs = {}
        builds = {}
        for case in ('scanner', 'second'):
            for mode in ('ordinary', 'selected'):
                d = work / (f + '-' + case) / mode
                header, = [p for p in (d / 'units').glob('*.hpp') if 'Scan scan_(' in p.read_text()]
                src = header.with_suffix('.cpp')
                obj = d / (cc + '.o')
                compile_command = [*flags, '-c', src, '-o', obj]
                call(compile_command)
                (d/'adapter.hpp').write_text(f'#include "units/{header.name}"\n#include <string>\n'
                    'using Result=App::scpp_records::Scan;\n'
                    'auto make_input(const std::string& s){return scpp::string(s.data(),long(s.size()));}\n'
                    'auto run(const scpp::string& s,size_t hint){return App::scan_(s,hint);}\n')
                exe = d / (cc + '-run')
                driver = HERE.parent / 'driver.cpp'
                if case == 'second':
                    driver = d / 'span.cpp'
                    driver.write_text('#include "adapter.hpp"\n#include <cassert>\nint main(){'
                        'for(int hint: {0,20}){auto r=run(make_input("if ==="),hint);assert(r.tokens.size()==2);'
                        'auto a=r.tokens.at(0),b=r.tokens.at(1);assert(a.begin==0 && a.width==2 && b.begin==3 && b.width==3);}}')
                call([*flags, '-I', d, driver, obj, rt, '-o', exe])
                if case == 'second':
                    call([exe])
                    continue
                for i in range(11):
                    inp = oracle / f / f'input{i}'
                    expected = (oracle / f / f'expected{i}').read_bytes()
                    for hint in ([], [inp]):
                        assert call([exe, 'dump', inp, *hint]) == expected
                programs[mode] = exe
                builds[mode] = compile_command
                if mode == 'selected':
                    probe = d/'failure.cpp'
                    probe.write_text((HERE/'failure.cpp').read_text())
                    call([*flags, '-I', d, probe, obj, rt, '-o', d/(cc+'-failure')])
                    call([d/(cc+'-failure')])
        compile_pairs = []
        for batch in range(4):
            pair = {}
            for mode in (('ordinary','selected') if batch % 2 == 0 else ('selected','ordinary')):
                start = time.monotonic_ns()
                call(builds[mode])
                pair[mode] = (time.monotonic_ns() - start) / 1e6
            compile_pairs.append(pair)
        corpus = oracle / f / 'input9'
        pairs = []
        for batch in range(8):
            pair = {}
            for mode in (('ordinary','selected') if batch % 2 == 0 else ('selected','ordinary')):
                pair[mode] = statistics.median(map(float, call([programs[mode], 'bench', corpus, corpus]).split()))
            pairs.append(pair)
        results[f'{cc}/{f}'] = {'seconds_per_scan':pairs, 'compile_ms':compile_pairs}
    return results


if __name__ == '__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    for name in ('work','runtime','oracle'):
        parser.add_argument(name,type=Path)
    args=parser.parse_args()
    print(json.dumps(native(args.work.resolve(),args.runtime.resolve(),args.oracle.resolve()),indent=2))
