"""prepare DIR locally; bench DIR pinned on dev002. Results on stdout."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import time

HERE = Path(__file__).resolve().parent


def prepare(dest, single_lookup=False):
    from keyed_probe import ROOT, install_helper, publish
    dest.mkdir()
    for role, source_key in [('int', '1'), ('string', '"k"')]:
        for flavor in ('cpp', 'php', 'js'):
            folder = dest / (flavor + '-' + role)
            folder.mkdir()
            out, error = publish(folder, flavor, 'hash<Token,' + role + '>', source_key)
            assert out, error
            units = out / 'units'
            record, = [p for p in units.glob('*.hpp') if 'struct Token {' in p.read_text()]
            table, = [p for p in units.glob('*.hpp') if 'void insert_new(key_type&&' in p.read_text()]
            plain = table.read_bytes()
            table, name, key = install_helper(out, counters=False, single_lookup=single_lookup)
            package = {'ordinary.hpp': plain, 'direct.hpp': table.read_bytes(), 'record.hpp': record.read_bytes()}
            for mode in ('ordinary', 'direct'):
                package[mode + '-binding'] = (f'#include "record.hpp"\n#include "{mode}.hpp"\n'
                                              f'using Table = App::{name};\nusing Key = {key};\n').encode()
            path = dest / role
            path.mkdir(exist_ok=True)
            for file, data in package.items():
                target = path / file
                if target.exists():
                    assert target.read_bytes() == data, (flavor, file)
                else:
                    target.write_bytes(data)
            shutil.rmtree(folder)
    shutil.copy(HERE / 'keyed_workload.cpp', dest)
    shutil.copy(HERE / 'keyed_bench.py', dest)
    runtime = ROOT / 'runtime'
    includes = ['generated/public', 'src/hash', 'src/sequence', 'src/numeric']
    sources = ['generated/src/runtime.cpp', 'src/hash/concrete_index_impl.hpp']
    args = ['clang++', '-std=c++23', '-MM', '-x', 'c++', *['-I' + inc for inc in includes]]
    for source in sources:
        rule = subprocess.check_output([*args, source], cwd=runtime, text=True)
        for name in rule.replace(chr(92) + '\n', ' ').split(':', 1)[1].split():
            relative = Path(name)
            target = dest / ('runtime.cpp' if name == sources[0] else relative)
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy(runtime / relative, target)



def bench(dest):
    os.chdir(dest)
    assert len(os.sched_getaffinity(0)) == 1
    flags = ['clang++', '-std=c++23', '-O2', '-g0', '-Igenerated/public', '-Isrc/hash', '-Isrc/sequence', '-Isrc/numeric']
    def execute(args):
        return subprocess.check_output(args, text=True)
    def ms(args):
        start = time.monotonic_ns()
        execute(args)
        return (time.monotonic_ns() - start) / 1e6
    execute(flags + ['-c', 'runtime.cpp', '-o', 'runtime.o'])
    execute(flags + ['-x', 'c++', '-c', 'src/hash/concrete_index_impl.hpp', '-o', 'index.o'])
    data = {'clang': execute(['clang++', '--version']).splitlines()[0], 'cpu': list(os.sched_getaffinity(0)),
            'flags': flags, 'pairs_ns_per_write': {}, 'build_ms': {}, 'header_sha256': {}}
    for role in ('int', 'string'):
        for mode in ('ordinary', 'direct'):
            folder = Path(role, mode)
            folder.mkdir(exist_ok=True)
            shutil.copy(Path(role, mode + '-binding'), folder / 'binding.hpp')
            data['header_sha256'][role + '/' + mode] = hashlib.sha256(Path(role, mode + '.hpp').read_bytes()).hexdigest()
        for i in range(6):
            for mode in (('ordinary', 'direct') if i%2==0 else ('direct', 'ordinary')):
                folder = Path(role, mode)
                obj, exe = str(folder/'work.o'), str(folder/'run')
                cpp = flags + ['-I'+str(folder), '-I'+role]
                if mode=='direct': cpp.append('-DDIRECT')
                if role=='string': cpp.append('-DSTRING_KEY')
                compile_ms = ms(cpp+['-c', 'keyed_workload.cpp', '-o', obj])
                link_ms = ms(['clang++', obj, 'runtime.o', 'index.o', '-o', exe])
                if i: data['build_ms'].setdefault(role+'/'+mode, []).append([compile_ms, link_ms])
        for mode in ('ordinary', 'direct'):
            folder = Path(role, mode)
            trace = folder / 'trace.json'
            args = flags + ['-I'+str(folder), '-I'+role, '-ftime-trace='+str(trace)]
            if mode == 'direct': args.append('-DDIRECT')
            if role == 'string': args.append('-DSTRING_KEY')
            execute(args + ['-c', 'keyed_workload.cpp', '-o', str(folder/'traced.o')])
            totals = {event['name']:event['dur']/1000 for event in json.loads(trace.read_text())['traceEvents']
                      if event['name'] in ('Total Frontend', 'Total Backend')}
            data.setdefault('trace_ms', {})[role+'/'+mode] = totals
        for op in ('insert', 'replace'):
            pairs = []
            for i in range(17):
                row = {}
                for mode in (('ordinary','direct') if i%2==0 else ('direct','ordinary')):
                    row[mode] = float(execute([str(Path(role, mode, 'run')), op]))
                if i: pairs.append(row)
            data['pairs_ns_per_write'][role+'/'+op] = pairs
    return data


if __name__ == '__main__':
    action, path = sys.argv[1:]
    if action in ('prepare', 'prepare-single'):
        prepare(Path(path).resolve(), single_lookup=action == 'prepare-single')
    elif action == 'bench':
        print(json.dumps(bench(path), indent=2))
