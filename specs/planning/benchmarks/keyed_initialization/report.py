"""Render the focused keyed initialization observations without inventing a comparison baseline."""
import json
from pathlib import Path
from statistics import median

HERE=Path(__file__).resolve().parent
data=json.loads((HERE/'results.dev002.json').read_text())
lines=['# Typed keyed initialization — dev002', '', 'Doc Status: supporting', '',
       'Measured 2026-11-19 on '+data['host']+'. '+data['clang++']+'; '+data['g++']+'.', '',
       'These are acceptance-cost observations, not a before/after optimization claim. '
       'One generated table is constructed per executable run. Execution wall includes process startup and output; '
       'it is **not** a per-operation construction-throughput benchmark.', '',
       '## Method', '',
       '- Four inputs × three source flavors × two native compilers × three rounds = 72 clean compile/link/executable samples. '
       'Twelve separate Clang compilations supply phase profiles.',
       '- C++23, O2, g0, warnings as errors, libstdc++, default linker, sequential jobs; no LTO, PCH or ccache. '
       'Runtime/hash/output/arithmetic objects from the native proof are reused; their build cost is excluded.',
       '- PHP 8.5 fresh processes. Prepare includes parsing/collection/analysis; emit is measured separately. '
       'PHP wall also includes process startup and loading. Filesystem caches are not flushed; dev002 is a shared host.',
       '- Each sample checks the program exit and count output. Separate source/native and allocation-failure proofs '
       'verify contents, ordering, duplicates, copies and cleanup. No sanitizer, allocation override or compiler tracing '
       'is present in clean timing samples.',
       '- Integer inputs exercise the 64-entry inline boundary. Strings use 16 distinct heap-backed values. '
       'Reported native wall is compile + link for that sample. The staged sum includes PHP process wall + native wall; '
       'it excludes harness orchestration and executable verification.', '', '## Clean medians', '',
       'Times are milliseconds; bytes are generated C++ for the full test unit.', '',
       '| Compiler | Flavor | Input | PHP prepare | PHP emit | PHP wall | Compile | Link | Native wall | Staged sum | Execute process | C++ bytes |',
       '| --- | --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |']
for row in data['cases']:
    values=[median(s[k] for s in row['samples']) for k in ('prepare_ms','emit_ms','php_wall_ms','compile_ms','link_ms','native_ms')]
    values += [median(s['php_wall_ms']+s['native_ms'] for s in row['samples']),median(s['execute_process_ms'] for s in row['samples'])]
    lines.append('| '+row['compiler']+' | '+row['flavor']+' | '+row['kind']+str(row['count'])+' | '+' | '.join(f'{v:.2f}' for v in values)+' | '+str(row['cpp_bytes'])+' |')
lines += ['', '## Separate Clang phase profiles', '',
          'Single instrumented runs, milliseconds. Optimizer/codegen subtotals are nested inside backend time; '
          'do not add them again or subtract them from clean median wall times.', '',
          '| Flavor | Input | Frontend | LLVM/backend | Optimizer | Codegen |', '| --- | --- | ---: | ---: | ---: | ---: |']
for row in data['cases']:
    if 'profile_ms' in row:
        lines.append('| '+row['flavor']+' | '+row['kind']+str(row['count'])+' | '+' | '.join(f"{row['profile_ms'].get(k,0):.2f}" for k in ('Total Frontend','Total Backend','Total Optimizer','Total CodeGenPasses'))+' |')
lines += ['', '## Verification and reproduction', '',
          '138 source/native fixtures passed in cpp/php/js on both Clang and GCC. Both ownership proofs passed '
          'with ASan/UBSan, leak detection and stack-use-after-return checking. Real retained-session Ninja '
          'tests additionally proved native object reuse and fresh-output equivalence in all flavors.', '',
          'Run `keyed_initialization_native.py --out native-clang` and the same command with `--cxx g++ --out native-gcc`. '
          'Then run this directory’s `run.py --out timing --objects <parent-of-native-clang-and-native-gcc> --php php8.5`. '
          'Source/runtime paths resolve from the checkout. `report.py` renders the checked-in JSON.', '',
          '[Raw measurements](results.dev002.json) · [Verification record](verification.dev002.txt)', '']
(HERE/'report.md').write_text('\n'.join(lines))
