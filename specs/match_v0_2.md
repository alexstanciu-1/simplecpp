# Bounded match expressions (v0.2)

Doc Status: normative

The cpp, php and js flavors share this Simple C++ expression syntax:

```text
match (subject) {
    label => result,
    default => fallback,
}
```

Evaluate the subject exactly once. Select the arm with the equal label, or the
required default when no label matches. Evaluate only that arm's result. A subject
failure evaluates no arm; a selected result failure propagates normally. Default
may appear anywhere. A trailing comma is optional. There is one label per arm.

The first slice admits published integer types and backed enums as subjects.
Labels are signed integer literals, named integer constants or enum cases, with
exactly the subject's canonical type. Labels are compile-time data, not executable
conditions. Duplicate values (including aliases), duplicate defaults and missing
defaults are errors. An enum label must belong to the subject enum. String-backed
enums are admitted by their nominal case identity; plain string subjects are not.

All value arms must produce the same canonical type. A default of the form
`default => throw expression` contributes no result type and requires at
least one non-default value arm. See [throwing fallbacks](exceptions_v0_2.md#throwing-fallbacks).
There is no implicit
common-type search or arm conversion. Ordinary conversion at the enclosing
initialization, argument or return boundary still applies. Selection produces a
value, not assignable storage; ordinary value-copy/shared-ownership rules apply.
Void and generator results are outside this slice. All arms are checked even when
the subject is statically known.

## Ordered boolean conditions

A literal `true` subject selects the condition form, `match (true)`. Each non-default
label is a boolean expression. Evaluate conditions once each, in written order,
until one returns true; evaluate only that result. Later conditions and results
are skipped. If none is true, evaluate default, irrespective of where it is written.
A failing condition or selected result propagates immediately. Evaluation within
an individual condition follows the ordinary expression contract.

Repeated conditions are allowed: each occurrence may have its own effects. There
is no duplicate-condition or reachability analysis. Every condition and result is
checked, including after a literal true condition. Require one default and identical
result types as above. Conditions must have canonical bool type; there is no
implicit truthiness conversion at the match boundary.

Only the literal `true` syntax selects this form. Boolean variables, calls, named
constants and `match(false)` remain outside this slice, even when their values are
known. No retained mode flag or constant-propagation rule is needed.

Deferred: general boolean subjects, plain string or float subjects,
computed integer/enum labels, multiple labels per arm, exhaustiveness inference, omitted
defaults, implicit result unification and reference results. This syntax is a
Simple C++ extension in cpp/js, not a claim about native C++/ECMAScript syntax.
