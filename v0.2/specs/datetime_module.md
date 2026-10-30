# Bounded datetime module

Doc Status: normative
Scope: concrete native v0.2 runtime and PHP API publication. Source compiler
integration and PHP compatibility wrappers are deferred.
Top-level `specs/builtins/datetime/` contracts remain the semantic authority.

## Publication and ownership

Optional `datetime` metadata registers the reviewed `native_datetime` provider and
operation set. A native publication selects a header and nonempty operation list:

```json
{"datetime":{"header":"scpp/datetime.hpp","operations":["now_unix_seconds","parse_iso_utc"]}}
```

The publication must explicitly include all required concrete types and carriers.
Preparation rejects duplicates, unregistered operations, conflicting headers and
missing dependencies. No unrequested functions or implicit type dependencies are
synthesized. `prepared.json` records signatures and the module's build dependencies;
`inventory.json` records generated artifacts. Existing publications without datetime
retain their exact generated artifacts.

`tools/api_generator/php/families.php` owns preparation; `php/render_modules.php` renders
concrete declarations and adapters. `runtime/src/datetime/` owns reusable native
behavior and checked temporal conversions. Generated `src/datetime.cpp` bridges
native values to the existing concrete runtime types. Public headers contain no
implementation templates or standard-library types. No unqualified short names are
added for module functions.

## Concrete interface

All functions reside in `scpp::dt`. Inputs use explicit runtime types; input references
are synchronous readonly borrows and are never retained. Results own their values.
This native API guarantee does not itself add source metadata borrowing annotations.

| Function | Arguments | Result |
| --- | --- | --- |
| now_unix_seconds | none | int64 |
| now_unix_millis | none | int64 |
| monotonic_millis | none | int64 |
| monotonic_micros | none | uint64 |
| monotonic_nanos | none | uint64 |
| sleep_millis | const int64& | void |
| format_iso_utc | const int64& | string |
| parse_iso_utc | const string& | result_int64 |
| format_local | const string& format, const int64& seconds | string |
| format_local_now | const string& format | string |
| parse_common_local | const string& | result_int64 |

The native transport uses explicitly published long-long/unsigned-long-long integer
construction. Format/parse require string; parse also requires result_int64 and its
existing error_t dependency. No new value family is introduced.

## Clock and sleep behavior

Wall-clock calls use system_clock and return Unix epoch seconds/milliseconds. They
may move backwards. Monotonic calls use steady_clock, whose epoch is unspecified;
precision is a host property and consecutive readings may be equal. Micro/nanosecond
results are unsigned, as required by the existing contracts.

Private checked duration conversion widens the count before multiplying by a unit
ratio, then divides with truncation toward zero and checks the destination range.
Out-of-range conversion throws a runtime error rather than overflowing or producing
an error sentinel. The supported Clang implementation uses private 128-bit arithmetic
for integral duration representations up to 64 bits; it does not expose that type.

Nonpositive sleeps return immediately. Positive sleeps use bounded chunks of at most
one day so even int64-sized millisecond requests do not overflow host nanosecond
conversion. Huge requests still mean huge waits, not a truncation or busy loop.
Scheduler precision and interruption behavior remain host-defined.

## Fixed UTC behavior

The parser accepts exactly 20 bytes `YYYY-MM-DDTHH:MM:SSZ`, with ASCII digits and years
0000–9999 in the proleptic Gregorian calendar. It validates month/day/leap rules and
hours/minutes/seconds. No leap seconds, offsets, fractions, lowercase z, embedded NUL,
trailing bytes or local-time forms are admitted. It calculates epoch seconds without
host time_t conversion; all admitted years fit int64.

Malformed input returns an error result with an owned message. A successful result
contains int64 seconds, including negative values. Allocation failure propagates;
no failure is silently changed into false or an empty value. Input storage is unchanged.

The formatter rejects timestamps outside years 0000–9999 before host conversion,
checks representability in time_t, and preserves checked gmtime_r/gmtime_s failure.
Unsupported host ranges throw runtime errors even inside the four-digit year domain.
It writes ASCII fields directly into a fixed 20-byte buffer, independent of locale.
UTC conversion ignores local UTC offsets; pre-epoch host support is not universal.
The resulting public string owns its bytes. Native parsing and fixed-buffer rendering allocate no C++ objects; host UTC conversion
may maintain its own internal state. Concrete string/result ownership can allocate.

## Host-local behavior

`format_local` supports Y/y/m/n/d/j/H/G/i/s/U. The donor token switch and backslash
escaping are preserved, including a literal trailing backslash and unknown bytes.
Embedded NUL is ordinary format data. Numeric rendering uses ASCII `to_chars`,
independent of locale. Year arithmetic widens before adding 1900; Y has a minimum
width of four, without the fixed UTC formatter's four-digit year limit. Checked host
localtime representability still applies, even to an empty or U-only format.
`format_local_now` samples wall-clock seconds once and uses the same formatter.
Returned strings own their bytes; internal formatting and public transport can allocate.

`parse_common_local` accepts YYYY-MM-DD, YYYY-MM-DD HH:MM:SS, YYYY-MM-DDTHH:MM:SS
and the UTC Z form. Shared fixed-width parsing validates calendar components;
malformed text, embedded NUL and trailing data return errors. Date-only forms use
midnight. The Z form delegates to the UTC parser independently of the local zone.

The host owns local interpretation with `tm_isdst=-1`. Gaps can normalize to a
different wall-clock time; overlaps use the host's choice, without a portable
first/second occurrence guarantee. No runtime call changes process-global TZ or
locale. External synchronization of changes to those settings remains the host's
responsibility, not a new runtime timezone service.

When mktime returns -1, checked local conversion of that value must match the
host-normalized calendar fields before accepting it. This distinguishes a valid -1
epoch from failure without relying solely on errno or rejecting DST normalization.
Host-to-int64 conversion is checked. Parse failures return result_int64 errors;
formatting failures throw. Allocation failures propagate and leave input bytes intact.

The private calendar parser, leap validation and UTC/local host-conversion helpers
have one owner. PHP operation plans carry an argument vector, so two-argument local
formatting follows the same declaration path as other module operations. Native
metadata explicitly records `local_dst: host_normalize_and_select`.

## Build composition

`cmake --preset default -DSCPP_ENABLE_DATETIME=ON` explicitly composes the default
core publication with uint64 and `runtime/metadata/datetime_publication.json`.
This is a bounded CMake profile, not a second semantic dependency resolver.
PHP validates the complete demand. CMake emits it in `runtime/generated/`.

Link `scpp_datetime` for the module; it depends on `scpp_runtime`. The core does not
depend on datetime. With the option OFF (default), no production datetime target is
created. Tests build an independent minimal publication fixture, including when the
production option is OFF. Both target owners use `cmake/Datetime.cmake`.
Regeneration removes disabled module artifacts from the shared publication.
`scpp_datetime_demo` demonstrates UTC parse/format when enabled. Source-only exports
must build without the legacy runtime tree, including with tests disabled.

## Deferred scope

The legacy source callable catalog remains unchanged. This slice does not claim
compiler support merely because native functions exist. Custom DST resolution, PHP time/date/strtotime/microtime adapters, named zones, locale-aware
formatters, calendar arithmetic and general module/plugin discovery are separate work.

Donor provenance and initial validation are recorded in `datetime_validation.md`;
local completion evidence is in `datetime_local_validation.md`.
