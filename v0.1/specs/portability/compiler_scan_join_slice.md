# Source-scan reconciliation
Doc Status: supporting

`read_sources\Source_Scan_Join` now accepts an explicitly typed result vector and
returns the next directory-task vector. The user approved making `compile\Join`
a marker: concrete owners retain their own input/output contracts. The host-only
cross-stage inventory invokes concrete methods reflectively; production callers
continue to use concrete owners.

Source reconciliation preserves task-order acceptance despite reversed completion,
exact originating-task checks, existing IDs, sequential new IDs and next-batch order.
`source_file::copy()` owns explicit shallow copying of the record, including shared
buffer identity. Acknowledgment reuses this same copy operation. Changed observations
clear their copied buffer; unchanged observations preserve it. Path spelling uses
the existing pure Source_Path_Syntax owner instead of its filesystem facade.

The converter now accepts required annotated container parameters on concrete
class/trait methods, nonpublic promoted fields and explicitly fully qualified
uppercase constant references. It does not infer symbols or constant types.
Interface container signatures, container parameter defaults and reference parameters
remain rejected. Locals needed after branches must be initialized in their enclosing
block; PHP's function-wide variable scope is not a portable assumption.

Cumulative PHP/native expected-result proofs cover reversed results, task provenance,
duplicate/incomplete batches, new/changed/unchanged rows, retained previous buffers,
next-directory tasks and ID exhaustion. The Source_Set frozen oracle still passes;
seventeen retained compiler fixtures pass, including cross-stage join contracts.
Selected native target: `2f0d667f38a35ff02ef77e813f409189cba2d032`.
Evidence: `specs/planning/compiler_migration/results/scan-join-01/summary.json`.

Nineteen production files are now in the ready manifest. This is a component count,
not overall compiler coverage. Full source discovery, file reading and subsequent
stage algorithms remain migration work. This slice adds no compiler functionality
and does not introduce a generic polymorphic batch/result model.
