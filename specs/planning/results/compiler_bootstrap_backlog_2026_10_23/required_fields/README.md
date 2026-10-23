# Required field initialization checkpoint
Doc Status: supporting

Reuse: Constructor_Preparation already validates set: inputs and nondefault fields;
synthesized data inputs, inherited constructors and record defaultability share
Object_Construction_Preparation::supports_default. Required nominal handles now have
no source default. Plain/static required fields without a construction route reject.
Explicit defaults, set: inputs and synthesized required inputs initialize directly.
Struct defaults remain supported; a struct lacking a valid default needs direct
initialization. Local predeclarations remain separate LOCAL-01 work.

The focused proof exposed a pre-existing data-construction prelowering gap: allocation
emitted raw shared<T> in application code. The backend now transports settled record
construction using the existing concrete construction input/result protocol. It
preserves evaluated snapshots, source order and private constructor access keys.
No semantic lookup or new frontend construction contract is added in the backend.
The first transport retry exposed a null constructor-key query; fixed. The private
factory extension then exposed temporary key addressing; materialized the key in the
calling context, as ordinary constructor calls already do.

/tmp/required-fields-05 passes sixteen tasks: both-mode positive execution and seven
negative cases per mode. Positive execution covers required object identity through
set:, synthesized data, inherited and private construction, plus struct defaults and
a valid local predeclaration. /tmp/required-fields-portable-01 passes PHP, legacy
native and both v0.2 routes, including promoted object identity and failure ordering.
/tmp/required-fields-constructors-03 passes all 60 native constructor regressions.
Two historical constructor fixtures used empty required storage; their intended
nullable fields/inputs are now explicit. This is not a full-suite or native compiler
rebuild claim. Unsafe local reads and runtime API presence guarantees remain open.

Bootstrap /tmp/compiler-bootstrap-required-fields-01: 80.25 s wall time /
340,500 KiB RSS (332.5 MiB) / 310 MiB PHP heap. Preparation fails first on
"Property requires set: initialization before the constructor body: file".
Many required field declarations now expose the planned source/converter migration:
constructor-body assignments need accepted direct initialization; deliberately staged
fields need INIT-02's converter-owned protocol. Diagnostics disappearing after earlier
failure do not establish their resolution. No fatal/OOM, emission or native compiler
rebuild. Lower elapsed time and memory reflect earlier rejection, not faster bootstrap.

