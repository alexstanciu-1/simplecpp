#!/usr/bin/env python3
"""Check the evidence and write a compact derived timing table."""
import hashlib
import json
from pathlib import Path
HERE=Path(__file__).resolve().parent
ROOT=HERE.parents[3]
report=json.loads((HERE/'results.json').read_text())
validation=json.loads((HERE/'validation_extra.json').read_text())
assert validation['asan_ubsan_passed'] and validation['owner_balance_passed']
assert len(validation['template_audit'])==18
assert not any(x['template_declarations'] for x in validation['template_audit'].values())
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
assert all(digest(ROOT/'examples/accounting_workload/.prism/generated'/p)==h for p,h in report['original_sha256'].items())
assert all(digest(HERE/'converted'/p)==h for p,h in report['converted_sha256'].items())
library=ROOT/'examples/accounting_workload/.prism/runtime/project/php-strict/116def5680c631b9/libruntime.so'
assert digest(library)==report['runtime_sha256']
assert set(report['results'])=={'O0','O2'}
for result in report['results'].values():
 assert set(result['variants'])=={'original','eager','concrete'}
 for samples in result['variants'].values():
  assert len(samples)==3 and all(s['behavior_passed'] for s in samples)
text='''# Measured native compilation results
Doc Status: planning

Median seconds across three serial repetitions. Every executable passed all 998
accounting checks. See README.md for scope and exclusions.

| Optimization | Representation | Application compile | New support compile | Link | Total native |
|---|---|---:|---:|---:|---:|
'''
for opt,result in report['results'].items():
 for variant,m in result['medians'].items():
  text+='| -'+opt+' | '+variant+' | '+' | '.join(f'{m[k]:.3f}' for k in ['application_compile_s','support_compile_s','link_s','total_native_s'])+' |\n'
text+='\nPCH creation (excluded from the table):\n\n'
for opt,result in report['results'].items():
 text+='- -'+opt+': '+', '.join(f'{variant} {seconds:.3f} s' for variant,seconds in result['pch_setup_s'].items())+'.\n'
text+='\nConcrete mode does not use a PCH or the original runtime shared library. Its total\nincludes compilation of all its support code. The existing original runtime library\nis reused for both original modes. Totals are paired medians and need not equal\nthe sum of independently reported median components.\n\n'
for opt,result in report['results'].items():
 m=result['medians'];app=m['concrete']['application_compile_s'];total=m['concrete']['total_native_s']
 text+=f"At -{opt}, concrete application compilation is {m['original']['application_compile_s']/app:.2f}x faster than ordinary PCH and {m['eager']['application_compile_s']/app:.2f}x faster than eager PCH. Including new support and linking, the ratios are {m['original']['total_native_s']/total:.2f}x and {m['eager']['total_native_s']/total:.2f}x.\n\n"
text+='''These results do not establish that C++ templates cannot be optimized: enabling
-fpch-instantiate-templates substantially improves the original baseline. They show
that explicit concrete representations still reduce native compilation work for this
application, even after that improvement. Other PCH/codegen/explicit-instantiation
configurations remain unmeasured. Representation/library changes mean the ratios
cannot be attributed solely to template syntax.

Validation passed for the unchanged generated input and final converted-file hashes,
unchanged original runtime binary, 18 benchmark executions, original/concrete
container/string probes, full concrete ASan/UBSan, zero outstanding shared owners,
and zero template declarations across all 18 preprocessed native inputs. LeakSanitizer
was disabled; no runtime performance or full production-runtime equivalence claim
is made.
'''
(HERE/'RESULTS.md').write_text(text)
print(text)
