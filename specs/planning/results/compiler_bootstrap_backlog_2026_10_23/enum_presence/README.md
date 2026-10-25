# Approved enum presence
Doc Status: planning

2026-10-25; baseline fe304fda2 plus user-approved bounded enum presence support.

Reuse: Value_Probe_Preparation owns admission and observation facts. Type_Queries
identifies registered enum semantics; the existing always_true decision evaluates
plain enum operands once. Existing keyed lookup establishes existence first and
retains lazy keys and non-mutating reads. No runtime or backend adapter added.
Empty enum and enum conditions remain rejected. Normative probe/enum docs updated.

Initial native test passed ordinary output but concrete output incorrectly reported
an absent nullable enum entry as present. Generic runtime probing saw the concrete
carrier as an ordinary record. Preparation now retains the existing wrapper presence
decision for keyed leaves as well as plain operands, reusing concrete wrapper helpers.
This repairs existing wrapper state semantics; it does not add wrapper truthiness.

Final native proof passes ordinary and concrete output. Covers zero, signed8,
unsigned64 and unit enums; once-only plain expression evaluation; hash/vector missing
and present entries; lazy nested key effects; unchanged container counts; nullable,
structured Result, false and boolean sentinel enum leaves. Checks both absent/error
and present states. Negative probes reject plain/keyed empty enum and conditions.
Host value_probes, type_capabilities and incremental_preparation pass.

Initial bootstrap before the keyed-wrapper correction: 125.98 s / 614460 KiB RSS /
582.5 MiB heap. Final bootstrap: 121.15 s / 614692 KiB RSS (600.29 MiB) / 582.5 MiB
PHP heap. Categories 17 -> 16; value-probe admission diagnostic disappears. First
remaining message: S2S enum comparison requires the same enum type.

Stopped before fixing that blocker. Native compiler build/run and second-generation
proof remain incomplete; focused generated native programs are not a native rebuild.
