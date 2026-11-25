# Cursor diagnostic completion
Doc Status: supporting

Parent 1dca23c5e plus diagnostic context/semantic type rendering. All 18 cursor tasks
pass: native ordinary/concrete original/renamed providers, interface/exact/base
reads, retained edit/recovery/reset, malformed relations and invalid records, plus
missing compatible construction metadata. Failure messages name the owner/input
applications or inferred source/target relation as appropriate. The exact-only
factory control does not falsely advertise an unconstrained two-variable provider.
Portable parity is saved in cursor_integration/portable_summary.json.

An initial converter checkpoint rejected the empty-string method parameter default;
source now uses supported ?string = null with empty coalescing. The retained test was
rerun after that source-only correction. No converter or language change was needed.

Bootstrap 75.90 seconds wall, 343416 KiB peak RSS, 314 MiB PHP heap. Conversion
succeeds; preparation first fails syntax_node initialization, with unchanged diagnostic
set including read_next completion. No full compiler build or CH15 closure claimed.
The bounded CURSOR-01 scope is complete; broader cursor conversions remain excluded.
