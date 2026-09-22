# Type lifetime checkpoint
Doc Status: derived

Four files migrate lifecycle ordering/roles, tagged imported and composed operations,
shared lifetime policies and provider codecs. Runtime metadata and source member plans
remain distinct; permissions are not inferred from representation or implementation
presence. The original composition and binding algorithms are preserved with explicit
records, typed vectors/maps and copied policy/order values.

154 independent expected PHP/native outcomes pass. Retained original lifecycle and
contract code additionally executes in a host oracle for ordering/source/destination
rules and forbidden expiring-copy fallback. Four host serialization assertions prove
policy/list/order-view independence. Those host checks were added during consolidation
and passed in the cumulative fast run; they are not additional native outcomes.

```sh
python3 compiler/tests/type_lifetimes/run.py --results /tmp/FRESH-lifetimes --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/FRESH-lifetimes-fast
```

Native target verified clean before/after:
9b4b33f35f053b487e018c94d6a4a7888d77c64a, clang++-18, strict profile. Cumulative
PHP/tool validation passes with 48 registered files; the fast run does not itself
request native proof. The component native closure plus earlier unchanged native
checkpoints supplies cumulative evidence. Production sources are identical at first
PHP-ready, native-ready and consolidation.

Authoring to first PHP-ready: **125.614 s (2m06s)**. PHP-ready to native-ready:
**102.580 s (1m43s)**. Native build: **22.221 s**. One build and one native behavior
run, zero checker/converter/native corrective cycles. Timings include observed waits
and overlapping oracle/regression/documentation work, not isolated typing costs.
Initial inspection and final commit are excluded. Raw milestones, source hashes and
cycle counts are preserved separately.

Full lifetime analysis, exports, ABI preparation and execution of described operations
remain later work. Next: the scalar language catalog's authoritative named definitions
and entry return binding. No converter/framework/target or src-runtime-preparation code
was changed.
