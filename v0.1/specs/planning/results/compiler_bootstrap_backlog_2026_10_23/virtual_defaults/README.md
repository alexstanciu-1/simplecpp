# Explicit virtual defaults checkpoint
Doc Status: planning

Starting revision 2afcce1d1 plus bounded explicit virtual default-method support,
converter markers and six AST root annotations. Full snapshot and tracked diff:
`/tmp/compiler-bootstrap-virtual-defaults-01`. Reproduce with
`python3 compiler/my-try/tools/bootstrap_probe.py --results FRESH`.

Conversion: 4.65 s / 195,064 KiB RSS; 269 PHS files, 1,959,421 bytes.
Bootstrap: 199.05 s / 472,348 KiB RSS (461.3 MiB), 451.0 MiB PHP heap.
Initialization: 0.302 s; sync/parse: 4.292 s. The nonvirtual inherited-method
diagnostic disappears; no new diagnostic text. The first blocker is now the existing
nullable-value boundary limitation (NULL-01/02). No fatal/OOM, emission or native
compiler rebuild. No competing tests/builds ran during measurement.

Core fixture `/tmp/virtual-defaults-core-02` proves default/inherited, base/interface,
trait, final override and parent calls with vector signatures in both output modes.
`/tmp/virtual-defaults-preparation-03` adds fifteen negative cases, repeated rejection
and recovery, plus cross-file ancestor-only marker removal/recovery in both modes.
`/tmp/virtual-defaults-portable-03` passes PHP, legacy native and both v0.2 modes;
it also checks the PHP attribute target, ten structural rejection/collision cases
and warm conversion reuse. `/tmp/virtual-defaults-regression-01` passes all 32
existing abstract-dispatch generated programs. Earlier portable fixture used a
reserved C++ local name; the final fixture uses `fallback`.

No C++ emitter change, generic source-method model or implicit virtualization was
introduced. Shared preparation feeds the existing dispatch and prelowering paths.
