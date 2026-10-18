#pragma once

// Executable startup state only: including this must not pull in boxed values
// or process APIs. The entrypoint initializes it before authored code runs.
namespace scpp::php {
namespace cli_detail {
inline int g_cli_argc = 0;
inline char** g_cli_argv = nullptr;
}

inline void set_cli_args(int argc, char** argv) {
	cli_detail::g_cli_argc = argc;
	cli_detail::g_cli_argv = argv;
}
} // namespace scpp::php
