#pragma once
#include "scpp_compiler/string.hpp"

namespace scpp::compiler_support
{
	string fs_read(const string &path);
	string fs_canonical_directory(const string &path);
}

namespace scpp::compiler
{
	using compiler_support::fs_read;
	using compiler_support::fs_canonical_directory;
}
