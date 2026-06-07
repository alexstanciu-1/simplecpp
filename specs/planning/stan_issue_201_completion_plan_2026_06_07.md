# STAN Issue 201 Completion Plan
Doc Status: planning

Date: 2026-06-07

## Purpose

This note records the remaining close criteria for GitHub issue #201, "STAN should enforce strict-profile design discipline before build/runtime."

This is a planning/audit note only. It does not define language semantics.

## Current Branch Evidence

Branch: `hotfix-stan-roadmap-201-return-paths`

Completed STAN strict-discipline slices:

- missing return-path diagnostics for typed non-void functions
- unchecked wrapper/result diagnostics at required typed locals
- unchecked wrapper/result diagnostics at required typed function and method arguments
- unchecked wrapper/result diagnostics at required typed returns
- unchecked wrapper/result diagnostics at required typed property writes
- dynamic/JSON-shaped value diagnostics at required typed locals
- dynamic coalesce fallback recognition for typed boundaries
- required property initialization diagnostics for missing defaults and incomplete constructor paths
- inherited default and inherited constructor property-initialization evidence
- strict diagnostic bucket/counter normalization for new strict-discipline findings

Relevant local validation already used for this branch:

- `php tests/tools/test_scpp_stan_strict_discipline.php`
- `php tests/tools/test_scpp_stan_diagnostics_session.php`
- `php tests/tools/test_scpp_strict_safety_edges.php`

## Item 5 Audit: Visibility And Structure Discipline

Issue #201 item 5 asks STAN to flag source-level structure violations before generated C++/build diagnostics where possible:

- private/protected property reads from outside the class
- private/protected method calls from outside the class
- static-vs-instance misuse
- unknown method/property access
- interface implementation mismatches

### Covered Or Substantially Covered

Private/protected property reads:

- implemented through property-read diagnostics and member visibility checks
- covered by `tests/tools/test_scpp_member_visibility.php`
- includes same-file, cross-file, inherited private property, and protected subclass positive cases

Private/protected method calls:

- implemented through call-site diagnostics and member visibility checks
- covered for cross-file private method calls in `tests/tools/test_scpp_member_visibility.php`
- protected subclass access is covered as a positive case

Static-vs-instance misuse:

- implemented in call-site diagnostics as `static_instance_misuse`
- covers static calls to non-static methods and instance calls to static methods
- build bucket currently treats this as a STAN error

Unknown method access:

- implemented in call-site diagnostics as `unresolved_method_call` / `unresolved_static_call`
- build bucket currently treats these as compile errors

Unknown property reads:

- implemented through property-read diagnostics as `unresolved_property_read` / `invalid_property_read`
- build bucket currently treats unresolved property reads as compile errors and invalid property reads as STAN errors

Unknown property writes:

- implemented through property assignment analysis as `unresolved_property_write`
- build bucket currently treats this as a compile error

Class constant and static property visibility:

- implemented through static property and class constant visibility diagnostics
- covered by `tests/tools/test_scpp_member_visibility.php`
- includes enum-case and unresolved-class-constant false-positive guards

Override conflict detection:

- implemented as `override_declaration` diagnostics
- current collector flags inherited method/property name conflicts
- this is related to item 5's structure-discipline intent, but it is not the same as interface contract conformance

### Remaining Gap

Interface implementation mismatch checking is not complete enough to treat item 5 as fully closed.

Current STAN extraction records implemented interfaces, and dependency resolution can resolve `implements` targets. However, the audited STAN diagnostic collector does not yet compare a concrete class against its interface method contract.

Recommended missing checks:

- implemented interface must exist and resolve unambiguously
- each interface method must be implemented by the concrete class or inherited from an ancestor
- implementing method visibility must be compatible with public interface methods
- parameter count should match
- required parameter names/types should match where STAN has source-level type data
- return type should match where STAN has source-level type data

Optional follow-up checks:

- interface inheritance if/when the source surface supports it reliably
- abstract class method implementation if/when abstract methods are represented in the extracted summary
- duplicate inherited interface method conflict reporting

## Proposed Close Criteria For #201

Issue #201 can be closed after either:

1. A final implementation slice adds focused interface implementation mismatch diagnostics and tests; or
2. The issue is explicitly scoped down to the completed STAN roadmap areas, and interface/contract checking is split into a follow-up GitHub issue.

Recommended path:

- add one final `#201` slice for interface implementation mismatch diagnostics
- keep the implementation focused on direct class implements clauses and direct interface methods
- open a separate follow-up only for deeper interface inheritance or abstract-class contract checks

## Suggested Final Test Additions

Add focused STAN tests for:

- class implements interface but omits one required method
- class implements interface with wrong return type
- class implements interface with wrong parameter count
- class implements interface correctly and reports no STAN warnings

Validation target:

- `php tests/tools/test_scpp_stan_strict_discipline.php`
- `php tests/tools/test_scpp_stan_diagnostics_session.php`

