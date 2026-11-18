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

Every arm must produce a value with the same canonical type; there is no implicit
common-type search or arm conversion. Ordinary conversion at the enclosing
initialization, argument or return boundary still applies. Selection produces a
value, not assignable storage; ordinary value-copy/shared-ownership rules apply.
Void and generator results are outside this slice. All arms are checked even when
the subject is statically known.

Deferred: boolean/`match(true)` conditions, plain string or float subjects,
computed labels, multiple labels per arm, exhaustiveness inference, omitted
defaults, implicit result unification and reference results. This syntax is a
Simple C++ extension in cpp/js, not a claim about native C++/ECMAScript syntax.
