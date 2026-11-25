# Fresh accounting acceptance through the public option
Doc Status: supporting

Production revision: `6bfbfb40bb65305fec07be8254c63f18c2d1ea66`, with this slice's
acceptance harness and recorder classification update. No production compiler or
accounting source changes were required. Source hashes were checked unchanged.

Both modes use the public CLI, normal source publication and native builder, fresh
output/cache directories, O0, debug disabled and no PCH. Prelowered publication uses
only `--prelower-templates`; ordinary publication omits it. The published policies
are respectively `forbidden` and `allowed`.

| Result | Ordinary | Prelowered |
|---|---:|---:|
| Accounting checks passed / failed | 998 / 0 | 998 / 0 |
| Exit status | 0 | 0 |
| Cold linked objects | 20 | 22 |
| Warm compiled / linked | 0 / 0 | 0 / 0 |
| Warm reused objects | 20 | 22 |
| PCH built | 0 | 0 |

Complete output is byte-identical, on both cold and warm execution:

```text
accounting workload: invoice/payment/trial-balance checks passed
CHECKS passed=998 failed=0
ACCOUNTING_WORKLOAD_PASS
```

The native builder performed 16 strict application token validations. The independent
manifest-driven closure audit covered **all 16 application units, including entry.cpp**.
Every unit and its transitive preprocessed headers had zero template declarations;
Clang traces had zero `InstantiateClass` and `InstantiateFunction` events.

Two middleware implementation units and three layout probes were explicitly excluded
from the application audit. Runtime provider objects also remain outside that boundary.
Runtime/middleware templates are expected; there was no silent fallback, manual output
patching or reuse of a historical accounting publication.

## Reproduce

```sh
python3 compiler/my-try/tests/prelower_accounting_acceptance.py --results FRESH_DIRECTORY
```

The harness records actual compiler commands automatically and invokes the existing
closure audit, requiring exact manifest coverage. It compares outputs, checks warm
reuse and records revision, toolchain, source hashes and options.

[Summary](summary.json) includes both builds and exact output;
[coverage](coverage.json) lists application units and deliberate exclusions;
[closure](closure.json) records each unit's counts and header origins.
Full local logs and recorded commands:
`/tmp/prelower-public-accounting-20261020`.

This is correctness and input-closure acceptance, not a timing comparison. Builds
were concurrently scheduled through the shared runner. No O2, native compiler rebuild,
full runtime coverage claim or new allocation/copy-counter claim is made.
