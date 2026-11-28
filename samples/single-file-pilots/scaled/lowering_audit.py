"""Single-unit lowering counterfactuals for the access-log 100 KB compiler fixture."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import platform
import random
import re
import shutil
import tempfile
import time

from ninja_split import BASE, SOURCE, run
from verify_workload import dashboard, fixture

MODES = ["baseline", "blocks", "tail", "literal", "combined"]
REPORT = re.compile(r"^void \w+_by_\w+\([^\n]+\)\n\{.*?^\}\n", re.M | re.S)
LEAF = re.compile(
    r'\(\[&\]\(\) -> void \{ owner_Dashboard (argument_\d+)_0 = var_book_0; '
    r'scpp::string \1_1 = scpp::string\{("[^"\\]+"), (\d+)\}; '
    r'scpp::int64 \1_2 = \(var_event_1\)->(\w+); return observe_\('
    r'static_cast<owner_Dashboard&&>\(\1_0\), static_cast<scpp::string&&>\(\1_1\), \1_2\); \}\(\)\);')
CMP = re.compile(r'\(\[&\]\(\) -> scpp::bool_t \{ const auto binary_left = \(var_event_1\)->(\w+); '
                 r'const auto binary_right = scpp::string\{("[A-Za-z]+"), (\d+)\}; '
                 r'return \(\(binary_left == binary_right\)\); \}\(\)\)')


def change(text, mode):
    helpers = set()
    counts = dict(reports=0, leaves=0, comparisons=0)
    def visit(match):
        body = match[0]
        leaves = list(LEAF.finditer(body))
        fields = {m[4] for m in leaves}
        assert body.count('return observe_(') == len(leaves) and len(fields) == 1
        counts['reports'] += 1
        counts['leaves'] += len(leaves)
        counts['comparisons'] += len(CMP.findall(body))
        if mode == 'borrowed':
            body = CMP.sub(lambda m: f'((var_event_1)->{m[1]} == scpp::string{{{m[2]}, {m[3]}}})', body)
        if mode in ('helper', 'helper-noinline'):
            field = next(iter(fields))
            helpers.add(field)
            body = LEAF.sub(lambda m: f'observe_{field}_literal(var_book_0, {m[2]}, {m[3]}, var_event_1);', body)
        if mode == 'blocks':
            def block(m):
                return '{ ' + m[0].split(' -> void { ',1)[1].removesuffix(' }());').replace('return observe_', 'observe_') + ' }'
            body = LEAF.sub(block, body)
        if mode in ('tail', 'combined'):
            # Each selected branch ends the function after the same call.
            # Only trivial literal address and length travel across the join.
            body = body.replace('\n{\n','\n{\n\tconst char* key_bytes = nullptr;\n\tscpp::int64 key_size = 0;\n',1)
            body = LEAF.sub(lambda m:f'key_bytes = {m[2]}; key_size = {m[3]};',body)
            body = body[:-2] + f'''    if (key_bytes) {{
        owner_Dashboard book = var_book_0;
        scpp::string key{{key_bytes, key_size}};
        scpp::int64 value = (var_event_1)->{next(iter(fields))};
        observe_(static_cast<owner_Dashboard&&>(book), static_cast<scpp::string&&>(key), value);
    }}
}}
'''
        if mode in ('literal', 'combined'):
            body = CMP.sub(lambda m:f'(equal_bytes((var_event_1)->{m[1]}, {m[2]}, {m[3]}))',body)
        return body
    result = REPORT.sub(visit,text)
    assert counts['reports']==31 and counts['leaves']==601 and counts['comparisons']==413
    if helpers:
        prefix = '[[clang::noinline]] ' if mode == 'helper-noinline' else ''
        definitions = []
        for field in sorted(helpers):
            definitions.append(f"""{prefix}static void observe_{field}_literal(const owner_Dashboard& source,
        const char* bytes, scpp::int64 length, const owner_ReportEvent& event) {{
    owner_Dashboard book = source;
    scpp::string key{{bytes, length}};
    scpp::int64 value = event->{field};
    observe_(static_cast<owner_Dashboard&&>(book), static_cast<scpp::string&&>(key), value);
}}
""")
        pos = REPORT.search(result).start()
        result = result[:pos]+''.join(definitions)+result[pos:]
    if mode in ('literal','combined'):
        pos = REPORT.search(result).start()
        result = result[:pos]+'''static inline bool equal_bytes(const scpp::string& s, const char* p, scpp::int64 n) {
    return s.size() == n && __builtin_memcmp(s.data(), p, n) == 0;
}
'''+result[pos:]
    return result, counts


def extra_case(directory):
    rng = random.Random(93)
    rows = []
    for _ in range(1500):
        rows.append(' '.join(map(str,['svc',rng.choice('GET PUT HEAD POST PATCH DELETE'.split()),
            rng.choice(['/health','/ready','/assets/app.js','/api/a/b/c','/']),rng.choice([200,299,300,399,400,499,500,599]),
            rng.choice([0,255,256,4095,4096]),rng.choice([0,50,51,250,251,1000,1001]),rng.choice(['HIT','MISS','BYPASS'])])))
    directory.mkdir()
    (directory/'input.txt').write_text('# dashboard\n'+'\n'.join(rows)+'\n')
    return dashboard('access-log',100,rows).encode()


def diagnostics(flags,unit,work):
    trace = work/'trace.json'
    run([*flags,'-ftime-trace='+str(trace),'-c',unit,'-o',work/'traced.o'])
    events = json.loads(trace.read_text())['traceEvents']
    totals = {e['name'][6:]:round(e['dur']/1000,2) for e in events if e['name'].startswith('Total ')}
    data = {'trace':totals}
    for key, more in [('before',['-Xclang','-disable-llvm-passes']),('after',[])]:
        ir = run([*flags,*more,'-S','-emit-llvm',unit,'-o','-']).stdout
        data[key] = dict(bytes=len(ir),functions=ir.count(b'\ndefine '), invokes=len(re.findall(rb'\binvoke\b',ir)),
                         landingpads=len(re.findall(rb'\blandingpad\b',ir)))
    data['text_sections_bytes'] = sum(int(line.split()[1]) for line in
        run(['size','-A',work/'traced.o']).stdout.decode().splitlines() if line.startswith('.text'))
    return data


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('v02',type=Path)
    parser.add_argument('output',type=Path)
    parser.add_argument('--php',default='php')
    parser.add_argument('--followup',action='store_true')
    args=parser.parse_args()
    modes = ['baseline','borrowed','helper','helper-noinline'] if args.followup else MODES
    root=args.v02.resolve()
    os.environ['XDEBUG_MODE']='off'
    flags=['clang++','-std=c++23','-stdlib=libc++','-O2','-g0','-I'+str(root/'runtime/generated/public')]
    link=['clang++','-stdlib=libc++','--ld-path='+shutil.which('ld.mold')]
    rng=random.Random(1403)
    rows, execution,diag=[],[],{}
    with tempfile.TemporaryDirectory() as t:
        temp=Path(t)
        for case in ['shared','full']:
            fixture('access-log',100,temp/case,case=='full')
        extra=extra_case(temp/'variation')
        for i in range(-1,5):
            order=modes.copy()
            rng.shuffle(order)
            for mode in order:
                w=temp/mode/str(i)
                app,out=w/'app',w/'out'
                app.mkdir(parents=True)
                for file in ['main.phs','module.json']:
                    shutil.copyfile(SOURCE/file,app/file)
                start=time.perf_counter()
                info=json.loads(run([args.php,root/'compiler/tests/incremental_executable.php',app,out],input=b'"run"\n').stdout)
                if 'error' in info: raise ValueError(info['error'])
                manifest=json.loads((out/'manifest.json').read_text())
                unit=out/next(iter(manifest['sources'].values()))
                original=unit.read_text()
                cpp,counts=change(original,mode)
                unit.write_text(cpp)
                prepared=time.perf_counter()
                run([*flags,'-c',unit,'-o',w/'app.o'])
                native=time.perf_counter()
                run([*flags,'-c',out/'entry.cpp','-o',w/'entry.o'])
                link_start=time.perf_counter()
                run([*link,w/'app.o',w/'entry.o',*manifest['link_inputs'],'-o',w/'program'])
                finish=time.perf_counter()
                for case in [BASE/'access-log',temp/'shared',temp/'full']:
                    assert run([w/'program'],cwd=case).stdout==(case/'expected.txt').read_bytes()
                assert run([w/'program'],cwd=temp/'variation').stdout.endswith(extra)
                if i>=0:
                    rows.append(dict(round=i,mode=mode,cpp_bytes=len(cpp.encode()),total_ms=(finish-start)*1000,
                                     compile_ms=(native-prepared)*1000,link_ms=(finish-link_start)*1000,compiler_ms=info['php_ms']))
                if i==0:
                    diag[mode]=diagnostics(flags,unit,w)
                    for kind,options in [('timing',[]),('memory',['-DCOUNT_ALLOCATIONS','-Wl,--wrap=_Znwm'])]:
                        run([*flags,link[-1],*options,BASE/'runtime.cpp',w/'app.o',*manifest['link_inputs'],'-o',w/kind])
        for i in range(7):
            jobs=[(m,c) for m in modes for c in ['shared','full']]
            rng.shuffle(jobs)
            for m,c in jobs:
                for kind in (['timing','memory'] if i==0 else ['timing']):
                    passes=24 if kind=='timing' else 1
                    res=run(['taskset','-c','8',temp/m/'0'/kind,temp/c/'input.txt',str(passes)])
                    ns,rss,calls,size=map(int,res.stderr.split())
                    execution.append(dict(round=i,mode=m,case=c,kind=kind,cpu_ms=ns/(passes*1e6),
                                          allocations=calls,requested_bytes=size))
    args.output.write_text(json.dumps(dict(host=platform.node(),counts=counts,original_sha256=hashlib.sha256(
        original.encode()).hexdigest(),builds=rows,execution=execution,diagnostics=diag),indent=2)+'\n')


if __name__=='__main__':
    main()
