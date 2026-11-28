"""Benchmark process_input owner slots using a staged baseline and scoped-block compiler."""
import argparse
import hashlib
import json
import os
import random
import re
import shutil
import subprocess as sp
import sys
import tempfile
import time
from pathlib import Path

FUNCTION = re.compile(r'^void process_input_\([^\n]+\)\n\{.*?^\}(?=\n\n)', re.M | re.S)
BLOCK = re.compile(r'\{ (owner_\w+ argument_\d+_0 = var_\w+; (?:owner_\w+ argument_\d+_1 = var_\w+; )?)(record_request_|count_response_|count_route_|render_traffic_)\(([^;]+)\); \}')
FORWARD = """template<auto Callee, class... Args>
static inline void forward_owner_args(Args&&... args) {
    Callee(static_cast<Args&&>(args)...);
}

"""


def change(text, mode):
    loop = FUNCTION.search(text)
    assert loop
    calls = BLOCK.findall(loop[0])
    assert len(calls) == 4 and sum(steps.count(';') for steps, _, _ in calls) == 7
    if mode == 'blocks':
        return text

    def rewrite(match):
        steps, target, args = match.groups()
        if mode == 'direct':
            for local, source in re.findall(r' (argument_\d+_\d+) = (var_\w+);', steps):
                args = re.sub(r'static_cast<owner_\w+&&>\('+local+r'\)', source, args)
            return '{ ' + target + '(' + args + '); }'
        if mode == 'hot-forward' and target not in ('count_response_', 'count_route_'):
            return match[0]
        assert mode in ('forward', 'hot-forward')
        return '{ ' + steps + 'forward_owner_args<' + target + '>(' + args + '); }'

    prefix = FORWARD if 'forward' in mode else ''
    return text[:loop.start()] + prefix + BLOCK.sub(rewrite,loop[0]) + text[loop.end():]



LOOP_SYMBOL = '_ZN3App14process_input_ERN4scpp6stringENS_15owner_DashboardEb'
MODES = ['old', 'blocks', 'direct', 'hot-forward', 'forward']
PADDING = [0, 32, 128, 224]


def run(*args, **kwargs):
    return sp.run(args, check=True, stdout=sp.PIPE, **kwargs).stdout


def loop_facts(directory, unit, entry, flags):
    ir_file = directory / 'app.ll'
    run(*flags, '-emit-llvm', '-S', unit, '-o', ir_file)
    definitions = re.finditer(r'^define [^\n]+\n.*?^\}', ir_file.read_text(), re.M | re.S)
    ir = next(m[0] for m in definitions if '@' + LOOP_SYMBOL + '(' in m[0].splitlines()[0])
    exe = directory / 'normal'
    assembly = run('objdump', '-d', '--no-show-raw-insn', '--disassemble=' + LOOP_SYMBOL, exe).decode()
    (directory / 'loop.ll').write_text(ir + '\n')
    (directory / 'loop.s').write_text(assembly)
    calls = re.findall(r'\b(?:call|invoke) void @_ZN3App\d+(?:count_route_|count_response_|record_request_|render_traffic_)[^\n]+', ir)
    slots = [slot for call in calls for slot in re.findall(r'ptr noundef nonnull (%\d+)\b', call)]
    assert len(slots) == 7 and 'forward_owner_args' not in ir

    def marked(slot):
        return all(re.search(r'@llvm\.lifetime\.' + op + r'\.p0\(i64 16, ptr nonnull ' + slot + r'\)', ir)
                   for op in ('start', 'end'))

    symbol = next(line for line in run('nm', '-S', exe).decode().splitlines() if line.endswith(' ' + LOOP_SYMBOL))
    return dict(owner_args=len(slots), marked=sum(marked(slot) for slot in slots),
                stack=int(re.search(r'sub\s+\$0x([0-9a-f]+),%rsp', assembly)[1], 16),
                native_bytes=int(symbol.split()[1], 16),
                instructions=len(re.findall(r'^\s*[0-9a-f]+:\s+\w+', assembly, re.M)),
                calls=len(re.findall(r'^\s*[0-9a-f]+:\s+call\s', assembly, re.M)), invokes=ir.count('invoke '),
                cpp_sha256=hashlib.sha256(unit.read_bytes()).hexdigest(),
                cpp_bytes=unit.stat().st_size + entry.stat().st_size)


DEFINITION = re.compile(r'^[^\s#][^\n]*? (\w+_)\([^\n]*\)\n\{', re.M)
OWNER_BLOCK = re.compile(r'^([ \t]*)\{ ((?:owner_\w+ argument_\d+_\d+ = var_\w+; )+)(\w+_)\(([^;]+)\); \}$', re.M)
OWNER = re.compile(r'(owner_\w+) (argument_\d+_\d+) = var_\w+; ')
LOCAL = re.compile(r'var_\w+')
EXPECTED = {'stock-ledger': (37, 1), 'route-table': (37, 2)}


def inspect_sites(text):
    definitions = list(DEFINITION.finditer(text))
    sites = []
    for match in OWNER_BLOCK.finditer(text):
        caller = next(m[1] for m in reversed(definitions) if m.start() < match.start())
        args = match[4].split(', ')
        moves = {f'static_cast<{kind}&&>({local})' for kind, local in OWNER.findall(match[2])}
        indices = [i for i, arg in enumerate(args) if arg in moves]
        assert len(indices) == len(moves) and all(arg in moves or LOCAL.fullmatch(arg) for arg in args)
        sites.append((caller, match, indices))
    return sites


def rewrite_owner_sites(text, mode, sample):
    sites = inspect_sites(text)
    assert (len(sites), sum(c == 'process_input_' for c, _, _ in sites)) == EXPECTED[sample]
    if mode == 'blocks':
        return text
    for caller, match, _ in reversed(sites):
        if mode == 'forward-all' or caller == 'process_input_':
            start, end = match.span(3)
            text = text[:start] + 'forward_owner_args<' + match[3] + '>' + text[end:]
    pos = DEFINITION.search(text).start()
    return text[:pos] + FORWARD + text[pos:]


def owner_site_facts(directory, unit, entry, flags, baseline):
    ir_path = directory / 'app.ll'
    run(*flags, '-S', '-emit-llvm', unit, '-o', ir_path)
    ir = ir_path.read_text()
    symbols = run('nm', '-S', directory / 'normal').decode().splitlines()
    functions = {}
    for caller, match, indices in inspect_sites(baseline):
        targets = functions.setdefault(caller, {})
        assert match[3] not in targets
        targets[match[3]] = indices
    facts = {}
    for caller, targets in functions.items():
        mangled = '_ZN3App' + str(len(caller)) + caller
        symbol = next(line for line in symbols if line.split()[-1].startswith(mangled) and line.split()[-2].lower() == 't')
        name = symbol.split()[-1]
        body = next(m[0] for m in re.finditer(r'^define [^\n]+\n.*?^\}', ir, re.M | re.S)
                    if '@' + name + '(' in m[0].splitlines()[0])
        assembly = run('objdump', '-d', '--no-show-raw-insn', '--disassemble=' + name, directory / 'normal').decode()
        (directory / (caller + '.s')).write_text(assembly)
        (directory / (caller + '.ll')).write_text(body)
        frame = re.search(r'sub\s+\$0x([0-9a-f]+),%rsp', assembly)
        sites = {}
        for callee, indices in targets.items():
            prefix = '_ZN3App' + str(len(callee)) + callee
            calls = re.findall(r'\b(?:call|invoke) [^@\n]*@' + prefix + r'\w*\(([^\n]*)\)', body)
            slots = []
            for call in calls:
                args = call.split(', ')
                for index in indices:
                    slot = re.search(r' (%\d+)\b', args[index])
                    if slot:
                        ptr = re.escape(slot[1])
                        markers = [re.search(r'@llvm\.lifetime\.' + op + r'\.p0\(i64 16, ptr (?:nonnull )?' + ptr + r'\)', body)
                                   for op in ('start', 'end')]
                        slots.append(dict(ptr=slot[1], marked=all(markers)))
            sites[callee] = dict(owner_positions=indices, visible_calls=len(calls), slots=slots)
        facts[caller] = dict(frame=int(frame[1], 16) if frame else 0, bytes=int(symbol.split()[1], 16),
                             helper_calls=len(re.findall(r'\b(?:call|invoke) [^@\n]*@[^ (]*forward_owner_args', body)), sites=sites)
    return dict(functions=facts, cpp_bytes=unit.stat().st_size + entry.stat().st_size,
                sha256=hashlib.sha256(unit.read_bytes()).hexdigest())


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('stage', type=Path, help='Directory containing v02 (previous compiler) and pilots')
    parser.add_argument('compiler', type=Path, help='Current compiler directory')
    parser.add_argument('output', type=Path)
    parser.add_argument('--sample', choices=('access-log', 'stock-ledger', 'route-table'), default='access-log')
    options = parser.parse_args()
    sample = options.sample
    extended = sample != 'access-log'
    modes = ['blocks', 'forward-loop', 'forward-all'] if extended else MODES
    base = options.stage.resolve()
    sys.path.insert(0, str(base / 'pilots/scaled'))
    from generate import BASE, HERE
    from verify_workload import fixture

    root = base / 'v02'
    flags = ['clang++', '-O2', '-g0', '-std=c++23', '-stdlib=libc++', '-I' + str(root / 'runtime/generated/public')]
    link = ['clang++', '-stdlib=libc++', '--ld-path=' + shutil.which('ld.mold')]
    work = Path(tempfile.mkdtemp(prefix='owner-slot-', dir=base))
    print(work, flush=True)
    rng = random.Random(17127)
    builds, traces, cpu = [], [], []
    facts, addresses = {}, {}
    for case in ('shared', 'full'):
        fixture(sample, 100, work / case, case == 'full')
    for pad in PADDING[1:]:
        asm = work / f'pad{pad}.s'
        asm.write_text(f'.text\n.fill {pad},1,0x90\n.section .note.GNU-stack,"",@progbits\n')
        run('clang', '-c', asm, '-o', work / f'pad{pad}.o')
    run(*flags, '-c', BASE / 'runtime.cpp', '-o', work / 'driver.o')

    for iteration in range(-1, 5):
        schedule = modes.copy()
        rng.shuffle(schedule)
        for mode in schedule:
            directory = work / mode / str(iteration)
            app, out = directory / 'app', directory / 'out'
            app.mkdir(parents=True)
            for file in ('main.phs', 'module.json'):
                shutil.copyfile(HERE / '100k' / sample / file, app / file)
            compiler = root / 'compiler' if mode == 'old' else options.compiler.resolve()
            start = time.perf_counter()
            info = json.loads(run('/usr/bin/php8.5', compiler / 'tests/incremental_executable.php', app, out, input=b'"run"\n'))
            manifest = json.loads((out / 'manifest.json').read_text())
            unit = out / next(iter(manifest['sources'].values()))
            if mode != 'old':
                text = unit.read_text()
                unit.write_text(rewrite_owner_sites(text, mode, sample) if extended else change(text, mode))
            ready = time.perf_counter()
            run(*flags, '-c', unit, '-o', directory / 'app.o')
            compiled = time.perf_counter()
            run(*flags, '-c', out / 'entry.cpp', '-o', directory / 'entry.o')
            linking = time.perf_counter()
            run(*link, directory / 'app.o', directory / 'entry.o', *manifest['link_inputs'], '-o', directory / 'normal')
            finished = time.perf_counter()
            for case in (BASE / sample, work / 'shared', work / 'full'):
                assert run(directory / 'normal', cwd=case) == (case / 'expected.txt').read_bytes()
            if iteration >= 0:
                builds.append(dict(mode=mode, round=iteration, php=info['php_ms'], prep=(ready-start)*1000-info['php_ms'],
                                   native=(compiled-ready)*1000, entry=(linking-compiled)*1000,
                                   link=(finished-linking)*1000, total=(finished-start)*1000))
            if iteration in (0, 2, 4):
                trace = directory / 'trace.json'
                run(*flags, '-ftime-trace=' + str(trace), '-c', unit, '-o', directory / 'traced.o')
                totals = {e['name']: e['dur']/1000 for e in json.loads(trace.read_text())['traceEvents']
                          if e['name'] in ('Total Frontend', 'Total Optimizer', 'Total CodeGenPasses')}
                traces.append(dict(mode=mode, round=iteration, **totals))
            if iteration == 0:
                if extended:
                    facts[mode] = owner_site_facts(directory, unit, out / 'entry.cpp', flags, text)
                else:
                    facts[mode] = loop_facts(directory, unit, out / 'entry.cpp', flags)
                addresses[mode] = {}
                for pad in PADDING:
                    exe = directory / f'exe{pad}'
                    extra = [work / f'pad{pad}.o'] if pad else []
                    run(*link, work / 'driver.o', *extra, directory / 'app.o', *manifest['link_inputs'], '-o', exe)
                    addresses[mode][pad] = next(line.split()[0] for line in run('nm', exe).decode().splitlines()
                                                if line.endswith(' ' + LOOP_SYMBOL))
        print('build', iteration, flush=True)

    for iteration in range(12):
        jobs = [(mode, pad, case) for mode in modes for pad in PADDING for case in ('shared', 'full')]
        rng.shuffle(jobs)
        for mode, pad, case in jobs:
            passes = 160 if case == 'shared' else 32
            exe = work / mode / '0' / f'exe{pad}'
            result = sp.run(['taskset', '-c', '8', exe, work / case / 'input.txt', str(passes)], capture_output=True, check=True)
            cpu.append(dict(mode=mode, pad=pad, case=case, round=iteration, ms=int(result.stderr.split()[0])/passes/1e6))
        if iteration % 4 == 3:
            print('cpu', iteration, flush=True)
    data = dict(sample=sample, host=os.uname().nodename, clang=run('clang++', '--version').decode().splitlines()[0], flags=flags,
                work=str(work), builds=builds, traces=traces, facts=facts, addresses=addresses, cpu=cpu)
    options.output.write_text(json.dumps(data, indent=1) + '\n')


if __name__ == '__main__':
    main()
