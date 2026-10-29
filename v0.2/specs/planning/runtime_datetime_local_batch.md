# Local datetime completion batch

> Status update (2026-10-29): Rust implementation and toolchain retired.
> This note is historical context; current owners are the PHP compiler and PHP API
> generator. See [compiler status](../../compiler/README.md).

Doc Status: planning
Date: 2026-10-29
Status: L0–L5 complete and validated; host DST delegation retained.
Reviewed repository revision: 4db60739d.

## Completed slice

Complete the three remaining native datetime functions before returning to the
packed-storage foundation for mixed/dynamic. This reuses the existing module target,
Rust publication owner, int64/string/result_int64 and private calendar helpers.
No new value family or general timezone framework is needed.

| Function | Inputs | Result |
| --- | --- | --- |
| format_local | const string& format, const int64& seconds | string |
| format_local_now | const string& format | string |
| parse_common_local | const string& text | result_int64 |

Keep native explicit arguments and owned results. The source compiler and PHP wrappers
remain deferred; eleven native module functions would not mean full PHP datetime parity.

## Contract and donor review

Authority: repository-root `specs/builtins/datetime/dt_format.md`, `dt_format_now.md`,
`dt_parse.md`, `date.md` and `strtotime.md`. Donor:
`runtime/include/modules/datetime/datetime.cpp`.

Formatting supports Y/y/m/n/d/j/H/G/i/s/U, literal unknown characters and backslash
escaping. Preserve a trailing backslash as a literal, matching the donor. Format
strings are bytes, including embedded NUL. Reuse the token switch and replace stream
number formatting with locale-independent ASCII output and checked/widened year
arithmetic. Unlike fixed UTC formatting, Y is minimum-width four digits, not an
implicit restriction to years 0000–9999; host local-time representability governs.

Parsing admits YYYY-MM-DD, YYYY-MM-DD HH:MM:SS, YYYY-MM-DDTHH:MM:SS and the existing
20-byte UTC Z form. Validate fixed syntax/calendar components before host conversion;
reject malformed/NUL/trailing input. Local input years have four digits; actual
representability is host-dependent. UTC Z input delegates to the completed UTC parser.

## Approved policy: retain host DST resolution

The current contracts explicitly interpret non-UTC input through the host C/C++
runtime. Keep tm_isdst=-1 and let the host resolve gaps/overlaps. Do not introduce an
independent timezone database, fold flag, earliest/latest selection or gap-rejection
rule. Document that a normalized local input need not round-trip to the same wall
clock fields and that the selected occurrence of an overlap is not portable.

The user approved this explicit clarification of existing host delegation. Rejecting
DST gaps would be a separate contract change.

The donor's unconditional mktime result == -1 failure test must be repaired. When
that value is returned, use checked local conversion and comparison with the host's
normalized calendar fields to distinguish a valid -1 timestamp from failure. Do not
rely solely on errno or confuse a normalized DST gap with conversion failure. Preserve
checked time_t/int64 boundaries, error results for failed parsing and exceptions for
failed formatting. Do not mutate process-global TZ or locale inside runtime calls.

## Focused host evidence

An isolated native probe on the current Linux host used std::mktime with tm_isdst=-1
and localtime_r. Separate processes selected TZ=UTC0 and the explicit POSIX rule
TZ=EST5EDT,M3.2.0/2,M11.1.0/2; no system timezone database was required.

- UTC0: 1969-12-31 23:59:59 returned -1 and converted back to the same fields.
  The donor rejects this valid value.
- EST5EDT rule: 2024-03-10 02:30:00 normalized to 03:30:00 (DST), epoch 1710055800.
- EST5EDT rule: 2024-11-03 01:30:00 selected standard time, epoch 1730615400.
  This is an observation on this host, not a portable choice guarantee.

The probe was temporary audit evidence, not a committed regression test. Implementation
must add deterministic epoch/format cases and isolated host-policy tests that do not
assert one portable overlap choice or platform support for all pre-epoch values.

## Completed backlog

| ID | Work | Acceptance |
| --- | --- | --- |
| L0 | Confirm host DST delegation and document edge contracts | No hidden gap/overlap policy; valid -1 distinguished |
| L1 | Share calendar parsing and checked host conversion within the datetime owner | UTC behavior unchanged; no duplicated calendar validator |
| L2 | Adapt local formatter and current-time variant | Token/escape/NUL/locale behavior, checked host/year conversion |
| L3 | Adapt common parser | Four forms, malformed data, errors, valid -1 and host DST normalization |
| L4 | Extend Rust preparation/emission and native module selection | Selected-operation/dependency proofs; minimal consumers |
| L5 | Validate, document and commit | Release/UBSan, focused lifetime/failure tests, timezone process isolation, standalone export and costs |

L0–L5 were implemented as one goal. See [validation evidence](../datetime_local_validation.md).
The successful pass is committed under the standing user preference; pushing remains
separately requested. Traversal RT-DEBT-01, boxed composition, mixed/dynamic, source
compiler integration and PHP compatibility remain outside this batch.
