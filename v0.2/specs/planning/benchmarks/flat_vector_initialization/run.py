"""Measure actual flat-list emission against read-only and test-only presized-append baselines."""
import argparse
import copy
import hashlib
import json
import os
from pathlib import Path
import random
import statistics
import subprocess
import time

ROOT = Path(__file__).resolve().parents[4]


def run(args):
    start = time.perf_counter()
    p = subprocess.run(list(map(str, args)), capture_output=True, text=True, env={**os.environ, 'XDEBUG_MODE':'off'})
    assert p.returncode == 0, (args, p.stdout, p.stderr)
    return p.stdout, (time.perf_counter() - start) * 1000


def driver(element, count):
    native = 'int64' if element == 'int' else 'string'
    vector = 'scpp::vector_' + native
    decl = '\n'.join(f'{vector} build{i}_();' for i in range(12))
    checks = ''
    for site in range(12):
        if element == 'int':
            check = f'if (v.at(i) != i + {site}) return 91;'
        else:
            check = f'auto expected = "heap-backed literal input number " + std::to_string(i) + " site {site}"; expected += "\\0end"s; auto x = v.at(i); if (std::string_view(x.data(), x.size()) != expected) return 92;'
        checks += f'{{ auto v = App::build{site}_(); if (v.size() != {count}) return 90; for (scpp::int64 i=0; i<{count};++i) {{ {check} }} }}\n'
    return f'''#include <scpp/vectors.hpp>
#include <chrono>
#include <cstdio>
#include <string>
#include <string_view>
using namespace std::string_literals;
namespace App {{ {decl} }}
int main() {{
{checks}
for (int i=0;i<200;++i) {{ auto v=App::build0_(); }}
unsigned long long n=1024, checksum=0; double elapsed;
do {{
 auto begin=std::chrono::steady_clock::now();
 for(unsigned long long i=0;i<n;++i) {{ auto v=App::build0_(); checksum+=v.size(); }}
 elapsed=std::chrono::duration<double,std::nano>(std::chrono::steady_clock::now()-begin).count();
 if(elapsed>=30000000) break;
 n*=2;
}} while(n<16777216);
std::printf("%.3f %llu\\n",elapsed/n,checksum);
}}
'''


def measure(work, php, repeats):
    work.mkdir(parents=True, exist_ok=True)
    runtime = ROOT / 'runtime'
    generator = ROOT / 'tools/api_generator/generate.php'
    facts = ROOT / 'build/clang/native-layout/facts.json'
    m = json.loads((runtime / 'metadata/runtime.json').read_text())
    m['vector']['initialization']['bulk'].pop('consume')
    metadata = work / 'readonly.json'; metadata.write_text(json.dumps(m))
    compiler = work / 'readonly-compiler'
    run([php, generator, 'emit-compiler', metadata, runtime / 'metadata/publication.json', facts, facts.with_name('context.json'), compiler])
    boot = work / 'readonly-boot.php'
    bootstrap = (ROOT / 'compiler/boot.php').read_text().replace("__DIR__ . '/generated/", repr(str(compiler) + '/') + " . '")
    boot.write_text(bootstrap.replace('__DIR__', repr(str(ROOT / 'compiler'))))
    samples, fixtures = {}, {}
    for mode, path in [('owned', ROOT / 'compiler/boot.php'), ('readonly', boot)]:
        for repeat in range(repeats):
            out = work / (mode + '.json')
            _, wall = run([php, ROOT / 'compiler/tests/list_initialization_benchmark.php', path, out])
            rows = json.loads(out.read_text())
            samples.setdefault(mode + '_batch_wall_ms', []).append(wall)
            for name, row in rows.items():
                if row['cpp'].count('::from_owned(') == 0 and mode == 'owned' and name.endswith('bulk'): raise AssertionError(name)
                if name.endswith('append') and mode == 'readonly': continue
                key = name.rsplit('-', 1)[0] + '-' + (mode if name.endswith('bulk') else 'presized_append')
                samples.setdefault(key, []).append({k:row[k] for k in ('prepare_ms','emit_ms')})
                fixtures[key] = row
    # Benchmark-only reserve factory; production has no public reserve method.
    support = (runtime / 'generated/src/runtime.cpp').read_text()
    methods, definitions = '', ''
    for native in ('int64','string'):
        v = 'vector_' + native
        methods += f'static {v} presized_{native}(int64 n) {{ {v} v; v.storage_=new {v}::storage; v.storage_->reserve(n); return v; }}\n'
        definitions += f'namespace scpp {{ {v} bench_presized_{native}(int64 n) {{ return detail::vector_access::presized_{native}(n); }} }}\n'
    anchor = 'namespace detail { struct vector_access {\n'
    assert anchor in support
    support = support.replace(anchor, anchor + methods, 1) + definitions
    support_path = work / 'timing-runtime.cpp'; support_path.write_text(support)
    inc = [arg for path in [runtime / 'generated/public', runtime / 'generated/private', runtime / 'src/numeric', runtime / 'src/sequence'] for arg in ('-I', str(path))]
    report = {'host':run(['hostname'])[0].strip(), 'flags':'C++23 O2 g0; sequential; no LTO/PCH/ccache; reused runtime and driver objects; separate Clang trace compile',
              'repeats':repeats, 'php_version':run([php,'-v'])[0].splitlines()[0], 'php_samples':samples,
              'runtime_sha256':hashlib.sha256((runtime / 'generated/src/runtime.cpp').read_bytes()).hexdigest(), 'cases':{}, 'setup':{}}
    for cxx in ('clang++', 'g++'):
        build = work / cxx; build.mkdir(exist_ok=True)
        flags = ['-std=c++23','-O2','-g0','-Wall','-Wextra','-Werror',*inc]
        report[cxx] = run([cxx,'--version'])[0].splitlines()[0]
        obj = build / 'runtime.o'
        _, runtime_ms = run([cxx,*flags,'-c',support_path,'-o',obj])
        report['setup'][cxx] = {'runtime_compile_ms':runtime_ms,'drivers':{}}
        drivers = {}
        for key,row in fixtures.items():
            if row['case'] not in drivers:
                path=build/(row['case']+'-driver.cpp');path.write_text(driver(row['element'],row['count']))
                target=path.with_suffix('.o');_, ms=run([cxx,*flags,'-c',path,'-o',target]);drivers[row['case']]=target
                report['setup'][cxx]['drivers'][row['case']]=ms
            code=row['cpp'].replace('int main()', 'int benchmark_unused_main()')
            if key.endswith('presized_append'):
                native='int64' if row['element']=='int' else 'string'
                declaration=f'namespace scpp {{ vector_{native} bench_presized_{native}(int64); }}\n'
                code=code.replace('namespace App {',declaration+'namespace App {',1).replace(f'scpp::vector_{native}{{}}',f'scpp::bench_presized_{native}({row["count"]})')
            path=build/(key+'.cpp');path.write_text(code)
            report['cases'][cxx+'-'+key]={'source_bytes':row['source_bytes'],'cpp_bytes':len(code.encode()),'samples':[]}
        schedule=list(fixtures)*repeats;random.Random(726).shuffle(schedule)
        for key in schedule:
            row=fixtures[key];path=build/(key+'.cpp');target=path.with_suffix('.o');exe=build/'bench'
            _, compile_ms=run([cxx,*flags,'-c',path,'-o',target])
            _, link_ms=run([cxx,target,obj,drivers[row['case']],'-o',exe])
            output,_=run([exe]);ns=float(output.split()[0])
            report['cases'][cxx+'-'+key]['samples'].append({'compile_ms':compile_ms,'link_ms':link_ms,'native_wall_ms':compile_ms+link_ms,'execute_ns':ns})
        if cxx=='clang++':
            for key in fixtures:
                path=build/(key+'.cpp');target=build/'profile.o'
                _, ms=run([cxx,*flags,'-ftime-trace','-c',path,'-o',target])
                trace=json.loads(target.with_suffix('.json').read_text())
                totals={e['name']:e['dur']/1000 for e in trace['traceEvents'] if e['name'].startswith('Total ')}
                report['cases'][cxx+'-'+key]['profile']={'compile_ms':ms,**totals}
        (work/'results.json').write_text(json.dumps(report,indent=2))
    print('PASS',len(report['cases'])*repeats,'clean native timing trials; full contents checked at all 12 sites')


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--out',type=Path,required=True);p.add_argument('--php',default='php8.5');p.add_argument('--repeats',type=int,default=3)
    a=p.parse_args();measure(a.out.resolve(),a.php,a.repeats)
