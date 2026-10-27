#include "scalar_access.hpp"
#include "scpp_compiler/textbuilder.hpp"
#include "scpp_compiler/io.hpp"
#include "scpp_compiler/datetime.hpp"
#include "scpp_compiler/filesystem.hpp"
#include "scpp_compiler/host.hpp"
#include "string_state.hpp"
#include "modules/filesystem/filesystem.hpp"
#include "modules/datetime/datetime.hpp"
#include <cstdio>
#include <stdexcept>

namespace scpp::compiler_support
{
	/**
	 * Read borrowed stdin into a runtime buffer without opening or creating any files.
	 */
	string read_standard_input()
	{
		textbuilder content;
		uint64 size = 0;
		char chunk[16384];
		while (true)
		{
			const auto count = std::fread(chunk, 1, sizeof(chunk), stdin);
			if (std::ferror(stdin)) {
				throw std::runtime_error("stdin read failed");
			}
			size += uint64(count);
			if (size > 4294967295ULL) {
				throw std::runtime_error("Source exceeds 32-bit offset capacity");
			}
			if (count != 0) {
				content.append(string(chunk, uint64(count)));
			}
			if (std::feof(stdin)) {
				return content.finish();
			}
		}
	}

	[[noreturn]] void fail(const string &message)
	{
		throw std::runtime_error(runtime_access::get(message).native_value());
	}

	/**
	 * Attach the catalog path to the active standard exception before propagating it.
	 */
	[[noreturn]] void rethrow_with_context(const string &context)
	{
		try {
			throw;
		}
		catch (const std::exception &error) {
			throw std::runtime_error(runtime_access::get(context).native_value() + ": " + error.what());
		}
	}

	string fs_read(const string &path)
	{
		auto result = fs::get(runtime_access::get(path));
		if (!result.has_value().native_value()) {
			throw std::runtime_error(result.require_error("read file").get_message().native_value());
		}
		return runtime_access::wrap(result.value());
	}

	/**
	 * Reject invalid catalog directories and return their canonical filesystem identity.
	 */
	string fs_canonical_directory(const string &path)
	{
		const auto &native = runtime_access::get(path);
		if (native.native_value().find('\0') != std::string::npos) {
			throw std::runtime_error("NUL in catalog path");
		}
		if (!fs::is_dir(native).native_value()) {
			throw std::runtime_error("Catalog directory does not exist: " + native.native_value());
		}
		auto result = fs::realpath(native);
		if (!result.has_value().native_value()) {
			throw std::runtime_error(result.require_error("catalog directory").get_message().native_value());
		}
		return runtime_access::wrap(result.value());
	}

	uint64 monotonic_micros()
	{
		return dt::monotonic_micros().native_value();
	}

	// Terminal/argv adaptation belongs only to this native host boundary.
	void print_line(const string &message)
	{
		const auto &bytes = runtime_access::get(message).native_value();
		if (std::fwrite(bytes.data(), 1, bytes.size(), stdout) != bytes.size() || std::fputc('\n', stdout) == EOF) {
			throw std::runtime_error("stdout write failed");
		}
	}

	/**
	 * Adapt native arguments and report uncaught startup failures at the process boundary.
	 */
	int run_host(int argc, char **argv, int (*entry)(const vector_string &))
	{
		try {
			vector_string arguments;
			for (int index = 1; index < argc; ++index) {
				arguments.append(string(argv[index]));
			}
			return entry(arguments);
		}
		catch (const std::exception &error) {
			std::fprintf(stderr, "startup failed: %s\n", error.what());
			return 1;
		}
		catch (...) {
			std::fputs("startup failed: unknown exception\n", stderr);
			return 1;
		}
	}
}
