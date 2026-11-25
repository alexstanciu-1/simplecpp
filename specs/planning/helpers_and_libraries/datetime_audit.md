# Datetime source integration audit

Doc Status: planning

Audited at `7e8ee388e` against release `d2f63e37e` and my-try `624ab8a2d`.
All 11 general operations already have native implementations. Generating the full
datetime runtime and compiler publications in scratch produced all 11 C++
declarations and zero general datetime source bindings. A fresh C++-flavor project
selecting datetime reported `Unknown function: dt_parse_iso_utc`.

## General family and proposed source signatures

| Source call | Native operation in scpp::dt | Source result | Probe disposition |
| --- | --- | --- | --- |
| dt_now() | now_unix_seconds | int | Direct wall-clock call |
| dt_now_ms() | now_unix_millis | int | Direct wall-clock call |
| dt_monotonic_ms() | monotonic_millis | int | Direct steady-clock call |
| dt_monotonic_us() | monotonic_micros | uint64 | Needs source scalar |
| dt_monotonic_ns() | monotonic_nanos | uint64 | Needs source scalar |
| dt_sleep_ms(int millis) | sleep_millis | void | Exact first integer operand, synchronous blocking sleep |
| dt_format_iso_utc(int timestamp) | format_iso_utc | string | Exact first integer operand |
| dt_parse_iso_utc(string value) | parse_iso_utc | result<int> | Direct, checked epoch zero and error |
| dt_format(string format, int timestamp) | format_local | string | Exact second integer operand |
| dt_format_now(string format) | format_local_now | string | Direct, host-local time |
| dt_parse(string value) | parse_common_local | result<int> | Direct, host-local or UTC Z input |

Native inputs borrow for the duration of the call. String and result outputs own
their values. The inventory's shallow `dt_format(int, string)` signature disagrees
with the release builtin spec, its PHP wrapper and executable fixture, the my-try
catalog and the current native declaration. Use format first. Wall clocks can
move backwards and steady-clock samples can be equal. The local parser inherits
host DST behavior. A failed parse returns an error and a format failure throws.
[Native contract](../../datetime_module.md) · [Release format contract](../../../v0.1/specs/builtins/datetime/dt_format.md) · [My-try signatures](../../../v0.1/specs/planning/runtime_surface_audit/catalog_datetime_1.tsv)

## Scratch proofs

Generated profiles with the base publication, an additional uint64 native type,
and either the full datetime request or only parse_iso_utc. Used emit-runtime
and emit-compiler with build/clang/native-layout/{facts,context}.json. Copied
the compiler to scratch and added temporary general runtime_function entries
to its generated registry. This exercises real source lookup and lowering.
Production metadata-to-binding generation remains to be implemented.

1. With one binding, C++ flavor resolved the general name by default. PHP needed
   general_api. Without it PHP reported an unknown function. With visibility
   enabled and the module omitted, the compiler required runtime-datetime.
   The reduced registry rejected dt_now. The generated header declared only
   parse_iso_utc and included carriers, core scalars, numeric scalars and string
   type. Both source units called ::scpp::dt::parse_iso_utc directly. Both
   Clang/libc++ executables distinguished a present zero from malformed input.
   Their manifests listed the datetime archive before the core archive. The
   probe linked the matching generated datetime bridge and private provider
   directly with the local core archive.
2. A PHP source caller exercised all nine operations with existing source types.
   The ordinary unary call path passed literal zero as 0LL even with
   exact_arguments enabled. Clang rejected sleep_millis and format_iso_utc
   because the native boundary deletes the long long overload on this target.
   Selecting the existing overloaded_first path for these two bindings emitted
   static_cast<scpp::int64>(0L). The two-argument formatter used the existing
   exact_arguments path for its timestamp. The resulting executable passed under
   TZ=UTC0. No datetime-specific compiler lowering or wrapper was needed.
3. Native unsigned clocks already return scpp::uint64. Publishing uint64 in
   the build profile emitted its C++ type, but the compiler emitted no uint64
   type definition. A source identity function using uint64 was rejected at
   its return annotation. The compiler scalar list in runtime metadata and
   the bootstrap's generated-type loading both need review before publishing
   these two bindings. Preserve the full unsigned range.
4. Six existing native tests passed: datetime_consumer, datetime_semantics,
   datetime_timezone, datetime_local_utc, datetime_local_dst and
   datetime_local_offset. They cover malformed dates, the epoch, clock return
   types and host-local behavior independently of the scratch registry.

## Proposed implementation goal

Publish the 11 general names from the selected operations, guarding each with
runtime-datetime and the established general-API visibility. Reuse the direct
call and overload selection shown above. Include the two unsigned clocks once
the compiler can represent, return and forward their exact type. Prove unsigned
identity through a source caller and native signature check. Separate the scalar
work for agreement if it requires broader arithmetic or conversion policy.
Verify incremental manifest removal and recovery, selection of a single
operation, actual archive linkage and both typed and literal integer arguments.

The native publisher currently groups selected declarations into one header.
A parse-only publication suffices for the reduced-profile proof. A full
publication also exposes unrelated datetime declarations to a clock-only
caller. For implementation, compare narrow per-operation declarations with
the filesystem precedent and check a clock-only caller's include closure.

The separate PHP compatibility review should account for time(), both date()
arities, and strtotime() with its false-on-error adaptation. The donor core
microtime() and microtime(bool) expose text and fractional seconds and have a
separate contract. The five release JSS dt.* aliases are donor evidence for the
later flavor review. General dt_sleep_ms is distinct from async_sleep_ms.
[Donor surface index](donor_surfaces.tsv) · [PHP donor wrappers](../../../v0.1/runtime/include/lang/php/php_datetime.hpp) · [Microtime contract](../../../v0.1/specs/builtins/datetime/microtime.md)

## Stage 1 outcome

All 11 general bindings now come from publication metadata. The compiler honors
exact arguments on its unary path, including literal zero. Each operation owns
a narrow declaration header. The datetime module activates numeric transport,
and the source-to-native fixture passes UINT64_MAX through a source-defined
identity function. [Active contract](../../datetime_module.md) ·
[Executable proof](../../../compiler/tests/datetime_native.py)
