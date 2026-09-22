"""Lint and exercise the PHP prototype against isolated copies of the sample."""

import argparse
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time


PROTOTYPE = Path(__file__).resolve().parents[1]
ROOT = PROTOTYPE
TESTS = PROTOTYPE / "tests"
PHP = shutil.which("php")
if PHP is None:
    raise SystemExit("PHP 8.1+ CLI is required")

# Explicit suite membership preserves submission order; support contains helpers/probes.
PHP_FIXTURES = (
    "03_parse/frontend_storage.php",
    "03_parse/struct_parsing.php",
    "03_parse/metaprogramming_parsing.php",
    "04_analyze/collect_symbols/semantic_storage.php",
    "04_analyze/type_model/type_storage.php",
    "04_analyze/type_model/type_reuse.php",
    "04_analyze/type_model/type_context.php",
    "04_analyze/type_model/semantic_calls.php",
    "04_analyze/type_model/type_definitions.php",
    "04_analyze/resolve_types/return_types.php",
    "04_analyze/resolve_types/parameter_types.php",
    "04_analyze/resolve_types/local_types.php",
    "04_analyze/check_bodies/body_checking.php",
    "04_analyze/check_bodies/body_tasks.php",
    "04_analyze/check_bodies/local_bodies.php",
    "04_analyze/check_bodies/parameter_bodies.php",
    "04_analyze/analyze_lifetimes/lifetime_analysis.php",
    "04_analyze/analyze_lifetimes/resource_contracts.php",
    "04_analyze/analyze_lifetimes/local_lifetimes.php",
    "04_analyze/analyze_lifetimes/parameter_lifetimes.php",
    "05_generate_code/lower/lowering_inputs.php",
    "05_generate_code/prepare_backend/backend_preparation.php",
    "05_generate_code/prepare_backend/layout_preparation.php",
    "05_generate_code/prepare_backend/source_exports.php",
    "05_generate_code/lower/lowering.php",
    "features/local_lowering.php",
    "features/parameter_lowering.php",
    "features/integer_conversions.php",
    "features/addition.php",
    "features/integer_comparisons.php",
    "features/control_flow.php",
    "integration/native_executable.php",
    "integration/single_file.php",
    "integration/runtime_abi.php",
    "integration/runtime_inputs.php",
    "integration/provider_family_declarations.php",
    "integration/provider_family_types.php",
    "integration/provider_family_methods.php",
    "integration/provider_family_append.php",
    "integration/provider_family_nested.php",
    "integration/provider_family_runtime_types.php",
    "integration/source_project_preparation.php",
    "integration/source_family_execution.php",
    "integration/source_family_custom.php",
    "integration/runtime_inline.php",
    "integration/runtime_cleanup.php",
    "integration/runtime_copy.php",
    "integration/runtime_assignment.php",
    "integration/runtime_strings.php",
    "integration/runtime_conversions.php",
    "integration/runtime_console.php",
    "integration/source_structs.php",
    "integration/lifecycle_composition.php",
    "integration/custom_lifecycle.php",
    "integration/allocation_ownership.php",
    "integration/typed_storage.php",
    "integration/managed_storage.php",
    "integration/managed_growing_list.php",
    "integration/owning_storage_fields.php",
    "integration/fixed_arrays.php",
    "integration/index_depth.php",
    "integration/fixed_array_list.php",
    "integration/growing_list.php",
    "integration/owned_results.php",
    "integration/source_record_borrows.php",
    "integration/provider_records.php",
    "integration/runtime_record_borrows.php",
    "integration/runtime_record_strings.php",
    "06_build_output/build_native/native_modules.php",
    "05_generate_code/emit_llvm/module_assembly.php",
    "integration/native_reuse.php",
    "integration/native_publication.php",
    "features/program_entry.php",
    "04_analyze/collect_symbols/symbol_collection.php",
    "04_analyze/resolve_symbols/symbol_resolution.php",
    "04_analyze/resolve_symbols/template_bindings.php",
    "04_analyze/instantiate/explicit_instances.php",
    "04_analyze/check_templates/generic_contracts.php",
    "04_analyze/resolve_types/concrete_preparation.php",
    "04_analyze/resolve_symbols/local_resolution.php",
    "04_analyze/resolve_symbols/parameter_binding.php",
    "04_analyze/resolve_symbols/argument_resolution.php",
    "04_analyze/collect_symbols/symbol_comparison.php",
    "01_prepare_inputs/read_manifest/project_manifest.php",
    "01_prepare_inputs/read_manifest/manifest_export.php",
    "01_prepare_inputs/read_sources/source_discovery.php",
    "01_prepare_inputs/read_sources/source_paths.php",
    "01_prepare_inputs/read_sources/source_scan_tasks.php",
    "01_prepare_inputs/read_sources/source_snapshots.php",
    "01_prepare_inputs/read_sources/source_read_races.php",
    "02_tokenize/tokenization.php",
    "02_tokenize/variable_tokens.php",
    "02_tokenize/lexical_updates.php",
    "03_parse/parsing.php",
    "03_parse/step_lifecycle.php",
    "03_parse/variable_parsing.php",
    "03_parse/parameter_parsing.php",
    "03_parse/parse_updates.php",
    "01_prepare_inputs/read_manifest/manifest_recovery.php",
    "integration/boundaries.php",
    "compile/join_contracts.php",
    "compile/step_lifecycle.php",
    "compile/compile_driver.php",
    "compile/incremental_policy.php",
    "compile/lock_ownership.php",
)

PYTHON_FIXTURES = (
    "simulate_increment/increment_simulation.py",
    "integration/project_lock.py",
    "integration/native_cli.py",
    "integration/single_file_cli.py",
    "integration/parallel_native.py",
)

def positive_int(value):
    number = int(value)
    if number < 1:
        raise argparse.ArgumentTypeError("must be at least 1")
    return number


def lint(source):
    return subprocess.run([PHP, "-l", str(source)], capture_output=True, text=True)


def run_fixture(fixture, timeout):
    """Own each fixture workspace and capture output without changing process-wide cwd."""
    with tempfile.TemporaryDirectory(prefix="scpp_check_") as temporary:
        workspace = Path(temporary)
        working = workspace / "compiler"
        working.mkdir()
        shutil.copytree(ROOT / "examples/three_files", workspace / "fixtures/three_files")
        if fixture.endswith(".php"):
            # Xdebug retains rejected-call arguments; disable it for destruction proof.
            options = ["-d", "xdebug.mode=off"] if fixture == "compile/step_lifecycle.php" else []
            command = [PHP, *options, str(TESTS / fixture)]
        else:
            command = [sys.executable, str(TESTS / fixture)]
        return subprocess.run(command, cwd=working, capture_output=True, text=True, timeout=timeout)


def run_batch(items, worker, jobs, *, progress):
    """Bound active subprocesses; report each complete result from the coordinator."""
    failures = []
    with ThreadPoolExecutor(max_workers=jobs) as pool:
        pending = {pool.submit(worker, item): item for item in items}
        for completed, future in enumerate(as_completed(pending), 1):
            item = pending[future]
            try:
                result = future.result()
                success = result.returncode == 0
                output = result.stdout + result.stderr
            except Exception as error:
                success = False
                output = str(error)
            if progress or not success:
                print(f"[{completed}/{len(items)}] {'PASS' if success else 'FAIL'} {item}", flush=True)
                if output:
                    print(output.rstrip(), flush=True)
            if not success:
                failures.append(str(item))
    return failures


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--jobs", "-j", type=positive_int, default=10,
                        help="maximum concurrent lint/test processes (default: 10; 1 for serial)")
    parser.add_argument("--timeout", type=positive_int, default=180,
                        help="timeout per test in seconds (default: 180)")
    options = parser.parse_args()
    started = time.monotonic()

    registered = (*PHP_FIXTURES, *PYTHON_FIXTURES)
    discovered = {str(path.relative_to(TESTS)) for path in TESTS.rglob("*")
                  if path.suffix in (".php", ".py") and path != Path(__file__).resolve()
                  and "support" not in path.relative_to(TESTS).parts}
    if len(registered) != len(set(registered)):
        raise SystemExit("Duplicate test registration")
    if set(registered) != discovered:
        raise SystemExit(f"Test inventory mismatch: unregistered={sorted(discovered - set(registered))}; "
                         f"missing={sorted(set(registered) - discovered)}")

    print(f"Running syntax checks and {len(registered)} tests with up to {options.jobs} jobs.", flush=True)
    failures = run_batch(sorted(PROTOTYPE.rglob("*.php")), lint, options.jobs, progress=False)
    if failures:
        raise SystemExit("PHP syntax checks failed: " + ", ".join(failures))
    print("All prototype PHP files pass syntax checks.", flush=True)

    failures = run_batch(registered, lambda fixture: run_fixture(fixture, options.timeout),
                         options.jobs, progress=True)
    elapsed = time.monotonic() - started
    print(f"{len(registered) - len(failures)}/{len(registered)} tests passed in {elapsed:.1f}s "
          f"({options.jobs} jobs).", flush=True)
    if failures:
        raise SystemExit("Failed tests: " + ", ".join(failures))


if __name__ == "__main__":
    main()
