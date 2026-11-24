# Bounded match expressions (v0.2)

Doc Status: normative

The cpp, php and js flavors share this Simple C++ expression syntax:

```text
match (subject) {
    label1, label2 => result,
    default => fallback,
}
```

Evaluate the subject exactly once. Select the arm with the equal label, or the
required default when no label matches. Evaluate only that arm's result. A subject
failure evaluates no arm; a selected result failure propagates normally. Default
may appear anywhere. A trailing comma is optional. A constant-label arm may list several comma-separated labels. Each selects the same result. Duplicate checking spans all labels, including labels in the same arm. The default stands alone.

Constant-label admission belongs to the subject type. Published integers use exact
integer values; backed enums use cases of the same nominal enum. Byte strings use
exact bytes, including length and embedded NULs. Integer and string labels accept
literals or named constants of exactly the subject type. Locals, calls and other
computed labels are rejected even when their value can be inferred. Duplicate
values (including aliases), duplicate defaults and missing defaults are errors.

With a one-layer nullable or result subject, the payload type supplies the label
domain for both constant-label match and switch. It must itself support constant
dispatch. Evaluate the subject once and test for a payload before extraction.
Present zero and present empty bytes participate in ordinary label comparison.
An absent nullable or a stored result error selects default directly. In switch,
execution then follows ordinary fallthrough, or continues after the switch if
default is omitted. An exception raised while evaluating the subject propagates.
Explicit alternate-state labels and the boolean-sentinel carrier families remain deferred.

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
arm has one boolean condition. Evaluate conditions once each, in written order,
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

Deferred: general boolean subjects, float subjects,
computed integer/enum label expressions, exhaustiveness inference, omitted
defaults, implicit result unification and reference results. This syntax is a
Simple C++ extension in cpp/js, not a claim about native C++/ECMAScript syntax.

## Statement selection with switch

All three flavors accept the following statement in ordinary executable bodies:

```php
switch ($extension) {
    case "cpp":
    case "hpp":
        echo "C++";
        break;
    default:
        echo "other";
}
```

Evaluate the subject exactly once, even in an empty switch. Case labels use the
constant-dispatch capability and exact label rules above. Duplicate labels and
multiple defaults are errors. Literal `true` has no special condition form here.
A failure evaluating the subject propagates before any clause runs.

Enter at the matching case, or at the optional default wherever it appears.
A miss without a default exits the switch. After entry, ordinary completion runs
the next clause in source order without comparing its label. Each clause has its
own lexical scope. Its automatic locals are destroyed before executing the next
clause, including on fallthrough. Declare values outside the switch to share them
between clauses.

Unlabelled `break` exits the nearest enclosing loop or switch. Unlabelled
`continue` advances the nearest enclosing loop, even across a switch. Transfers
that leave a protected region run its finalizer before reaching their destination.
Existing restrictions on exiting a finalizer and the bounded set of statements
in resumable iterator bodies still apply.
