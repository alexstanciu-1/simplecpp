"""Render the four-way storage comparison from the dev002 samples."""
from pathlib import Path
from statistics import median
import json

here = Path(__file__).resolve().parent
data = json.loads((here/'results.dev002.json').read_text())
variants = ('copy','reserve_copy','move','reserve_move')
lines = ['# Token storage on dev002', '', 'Doc Status: supporting', '',
         'The four configurations use the same emitted recognition loop. Copy is the existing final vector assignment. Reserve supplies the preceding successful scan’s token count to the vector’s existing checked storage growth. Move invokes the existing nonthrowing move assignment at the last use of the private local. The source snapshot and all byte reads retain their original value and bounds checks.', '',
         'The experiment builds private copies of the generated scanner and vector headers. These measurements evaluate candidate lowering and runtime behavior before adding a language API. A production reserve binding would need metadata and a defined error policy. A compiler move would require proof of last use and absence of observable aliases.', '',
         '## Median scan throughput', '',
         'MiB/s on repeated in-memory inputs. The last three columns give ratios over copy with geometric growth in the same compiler, flavor and corpus. Higher is faster. Each median contains 27 batches of 128 scans. Three process-level repetitions shuffled variant order within each group. Raw samples include batch spread.', '',
         '| Compiler | Flavor | Input | Copy | Reserve + copy | Move | Reserve + move | Reserve only | Move only | Both |',
         '|---|---|---|---:|---:|---:|---:|---:|---:|---:|']
for row in data['rows']:
    for corpus in ('identifiers','operators','mixed'):
        cases = [row['cases'][v]['corpora'][corpus] for v in variants]
        rates = [c['bytes']/2**20/median(c['seconds']) for c in cases]
        fields = [row['cc'],row['flavor'],corpus,*[f'{v:.1f}' for v in rates],
                  *[f'{v/rates[0]:.2f}×' for v in rates[1:]]]
        lines.append('| '+' | '.join(fields)+' |')
lines += ['', '## Heap traffic', '', 'Entries show allocation calls / total requested bytes per successful scan. Counts come from separate instrumented executables and exclude fixture loading and the scan supplying the hint.', '',
          '| Input | Copy | Reserve + copy | Move | Reserve + move |','|---|---:|---:|---:|---:|']
for name in ('identifiers','operators','mixed'):
    counts = {tuple((r['cases'][v]['corpora'][name]['allocations'],r['cases'][v]['corpora'][name]['allocated_bytes']) for v in variants) for r in data['rows']}
    assert len(counts)==1, counts
    lines.append('| '+name+' | '+' | '.join(f'{n} / {b:,}' for n,b in counts.pop())+' |')
lines += ['', '## Method and verification', '',
          f"Host {data['host']}. "+'; '.join(sorted({r['version'] for r in data['rows']}))+'. C++23, O2, g0, default linker/libstdc++, one compilation at a time, no LTO/PCH. Each variant links against the same driver and prebuilt runtime for that compiler and flavor. Source compile times in the raw data are single build observations.',
          '', 'The timed previous revision has one additional trailing identifier. Correctness runs include zero, low and high hints. All variants compare complete token dumps (including slices) and lexical diagnostics to the PHP oracle, and require an empty published token list on failure. The edit sequence retains original and sibling results, updates the hint after success, keeps the last successful count through failure and checks restored tokens. The separate resident compiler proof checks fresh equivalence and sibling token-list identity across source edits.',
          '', 'The allocator and repeated short corpora are part of these results. The measurements isolate the two storage changes within each row. They do not measure random cold inputs, peak RSS or the performance of a full tokenizer.',
          '', '## Reproduce', '', 'Run in a fresh directory on the benchmark host with matching compiler-generated runtime files.', '',
          '```sh','python3 -B v0.2/compiler/tests/tokenizer_scanner_native.py /tmp/token-storage-fixtures',
          'python3 v0.2/specs/planning/benchmarks/tokenizer/storage/measure.py /tmp/token-storage-fixtures v0.2/runtime /tmp/token-storage-run','```','',
          '[Raw measurements](results.dev002.json) · [Original handwritten comparison](../report.md)','']
(here/'report.md').write_text('\n'.join(lines))
