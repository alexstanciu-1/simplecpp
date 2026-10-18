import json, pathlib, random, statistics, subprocess, time
root = pathlib.Path('/tmp/scpp-int-literal-bench')
include = '/home/alexv/__AI/simple_cpp/simple_cpp_01/runtime/include'
base = ['clang++', '-std=c++20', '-I', include]
header = '#include "scpp/int_t.hpp"\nusing scpp::int_t;\n'
bad = root / 'invalid.cpp'
bad.write_text(header + 'void f() { int_t<> a = 10; }\n')
p = subprocess.run(base + ['-fsyntax-only', str(bad)], capture_output=True, text=True)
(root / 'invalid.txt').write_text(p.stderr)
assert p.returncode != 0
results = []
rng = random.Random(9025)
for count in [1, 10000]:
    files = {}
    for form in ['direct', 'auto_cast']:
        lines = [header]
        # Bounded functions keep this from being one pathological giant scope.
        for start in range(0, count, 100):
            lines.append(f'long long f{start}() {{')
            for i in range(start, min(count, start + 100)):
                lines.append(f'int_t<> a{i}{{10}};' if form == 'direct' else f'auto a{i} = static_cast<int_t<>>(10);')
            lines.append('return ' + ' + '.join(f'a{i}.native_value()' for i in range(start, min(count, start+100))) + ';\n}')
        path = root / f'{form}_{count}.cpp'
        path.write_text('\n'.join(lines))
        files[form] = path
    for mode, flags in [('syntax', ['-fsyntax-only']), ('object_O0', ['-O0', '-c']), ('object_O2', ['-O2', '-c'])]:
        samples = {'direct': [], 'auto_cast': []}
        pairs = []
        def run(form):
            args = base + flags + [str(files[form])]
            if mode != 'syntax':
                args += ['-o', str(root / 'output.o')]
            t = time.perf_counter()
            subprocess.run(args, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
            return (time.perf_counter() - t) * 1000
        for form in samples:
            run(form)
        for repeat in range(12):
            order = list(samples)
            rng.shuffle(order)
            pair = {}
            for form in order:
                pair[form] = run(form)
                samples[form].append(pair[form])
            pairs.append(pair['auto_cast'] - pair['direct'])
        row = {'count': count, 'mode': mode, 'samples_ms': samples,
               'median_ms': {k: statistics.median(v) for k,v in samples.items()},
               'paired_delta_median_ms': statistics.median(pairs),
               'paired_delta_range_ms': [min(pairs), max(pairs)],
               'auto_slower_pairs': sum(x > 0 for x in pairs)}
        results.append(row)
        (root / 'results.json').write_text(json.dumps(results, indent=2))
        print(json.dumps({k:v for k,v in row.items() if k != 'samples_ms'}), flush=True)
# Confirm both forms generate identical optimized machine instructions, excluding filenames.
for form in ['direct', 'auto_cast']:
    out = root / f'{form}.s'
    subprocess.run(base + ['-O2', '-S', str(root / f'{form}_10000.cpp'), '-o', str(out)], check=True)
a, b = [(root / f'{f}.s').read_text().splitlines() for f in ['direct','auto_cast']]
assert [l for l in a if not l.lstrip().startswith('.file')] == [l for l in b if not l.lstrip().startswith('.file')]
print('Optimized assembly identical (excluding source filename).', flush=True)
