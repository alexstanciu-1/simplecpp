"""Render the checked-in dev002 flat-vector measurements; no benchmark execution."""
import hashlib
import json
from pathlib import Path
import statistics

root = Path(__file__).resolve().parent
r = json.loads((root / 'results.dev002.json').read_text())
def median(row, metric):
    return statistics.median(s[metric] for s in row['samples'])
lines = ['# Flat vector initialization — dev002', '', 'Doc Status: supporting', '',
'2026-11-19. Actual generated flat vector construction, with fixed bindings per compiler session.', '',
'## Findings', '',
'The accepted lowering uses ordinary typed local arrays up to the metadata limit and typed heap arrays with a concrete cleanup guard above it. It emits one presized factory call. The initial placement-array experiment exposed a large Clang compile-time regression and was replaced before acceptance; the measurements below use the corrected typed-array implementation.', '',
'For the PHP-flavor 512-integer case, Clang compilation fell from the presized-append baseline’s **442.94 ms to 49.05 ms**, and execution from **1748.81 ns to 104.47 ns**. GCC compilation fell from **568.12 ms to 55.86 ms**. For 16 heap strings, consuming inputs approximately halved execution time relative to the read-only bulk factory. Compared with presized append, the string case trades a modest compilation increase for a smaller execution improvement. Small-case differences are not treated as stable winners.', '',
'## Method and limits', '',
'- Host: tf-dev002, shared Ryzen 9 5950X host. Clang 18.1.3 and GCC 13.3; PHP 8.5 is invoked explicitly. No WSL timings are used in this report.',
'- Three workloads × three construction modes × three source flavors × two native compilers × three randomized rounds = **162 clean compile/link/execution trials**. Each unit contains 12 independently named construction sites; every site receives a full correctness check before timing. Execution measures site zero’s construction, size observation and destruction after warmup, for an adaptive interval of at least roughly 30 ms.',
'- C++23, O2, g0, warnings as errors; libstdc++, default linker, one build at a time, no LTO/PCH/ccache. Runtime and workload-driver objects are compiled once per compiler and reused. Compile/link samples have no tracing, sanitizer or allocation overrides. The 27 Clang phase profiles come from separate instrumented compilations, not the clean wall samples.',
'- The owned and read-only modes are emitted by actual compiler sessions with separately generated bindings. The presized-append baseline starts from actual append emission and substitutes a benchmark-only reserve factory into that output. That helper reuses production storage allocation/reserve mechanics; it is not a new public source/runtime API. The same helper-augmented runtime object is linked into every timing variant.',
'- PHP prepare timing includes parse/collection and preparation for the 12-site source. Emit timing is the standalone C++ emitter. These run separately from native compilation; they are not a measured end-to-end build or retained-service wall time. Presized-append PHP timings describe the append source before the benchmark-only reserve substitution.',
'- Native wall below is fixture compile plus link; it excludes shared runtime/driver setup. Setup costs and all individual samples are retained in the JSON. Link timings include the fixed driver and runtime inputs. Filesystem caches are not flushed and CPUs are not exclusively reserved.',
'- Integer cases contain 3 or 512 entries. Heap-string inputs contain 16 distinct long strings with embedded NULs. All values at all sites are verified, not just counts/checksums. Allocation instrumentation and ASan/UBSan run in separate correctness proofs.',
'- Source incremental reuse is verified by retained-session tests. Provider changes are verified by regenerating exports and starting fresh PHP sessions; no runtime-provider hot reload is claimed.', '',
'## PHP-flavor native results', '',
'Compile/link/native wall are milliseconds per 12-site unit; execute is nanoseconds per construction.', '',
'| Compiler | Input | Mode | Compile | Link | Native wall | Execute ns | C++ bytes |',
'| --- | --- | --- | ---: | ---: | ---: | ---: | ---: |']
for cxx in ('clang++','g++'):
    for case in ('int3','int512','heap16'):
        for mode in ('owned','readonly','presized_append'):
            row = r['cases'][f'{cxx}-php-{case}-{mode}']
            lines.append(f'| {cxx} | {case} | {mode} | '+ ' | '.join(f'{median(row,m):.2f}' for m in ('compile_ms','link_ms','native_wall_ms','execute_ns'))+f' | {row["cpp_bytes"]} |')
lines += ['', '## Compiler stages across flavors', '', 'Median PHP milliseconds across three source preparations/emissions; separate single-run Clang profiles in milliseconds.', '',
'| Flavor | Input | PHP prepare | PHP emit | Clang frontend profile | LLVM/backend profile |', '| --- | --- | ---: | ---: | ---: | ---: |']
for flavor in ('cpp','php','js'):
    for case in ('int3','int512','heap16'):
        samples = r['php_samples'][f'{flavor}-{case}-owned']
        phase = r['cases'][f'clang++-{flavor}-{case}-owned']['profile']
        lines.append(f'| {flavor} | {case} | {statistics.median(x["prepare_ms"] for x in samples):.2f} | {statistics.median(x["emit_ms"] for x in samples):.2f} | {phase["Total Frontend"]:.2f} | {phase["Total Backend"]:.2f} |')
lines += ['', 'Phase totals cannot be subtracted from the separate clean wall samples. Optimizer/codegen subtotals are nested inside backend time; the raw profiles retain them.', '',
'## Verification and reproduction', '',
'Run the registered list-initialization compiler, native, ownership and provider tests. The final dev002 verification log records the exact fixture counts and sanitizer outcomes. Allocation failure is injected at every allocation position until success for consuming/read-only factories and heap-backed private inputs. Checks cover unchanged assignment destinations, repeated source lvalues, full string bytes, partial cleanup, exact destination capacity, zero growth and inline/heap boundaries.', '',
'From a checkout with generated runtime/compiler bindings and native layout facts:', '',
'```sh', 'XDEBUG_MODE=off php8.5 compiler/tests/list_initialization.php /tmp/list-fixtures.json',
'python3 -B compiler/tests/list_initialization_native.py --out /tmp/list-native --fixtures /tmp/list-fixtures.json',
'python3 -B compiler/tests/list_initialization_native.py --out /tmp/list-native-gcc --fixtures /tmp/list-fixtures.json --cxx g++',
'ASAN_OPTIONS=detect_stack_use_after_return=1:detect_leaks=1 python3 -B compiler/tests/list_initialization_ownership.py --out /tmp/list-ownership --fixtures /tmp/list-fixtures.json --sanitize',
'python3 -B specs/planning/benchmarks/flat_vector_initialization/run.py --out /tmp/list-measurements --php php8.5 --repeats 3', '```', '',
'[Raw measurements](results.dev002.json) · [Interactive comparison](report.html) · [Verification log](verification.dev002.txt)', '',
'No nested/keyed literals, user factory execution, application element initialization or hot-reload implementation is claimed. The older isolated comparison is background research, not these production measurements.']
(root/'report.md').write_text('\n'.join(lines)+'\n')
html='''<!doctype html><meta charset="utf-8"><title>Flat vector initialization — dev002</title>
<style>body{font:16px system-ui;background:#101827;color:#e8eef9;margin:32px}p{color:#b8c7de}select{padding:8px;margin:6px}table{border-collapse:collapse;width:100%;margin-top:20px}td,th{padding:10px;border-bottom:1px solid #3a4760;text-align:right}td:first-child,th:first-child{text-align:left}a{color:#8ec8ff}</style>
<h1>Flat vector initialization</h1><p>dev002 · Clang/GCC · O2 · 12 sites per unit · three clean rounds · no LTO</p>
<select id="compiler"><option>clang++</option><option>g++</option></select><select id="flavor"><option>cpp</option><option selected>php</option><option>js</option></select><select id="workload"><option>int3</option><option selected>int512</option><option>heap16</option></select><div id="table"></div>
<p>Times are medians. Compile/link: ms per 12-site unit; execution: ns per construction, size observation and destruction. Presized append uses a benchmark-only reserve adapter. Close results are not stable winner claims.</p><a href="report.md">Method, limitations and verification</a>
<script>const data=DATA;function med(a){a.sort((x,y)=>x-y);return a[Math.floor(a.length/2)]}function show(){let pre=document.querySelector('#compiler').value+'-'+document.querySelector('#flavor').value+'-'+document.querySelector('#workload').value+'-';let rows=['owned','readonly','presized_append'];document.querySelector('#table').innerHTML='<table><tr><th>Mode</th><th>Compile ms</th><th>Link ms</th><th>Execute ns</th><th>C++ bytes</th></tr>'+rows.map(m=>{let r=data.cases[pre+m];return '<tr><td>'+m+'</td>'+['compile_ms','link_ms','execute_ns'].map(k=>'<td>'+med(r.samples.map(s=>s[k])).toFixed(2)+'</td>').join('')+'<td>'+r.cpp_bytes+'</td></tr>'}).join('')+'</table>'}document.querySelectorAll('select').forEach(s=>s.onchange=show);show();</script>'''
(root/'report.html').write_text(html.replace('DATA',json.dumps(r)))
