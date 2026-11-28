"""Native representation proof using current generated owners and upcasts. No source union syntax required."""
import argparse
import json
from pathlib import Path
import re
import subprocess
import tempfile
import time

from app_namespace_native import invoke, service

ROOT = Path(__file__).resolve().parents[2]
FIXTURE = Path(__file__).with_name('union_owner')
ARMS = ('NameExpr', 'FieldExpr', 'LiteralExpr')


def signature(arms):
    union = '|'.join(sorted(arms))
    return ('int64\0value:' + union).encode().hex()


def emit(work):
    app, output = work/'app', work/'output'
    app.mkdir()
    (app/'module.json').write_text('{"entry":"main.phs"}')
    models = 'class Expr { public int $mark = 0; }\n'
    models += '\n'.join(f'class {name} extends Expr {{}}' for name in ARMS)
    (app/'model.phs').write_text(models)
    (app/'api.phs').write_text('function examine(Expr $value): int {return $value->mark;}')
    (app/'main.phs').write_text('\n'.join(f'examine(new {name}());' for name in ARMS)+'\nreturn 8;')
    server = service(app, output)
    try:
        report = invoke(server)
        assert 'error' not in report, report
    finally:
        server.stdin.close()
        assert server.wait() == 0
    manifest = json.loads((output/'manifest.json').read_text())
    units = {Path(key).stem: output/file for key,file in manifest['sources'].items()}
    adapters = {}
    for header in (output/'units').glob('*.hpp'):
        source = header.read_text()
        match = re.search(r'inline owner_Expr (scpp_adapter_\w+)\(owner_(\w+) source\) noexcept', source)
        if match:
            symbol, arm = match.groups()
            assert 'result.view = source.view;' in source and 'result.ptr = source.ptr;' in source
            assert all(token not in source for token in ('if (','new ', 'dynamic_cast','::require('))
            adapters[arm] = symbol
    assert set(adapters) == set(ARMS)
    names = {f'UP_{arm.removesuffix("Expr").upper()}': adapter for arm,adapter in adapters.items()}
    for key, arms in [('FIELD', ARMS[:2]), ('LITERAL', ARMS[::2])]:
        suffix = signature(arms)
        assert suffix == signature(reversed(arms))
        names['FREE_'+key] = 'scpp_function_read_'+suffix
        names['SLOT_'+key] = 'scpp_slot_read_'+suffix
        names['RETURN_'+key] = 'scpp_slot_choose_'+'|'.join(sorted(arms)).encode().hex()
    (work/'names.hpp').write_text(''.join(f'#define {key} {value}\n' for key,value in names.items()))
    return units, report['php_ms'], names


def build(work, cc, units, names):
    dest = work/Path(cc).name
    dest.mkdir()
    flags = ['-std=c++23','-O2','-g0','-fno-rtti','-Wall','-Wextra','-Werror',
             '-I',str(work),'-I',str(ROOT/'runtime/generated/public')]
    if cc == 'clang++':
        flags += ['-ftime-trace']
    # The two aliases alone cannot distinguish overloads or virtual slots in C++.
    collision = ('struct owner_Expr {}; using NameOrField = owner_Expr; using NameOrLiteral = owner_Expr;'
                 'struct Reader { virtual int read(NameOrField) = 0; virtual int read(NameOrLiteral) = 0; };')
    negative = subprocess.run([cc,'-std=c++23','-fsyntax-only','-x','c++','-'],input=collision,
                              text=True,capture_output=True)
    assert negative.returncode != 0 and 'read' in negative.stderr, negative.stderr
    start = time.perf_counter()
    jobs = []
    for label, source, include in [('caller',FIXTURE/'caller.cpp',units['main']),
                                   ('callee',FIXTURE/'callee.cpp',units['api']),
                                   ('model',units['model'],None),
                                   ('owner_access',ROOT/'runtime/src/ownership/owner_access.cpp',None)]:
        args = ['-include',str(include)] if include else []
        jobs.append(subprocess.Popen([cc,*flags,*args,'-c',source,'-o',label+'.o'],cwd=dest))
    assert all(status == 0 for status in [job.wait() for job in jobs])
    link = time.perf_counter()
    subprocess.run([cc,'-fuse-ld=mold',*sorted(dest.glob('*.o')),'-o',dest/'proof'],check=True)
    end = time.perf_counter()
    subprocess.run([dest/'proof'],check=True)
    symbols = subprocess.check_output(['nm','-C',dest/'proof'],text=True)
    for key in ['FREE_FIELD','FREE_LITERAL','SLOT_FIELD','SLOT_LITERAL']:
        assert names[key]+'(App::owner_Expr)' in symbols, key
    totals = {}
    for trace in dest.glob('*.json'):
        for event in json.loads(trace.read_text())['traceEvents']:
            if event['name'] in ('Total Frontend','Total Backend'):
                key = event['name']
                totals[key] = totals.get(key, 0) + event['dur']/1000
    print(cc, 'wall_ms',round((end-start)*1000,1),'link_ms',round((end-link)*1000,1),totals)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-union-owner-') as tmp:
        work = (args.output or Path(tmp)).resolve()
        work.mkdir(exist_ok=True)
        units, php_ms, names = emit(work)
        print('PHP compiler ms',round(php_ms,1),flush=True)
        for cc in ('clang++','g++'):
            build(work, cc, units, names)
