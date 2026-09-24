<?php

/*
 * Role: native build and execution results.
 * Used by: Native_Runner and host reports.
 */
namespace scpp\compiler;

final class native_process_result {
	public int $exit_code;
	public string $stdout;
	public string $stderr;
}

final class native_result {
	public native_process_result $build;
	/** Null when compilation failed; host launch/capture failures throw instead. */
	public ?native_process_result $execution = null;
}
