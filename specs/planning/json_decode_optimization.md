# Typed JSON decode optimization plan

Doc Status: planning

## First goal

Resolve the UTF-8 prevalidation tradeoff on dev002. Deliver either a verified production improvement or a reproducible decision to retain the committed decoder. Both outcomes finish the goal. Start with the closing-quote decoder at `56800977e`; check the actual production diff before starting in case the branch has advanced.

The [UTF-8 study](benchmarks/json_utf8_validation/README.md#dense-unicode-follow-up) has two useful controls. The original eight-byte check delivered about 2.35× production throughput on a 4,096-byte ASCII scalar but slowed dense literal Unicode. The width-only candidate gained 42–85% on full dense-Unicode decode and reached about 1.92× production on the ASCII scalar. Moving that loop into a separate function left the ASCII gap. The dense-Unicode follow-up and its raw measurements are committed at `a704cc6c4`. Earlier SSE2 scanner patches remain experimental.

## Work the goal can do autonomously

1. **Establish the comparison.** Record the production revision, candidate diff, build flags and machine. Reuse the existing generated drivers and raw fixtures. Run production, the original ASCII candidate and the width-only candidate side by side on identical inputs. Use committed production for all shipping decisions.
2. **Locate the cost.** Measure cycles per document for validation through the real reader constructor alongside isolated validation and complete typed decode. Start with the 4,096-byte ASCII scalar and dense literal Unicode. Inspect optimized code or sampled profiles where available. Determine whether the missing throughput appears during construction or later in decode before changing another loop.
3. **Test one explanation at a time.** Give each candidate a prediction for a named fixture and a control where it should have little effect. Run focused checks locally. Build and benchmark in isolation on dev002, pin one CPU, shuffle binary order, and keep raw samples. If the prediction fails, record it and use the evidence to select the next experiment.
4. **Qualify the best candidate.** Repeat full typed timings with a fresh shuffle. Compare short and long ASCII, sparse and dense Unicode, mixed widths, ASCII/Unicode transitions, escaped Unicode, string vectors and numeric arrays. Compare owned decoded values against an independent oracle. Compare diagnostics byte for byte, including invalid offsets, paths and UTF-8 error precedence. Exercise the portable path and guarded input boundaries. Run the default suite on dev002 with `ctest --preset default -j24`.
5. **Land or close.** Commit the study separately. For a qualifying winner, integrate only the winning change, rebuild from production source, remeasure against the pinned baseline, verify diagnostics and commit the integration. Otherwise keep the candidates as study patches and record why production wins. Leave unrelated work alone. Publishing or pushing is a separate action.

## Proposed decision rules and finish line

- Correctness is mandatory. A value, ownership, error-precedence or diagnostic mismatch disqualifies that version.
- Use end-to-end throughput for the decision. Use validation-only throughput to explain it. Compare per-fixture medians and run-to-run spread; a large average gain cannot hide a repeatable loss in a protected workload.
- For this first goal, aim to retain the width-only candidate's dense gains and recover the original eight-byte candidate's long-ASCII throughput within measured variability. Keep short inputs and numeric vectors at least at production performance within variability. If the host is noisy, repeat the disputed fixtures with longer timed intervals before making the decision.
- Bound exploration to diagnosis and up to three new, materially different candidates for this goal. Historical failed placements do not need another run without new evidence. Close with the best verified result if the tradeoff persists. Record any proposed change to the acceptance criteria for the user to decide.
- The final report identifies commits, exact baseline, representative throughput with ranges, regressions, correctness evidence, raw measurements and any remaining open question.

## Subsequent goals

Use the same loop for one measured bottleneck at a time. After prevalidation, profile the committed decoder on the existing [typed records and integer vectors](benchmarks/typed_json_decode/README.md), [record arrays](benchmarks/json_record_arrays/README.md) and [string vectors](benchmarks/json_string_batches/README.md). Rank hotspots by their share of complete decode time and the workloads affected. Choose the next bounded goal from those measurements. Rebaseline after each integration so gains are measured on the decoder users actually run.
