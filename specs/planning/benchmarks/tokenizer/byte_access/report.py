"""Render the presized scanner comparisons from recorded raw samples."""
import json
from pathlib import Path
from statistics import median

here = Path(__file__).resolve().parent
data = json.loads((here/'results.dev002.json').read_text())
labels = ('runtime_copy','runtime_move','inline_checked_copy','inline_checked_move',
          'direct_copy','direct_move','handwritten')
corpora = ('identifiers','operators','mixed')
assert len(data['rows']) == 6


def rate(row, label, corpus):
    case=row['cases'][label][corpus]
    return case['bytes']/2**20/median(case['seconds'])


lines=['# Presized tokenizer byte access and publication', '', 'Doc Status: supporting', '',
       'The source scanners in cpp/php/js were emitted by the PHP compiler. The experiment changes copies of those emitted units on '+data['host']+'. All variants receive token counts from the same previous revision and retain the original recognition loop. The handwritten scanner is rebuilt and timed alongside them.', '',
       '| Access mode | Per-byte operation |','|---|---|',
       '| Runtime | Existing out-of-line string byte_at, which recovers a span and checks its index. |',
       '| Inline checked | Cache the source pointer and byte length once per scan. An inline read checks the signed index before dereferencing. |',
       '| Direct | Same cached pointer and length, with only the scanner’s existing loop and keyword-length guards. |','',
       'Each mode is measured with final vector copy assignment and with last-use move assignment. Source-string ownership stays the same in all six generated variants. The inline-checked/direct pair isolates the explicit range guard within that representation, including checks the native optimizer can eliminate. Runtime/inline-checked includes the call boundary and repeated span access together.', '',
       '## Paired throughput ratios', '',
       'Median of six within-corpus ratios, one for each flavor/compiler pair. A value above 1 means the change increased throughput. The precise baseline of each comparison is given in the column label.', '',
       '| Corpus | Inline checked / runtime (copy) | Direct / inline checked (copy) | Move / copy (runtime) | Move / copy (inline checked) | Move / copy (direct) | Runtime + copy / handwritten | Inline checked + move / handwritten | Direct + move / handwritten |',
       '|---|---:|---:|---:|---:|---:|---:|---:|---:|']
pairs=[('inline_checked_copy','runtime_copy'),('direct_copy','inline_checked_copy'),('runtime_move','runtime_copy'),
       ('inline_checked_move','inline_checked_copy'),('direct_move','direct_copy'),('runtime_copy','handwritten'),
       ('inline_checked_move','handwritten'),('direct_move','handwritten')]
for c in corpora:
    ratios=[median(rate(r,a,c)/rate(r,b,c) for r in data['rows']) for a,b in pairs]
    lines.append('| '+c+' | '+' | '.join(f'{v:.2f}×' for v in ratios)+' |')
lines+=['','## Fraction of elapsed-time gap closed', '',
        'For each matched binary pair, divide time saved per scan by the original runtime-copy time minus handwritten time. The table gives medians across the six pairs. These interventions interact, so the separate percentages do not add.', '',
        '| Corpus | Cached checked reads alone | Final move alone | Cached checked reads and final move |',
        '|---|---:|---:|---:|']
for c in corpora:
    values=[]
    for label in ('inline_checked_copy','runtime_move','inline_checked_move'):
        ratios=[]
        for row in data['rows']:
            def duration(l): return median(row['cases'][l][c]['seconds'])
            original=duration('runtime_copy')
            ratios.append((original-duration(label))/(original-duration('handwritten')))
        values.append(median(ratios))
    lines.append('| '+c+' | '+' | '.join(f'{v:.0%}' for v in values)+' |')
lines+=['','## Allocation sites', '',
        'A separate executable marks scanner phases and records the phase and requested size at each C++ heap allocation in a fixed array. All six flavor/compiler pairs agreed. The following are the individual allocation sizes in bytes for the mixed corpus (20,480 input bytes, 5,120 tokens, previous count 5,121).','',
        '| Configuration | Input argument | Result source | Reserve tokens | Publish tokens | Total calls | Total requested bytes |','|---|---:|---:|---:|---:|---:|---:|']
for label in ('runtime_copy','runtime_move','handwritten'):
    events=data['rows'][0]['cases'][label]['mixed']['events']
    for row in data['rows']:
        assert sorted(row['cases'][label]['mixed']['events'])==sorted(events)
        if label!='handwritten':
            suffix=label.split('_')[-1]
            for mode in ('inline_checked','direct'):
                assert sorted(row['cases'][mode+'_'+suffix]['mixed']['events'])==sorted(events)
    fields=[', '.join(f'{size:,}' for phase,size in events if phase==s) or '—'
            for s in ('input argument','result source','reserve tokens','publish tokens')]
    lines.append('| '+label+' | '+' | '.join(fields)+f' | {len(events)} | {sum(n for _,n in events):,} |')
lines+=['','A heap-backed scpp string copy allocates a storage object and its byte buffer. The scanner takes a string by value and assigns it to the result, making two copies. Its vector likewise allocates a storage object and element backing during reserve, then allocates both again on copy publication. The handwritten std::string copy uses one byte allocation and transfers it to the result. Its embedded std::vector needs one backing allocation. Byte access itself allocates zero bytes during recognition.', '',
       '## Native throughput (MiB/s)','',
       '| Compiler | Flavor | Input | Runtime copy | Runtime move | Inline checked copy | Inline checked move | Direct copy | Direct move | Handwritten |','|---|---|---|---:|---:|---:|---:|---:|---:|---:|']
for row in data['rows']:
    for c in corpora:
        lines.append('| '+row['cc']+' | '+row['flavor']+' | '+c+' | '+' | '.join(f'{rate(row,l,c):.1f}' for l in labels)+' |')
lines+=['','## Verification and reproduction','',
        'All 42 binaries compare complete token outputs or lexical diagnostics for 11 fixtures against PHP. Each checks valid edits, failure and recovery while retaining the original and sibling results. For timing, the previous revision contains one additional identifier. Each median uses 27 batches of 128 in-memory scans, with preloaded bytes and shuffled executable order across three repetitions. Timing includes result destruction. Allocation hooks and phase markers appear only in the separate trace executables. The JSON contains individual batch durations and allocation events for every corpus.', '',
        'Compiler versions: '+', '.join(sorted({r['version'] for r in data['rows']}))+'. C++23, O2, g0, libstdc++, separate translation units, no LTO. These warm-cache repeated corpora measure a bounded scanner.', '',
        '```sh','python3 -B compiler/tests/tokenizer_scanner_native.py /tmp/lexer-fixtures',
        'python3 specs/planning/benchmarks/tokenizer/byte_access/measure.py /tmp/lexer-fixtures runtime /tmp/byte-proof','```','',
        '[Raw samples](results.dev002.json)','']
(here/'report.md').write_text('\n'.join(lines))
