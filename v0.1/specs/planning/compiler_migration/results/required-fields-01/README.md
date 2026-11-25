# Required field conversion evidence
Doc Status: derived

Target: 9b4b33f35f053b487e018c94d6a4a7888d77c64a, clean checkout at
/tmp/scpp-json-240-probe. Strict profile; clang++-18. Target checks stayed enabled.

Two native command attempts, first pass on attempt 2. The first stopped in STAN
before C++ generation: it could not prove a separate populate call initialized
private/protected fields read by another method. One fixture adaptation moved
those assignments into the constructor. No converter correction, target edit,
generated-output patch or check bypass was used to resolve that diagnostic.
This establishes the documented construction pattern, not general interprocedural
initialization analysis. No verification build beyond the passing native build.

First passing PHP checkpoint used php -r
'foreach (array_slice($argv,1) as $path) { require $path; }', with the portability
bootstrap, model.php and main.php in order. First-checkpoint SHA-256 values:

- model.php: `dee59d44831d894028989bd18b9d594b321e981ace133e2ac26d9ff1087cbb95`
- main.php: `4ff0795662a178d5d04c7e7ba68a49fb5265ebd7b14a91a30808a6b55cc7f658`

Command elapsed times (seconds):

| Attempt | PHP/check/conversion/setup/rejections | Native command | Outcome |
| --- | ---: | ---: | --- |
| 1 | 2.962 | 0.917 | STAN rejection |
| 2 | 1.593 | 19.474 | passed |

Authoring wall time was not separately measured. The original PHP checkpoint is
retained above; the second runner directory contains revised fixture hashes.
Expected and observed PHP/native output:

```text
12:ready:source:ok:3
phase:same:7:0
retained:replaced:2:0
published
float
```

Reproduce with tests/portability/required_fields.py --results FRESH
--target-checkout /tmp/scpp-json-240-probe. The runner saves commands, outputs,
source/generated fixtures, durations and checkpoints. Additional regression passes:
portability foundation, read-only checker and static-field PHP/conversion proof.
No compiler production files were added to the ready set. Layout/ownership changes
are documented direction only; this syntax slice preserves ordinary class identity.
