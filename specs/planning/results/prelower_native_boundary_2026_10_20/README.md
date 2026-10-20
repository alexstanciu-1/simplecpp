# Native template-boundary enforcement
Doc Status: supporting

Base: `d3287de5`, plus this slice. The existing compiler coordinator publishes an
explicit application-template policy. The native builder rejects incompatible PCH,
checks active Clang tokens before accepting application objects, and separates
validated object identities from ordinary compilation through its existing cache.
Fresh dependency scans invalidate validation when transitive provider headers change.
Middleware/provider objects and layout probes may still compile templates.

Proof commands:

```sh
python3 compiler/my-try/tests/prelower_native_boundary.py --results FRESH
python3 compiler/my-try/tests/prelower_null_output.py --results FRESH
python3 compiler/my-try/tests/prelower_contracts.py --results FRESH
```

The native boundary fixture covers nested provider headers, macro-expanded template
declarations, comments/string literals/inactive branches, explicit PCH rejection,
ordinary PCH success/warm reuse, header mutation/recovery, ordinary-cache separation,
unknown translation-unit locations and missing entry rejection. Every rejected native
attempt leaves readiness pending. Restoring clean inputs reuses validated objects.

The real concrete null-output program preserves `[p1:p2]2`, compiles without PCH,
and reuses all objects/linking on a warm build. Its independent manifest-driven closure
audit covers both application units with zero template declarations/instantiations.
The existing 21 rejection/recovery cases and 10 closure-coverage tests also pass.

Local logs: `/tmp/prelower-native-boundary-complete-20261020`,
`/tmp/prelower-native-null-20261020`, `/tmp/prelower-native-contracts-20261020`.
Shared runner used CLI fallback because FPM sockets were unavailable.

This is O0 generated-program validation, not a native compiler rebuild or a new
accounting/performance benchmark. The token check is a conservative no-template-keyword
input gate, not a general C++ semantic analyzer. The independent closure/instantiation
regression remains. Public flag consolidation and broader operation coverage remain
pending. Source-publication readiness is distinct from successful native readiness;
older publications must be regenerated to supply the new policy.
