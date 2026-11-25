# Native value transport repair
Doc Status: planning

Candidate: 7f6e7b6ee plus this slice. Existing CPP_Wrapper_Transport provider_value
owns recursive physical payload adaptation. Explicit runtime casts now consume its
native view; split.2/split.3 bindings opt into existing native_payloads metadata.
No semantic or retained protocol changes; ordinary lowering and runtime casts stay
unchanged. Null/false/bool sentinel stringification keeps existing empty text.

Baseline focused ordinary cast/split passed; concrete failures reproduce both full
compiler native errors. Six final tasks pass, including lifted result/string
carriers, nullable integer control, once-only evaluation, split limits/order/empty
values. A preliminary carrier expectation was corrected to the existing runtime
contract (true sentinel stringifies to empty text, not "1"); no production change.

Commands:
- `python3 compiler/my-try/tests/native_value_transport.py --results /tmp/native-value-transport-verified`
- `python3 compiler/my-try/tools/bootstrap_probe.py --results /tmp/compiler-bootstrap-native-transport-01`

Full PHP-hosted publication passes: 2:03.79 wall, 796076 KiB RSS, 757.14 MiB PHP heap.
Focused tests overlapped its early stages; this is an observed attempt, not an
isolated performance comparison. JSON's fatal field contains the last nonfatal
first-publication lstat warning; failure is null and exit is zero.
Native compiler build is a separate measurement; this result alone does not prove
an executable compiler or second-generation bootstrap.
