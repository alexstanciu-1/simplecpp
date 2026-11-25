# Complex-edit/PCH experiment evidence
Doc Status: supporting

The [report](../native_edit_matrix_20261016.md) defines scope and timing interpretation. summary.json contains all 78 runs, exact changed inputs, predicted/observed compile outputs, human-readable object names, PCH/object/link counts, stages and peak memory. Source patches show the six mutations. Both fixture Git trees were clean at completion.

These scripts are archived experiment scripts, not installed CLI commands. Use the earlier native accounting benchmark layout: bench/ with the identical O2 native executable, matching runtime library in lib/, accounting Git fixture, php-published reference, Mold and the non-substituting measure-clang wrapper; adjacent repo/ contains tools/test_runner.py, tools/php_executor.py and a separate runtime tree. Initialize the runtime tree as its own Git baseline. Run sequentially with LD_LIBRARY_PATH=$PWD/lib: edit_matrix.py, mold_provenance.py, finalize_evidence.py. Empty output directories are required. The shared Task runner owns the sequential matrix; Ninja jobs remain eight.

finalize_evidence.py rederives PCH/provider overlap from raw monotonic timestamps in seconds, including normalization of the initial derived-field unit label. No primary durations or rebuild results are changed. The archived matrix script directly emits the corrected seconds-valued sum; overlap summed across providers can exceed PCH elapsed time.

Raw remote workspace:
`alex-ai@dev002.travelfuse.ro:/home/alex-ai/accounting-edit-matrix-20261016/bench/`

Downloaded raw evidence:
`/home/alexv/__AI/simple_cpp/simple_cpp_01/sandbox/accounting-recovery-20261016/native-edit-matrix-20261016/dev002-results/`

Raw results include marker timestamps, nominal 10 ms RSS samples, actual Clang argv and timing intervals, application stdout, dependency snapshots and patches. No generated C++ or shared runtime was hand-edited. Runtime mutations are confined to the copied authored runtime sources.
