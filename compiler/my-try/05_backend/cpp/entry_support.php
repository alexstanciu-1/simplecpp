<?php
namespace scpp\compiler;

/** Compiled entry and runtime-error boundary shared by concrete applications. */
final class CPP_Entry_Support
{
	/** Expose a small nontemplate bridge to existing CLI and error contracts. */
	public static function base_header(): string
	{
		return '#pragma once
#include <stdint.h>
namespace scpp::internal {
void initialize_cli(int argc, char** argv);
int run_application(int argc, char** argv, int (*body)());
}
';
	}

	/** Keep production runtime diagnostics behind separately compiled support. */
	public static function base_body(): string
	{
		return '#include "specializations/base.hpp"
#include <cstdint>
#include "scpp/runtime_error.hpp"
#include "lang/php/cli_context.hpp"
namespace scpp::internal {
void initialize_cli(int argc, char** argv) { scpp::php::set_cli_args(argc, argv); }
int run_application(int argc, char** argv, int (*body)()) {
    try { initialize_cli(argc, argv); return body(); }
    catch (const std::exception& failure) { scpp::print_runtime_exception(failure); return 1; }
}
}
';
	}
}
