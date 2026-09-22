# Type representation checkpoint
Doc Status: derived

Three source files migrate all nine representation shapes, callable passing/result
vocabulary and context/lineage/member records. Factory-owned private state replaces
variant PHP payload objects without adopting unions or prematurely narrowing integers.
Signedness, lifetime/operation capabilities, named definitions and catalog ingestion
remain separate dependencies. Next: lifecycle operations/contracts and named definitions.

98 independent expected PHP/native outcomes passed. The retained constructors run
directly as an additional host oracle for floating widths, default passing, borrow
classification and result strings. Five host assertions verify observation purity and
large alignment boundaries; large alignment cases are host-only, not native coverage.
Complete old type-storage/cache/session tests are not claimed by this representation
foundation. Source/test/reference hashes are in provenance.json.

```sh
python3 compiler/tests/type_representations/run.py --results /tmp/FRESH-representations --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/FRESH-representations-fast
```

The native checkout was verified clean before/after at exact commit
9b4b33f35f053b487e018c94d6a4a7888d77c64a, using clang++-18 and strict mode.
Cumulative PHP/tool validation passes with 44 registered source files; that fast
command does not itself request native proof. This component's native closure plus
prior unchanged stage proofs supplies cumulative native evidence.

Authoring to the first PHP behavior pass: **106.109 s (1m46s)**. First PHP-ready to
native-ready: **227.882 s (3m48s)**, including one checker correction and one STAN
correction. Two native build commands were attempted: the first stopped at STAN
(0.886 s), and the second compiled successfully (22.069 s). One C++ compilation and
one native behavior run occurred. No converter/framework/target code was changed.

The checker rejected bitwise AND. An integer-only power-of-two loop now rejects
before doubling could overflow; positivity precedes modulo. STAN then reported
missing returns for codec methods ending in throws. Explicit success branches plus
a common return preserve mappings/errors, without dummy unreachable returns or STAN
suppression. Logs for both failures and the successful native run are saved.

Timings include waits and overlapping documentation/regression work, exclude initial
inspection and final commit, and are not isolated active typing measurements. The
first PHP hashes/checkpoint are preserved across stabilization fixes. Raw milestones
and interval details are in timing.json; cycle distinctions are in cycles.json.
