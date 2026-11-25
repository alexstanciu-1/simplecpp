"""Render the checked-in dev002 measurements."""
import json
from pathlib import Path
from statistics import median

here=Path(__file__).resolve().parent
data=json.loads((here/'results.dev002.json').read_text())
rows=data['rows']
lines=['# First tokenizer proof on dev002', '', 'Doc Status: supporting', '',
       'Measured on '+data['host']+' using '+rows[0]['cc']+' and '+rows[-1]['cc']+'.',
       '', 'The PHP lexer supplied every expected token ID, byte offset, length, spelling and first invalid-byte diagnostic. Both native compilers passed the eleven inputs per flavor for generated and handwritten scanners. Each executable also scanned valid, edited, invalid and restored bytes in one process while retaining the original and a sibling result.', '',
       'Each scanner receives preloaded bytes by value and returns owned source bytes and an ordered vector of three 64-bit integers (24 bytes per token). The handwritten scanner moves its input, compares keyword spans with a local string view and appends directly to the result. Generated C++ uses checked byte reads, copies the input to the result and assigns the completed local vector by value. Both cold policies start token backing at four slots on these nonempty inputs and grow geometrically. The handwritten prior-count run reserves the actual count from a previous revision with one extra identifier. The published application-vector API provides append without a reserve binding.', '',
       '## Scan throughput and allocation cost', '',
       'Rates are the median of nine batches of 128 in-memory scans after warmup. Brackets give the observed minimum and maximum MiB/s. Scanning includes result destruction. File I/O, validation dumps and printing are outside the interval. A separate executable counts requested C++ heap allocations and bytes for one scan. These are warm-cache, single-thread measurements. Identifiers, operators and mixed inputs repeat fixed patterns 512 times.', '',
       '| Compiler | Flavor | Corpus (bytes / tokens) | Generated MiB/s [range] | C++ MiB/s [range] | Generated / C++ | Mtokens/s gen / C++ | Allocations gen / C++ | Bytes allocated gen / C++ | C++ prior MiB/s / allocations |',
       '|---|---|---|---:|---:|---:|---:|---:|---:|---:|']
def rate(c):
    samples=[c['bytes']/2**20/t for t in c['seconds']]
    return f'{median(samples):.1f} [{min(samples):.0f}–{max(samples):.0f}]'
for base,gen in zip(rows[::2],rows[1::2]):
    assert base['flavor']==gen['flavor'] and base['implementation']=='handwritten'
    for name in ('identifiers','operators','mixed'):
        b=base['cases'][name+'-cold'];g=gen['cases'][name+'-cold'];prior=base['cases'][name+'-prior']
        tb,tg=median(b['seconds']),median(g['seconds'])
        lines.append(f"| {gen['cc'].split()[1] if 'clang' in gen['cc'] else 'GCC'} | {gen['flavor']} | {name} ({g['bytes']} / {g['tokens']}) | {rate(g)} | {rate(b)} | {tb/tg:.2f} | {g['tokens']/tg/1e6:.1f} / {b['tokens']/tb/1e6:.1f} | {g['allocations']} / {b['allocations']} | {g['allocated_bytes']} / {b['allocated_bytes']} | {prior['bytes']/2**20/median(prior['seconds']):.1f} / {prior['allocations']} |")
lines+=['','## Build costs','','C++23, O2, g0, libstdc++, default linker, sequential builds, no LTO/PCH/ccache. Scanner unit compilation and executable link are measured three times each. Each link uses a prebuilt driver and generated scanners also link a prebuilt runtime object. Native wall is the median paired sum. Scanner bytes count the translation unit alone. Clang frontend/backend milliseconds come from a separate instrumented build. Raw traces include nested optimizer/codegen subtotals in the JSON.', '',
        '| Compiler | Flavor | Scanner | C++ bytes | Compile ms | Link ms | Native wall ms | Clang frontend / backend ms |','|---|---|---|---:|---:|---:|---:|---:|']
for r in rows:
    prof=r['profile_ms']
    lines.append('| '+' | '.join(['Clang' if prof else 'GCC',r['flavor'],r['implementation'],str(r['source_bytes']),f"{median(r['compile_ms']):.1f}",f"{median(r['link_ms']):.1f}",f"{median(a+b for a,b in zip(r['compile_ms'],r['link_ms'])):.1f}",f"{prof['Total Frontend']:.1f} / {prof['Total Backend']:.1f}" if prof else '—'])+' |')
lines+=['','PHP 8.5 medians from five fresh processes on dev002 (prepared two source units, then emitted C++ in memory, process startup excluded).','',
        '| Flavor | Prepare ms | Emit ms |','|---|---:|---:|']
for f,v in data['php_prepare_emit_ms'].items():
    lines.append(f'| {f} | {median(x[0] for x in v):.2f} | {median(x[1] for x in v):.2f} |')
lines+=['','## What the generated code shows','',
        'Inside identifier recognition, every input byte goes through the runtime byte_at call. Length-matched keywords reread the candidate bytes through that same API. The scanner constructs no substring there. At publication, the assignment from the local application vector into the result invokes the generated vector copy assignment. Its storage copy and the owned string copies contribute to the allocation totals. Redundant scalar casts are visible in the emitted C++, but source inspection alone cannot attribute execution time to them.',
        '', 'The workload split makes identifier scanning the first candidate for an isolated comparison. Hold the vector/result path fixed and vary only spelling comparison or byte access. Then hold scanning fixed and measure token publication separately. The current measurements cover the combined scanner.']
lines+=['','## Reproduce','','From the checkout root, use a fresh output directory. The first command emits the three Simple C++ fixtures, exercises incremental compiler recovery, and writes PHP oracle data. The second runs the native proofs and benchmark. On a separate host transfer the fixture directory, benchmark directory, compiler (including generated bindings), the API generator PHP sources and templates, and matching runtime sources, metadata and generated headers.','','```sh','python3 -B compiler/tests/tokenizer_scanner_native.py /tmp/tokenizer-fixtures','PHP=php8.5 XDEBUG_MODE=off python3 specs/planning/benchmarks/tokenizer/run.py /tmp/tokenizer-fixtures runtime','```','','[Raw samples](results.dev002.json)','']
(here/'report.md').write_text('\n'.join(lines))
