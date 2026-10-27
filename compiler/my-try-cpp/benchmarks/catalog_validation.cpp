#include "startup/catalog.hpp"
#include "scpp_compiler/datetime.hpp"
#include "scpp_compiler/filesystem.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/io.hpp"
#include "scpp_compiler/textbuilder.hpp"

// Measurement-only native hooks. Ordinary C++ new, not malloc or aligned new.
extern "C" void *malloc(__SIZE_TYPE__);
extern "C" void free(void *);
struct alignas(__BIGGEST_ALIGNMENT__) allocation_header {
	__SIZE_TYPE__ size;
};
static __UINT64_TYPE__ allocation_count = 0;
static __UINT64_TYPE__ requested_bytes = 0;
static __UINT64_TYPE__ live_bytes = 0;
static __UINT64_TYPE__ peak_bytes = 0;

/**
 * Retain requested sizes to measure live payload, excluding allocator/header overhead.
 */
void *operator new(__SIZE_TYPE__ size)
{
	if (size > __SIZE_MAX__ - sizeof(allocation_header)) {
		__builtin_trap();
	}
	auto *header = static_cast<allocation_header *>(malloc(sizeof(allocation_header) + size));
	if (header == nullptr) {
		__builtin_trap();
	}
	header->size = size;
	++allocation_count;
	requested_bytes += size;
	live_bytes += size;
	if (live_bytes > peak_bytes) {
		peak_bytes = live_bytes;
	}
	return header + 1;
}

void operator delete(void *memory) noexcept
{
	if (memory != nullptr) {
		auto *header = static_cast<allocation_header *>(memory) - 1;
		live_bytes -= header->size;
		free(header);
	}
}

void *operator new[](__SIZE_TYPE__ size)
{
	return ::operator new(size);
}

void operator delete[](void *memory) noexcept
{
	::operator delete(memory);
}

void operator delete(void *memory, __SIZE_TYPE__) noexcept
{
	::operator delete(memory);
}

void operator delete[](void *memory, __SIZE_TYPE__) noexcept
{
	::operator delete(memory);
}

/**
 * Calibrate counts and live-byte accounting before measuring application work.
 */
static void verify_allocation_hooks()
{
	const auto count_before = allocation_count;
	const auto bytes_before = requested_bytes;
	const auto live_before = live_bytes;
	void *memory = ::operator new(37);
	if (allocation_count != count_before + 1 || requested_bytes != bytes_before + 37 ||
		live_bytes != live_before + 37) {
		__builtin_trap();
	}
	::operator delete(memory);
	if (live_bytes != live_before) {
		__builtin_trap();
	}
}

namespace scpp::compiler
{
	struct measurement {
		uint64 allocations = allocation_count;
		uint64 bytes = requested_bytes;
		uint64 live = live_bytes;
		uint64 start = monotonic_micros();
	};

	/**
	 * Snapshot before report construction so formatting does not pollute the sample.
	 */
	static void report(const string &label, const measurement &before)
	{
		const uint64 elapsed = monotonic_micros() - before.start;
		const uint64 allocations = uint64(allocation_count) - before.allocations;
		const uint64 bytes = uint64(requested_bytes) - before.bytes;
		const int64 retained = int64(uint64(live_bytes)) - int64(before.live);
		const uint64 peak = uint64(peak_bytes) - before.live;
		textbuilder output;
		output.append(label);
		output.append(" allocations=");
		output.append_number(allocations);
		output.append(" requested_bytes=");
		output.append_number(bytes);
		output.append(" peak_extra_bytes=");
		output.append_number(peak);
		output.append(" retained_bytes=");
		output.append_number(retained);
		output.append(" elapsed_us=");
		output.append_number(elapsed);
		print_line(output.finish());
	}

	/**
	 * Validate already parsed default catalogs with one shared identity inventory.
	 */
	static int profile(const vector_string &arguments)
	{
		if (arguments.size() != 1) {
			fail(string("Usage: catalog-validation-profile CATALOG_DIRECTORY"));
		}
		vector_string names;
		names.append(string("core"));
		names.append(string("json"));
		names.append(string("strings"));
		names.append(string("datetime"));
		names.append(string("filesystem"));
		catalog_inventory inventory;
		for (int64 i = 0; i < names.size(); ++i)
		{
			const auto name = names.read(i);
			textbuilder path;
			path.append(arguments.read(0));
			path.append("/");
			path.append(name);
			path.append(".json");
			const auto root = json_value::parse(fs_read(path.finish()));
			const measurement before;
			peak_bytes = live_bytes;
			validate_catalog(root, name, inventory);
			report(name, before);
		}

		const auto object = json_value::parse(string("{\"name\":\"example\"}"));
		const string key("name");
		const string literal_label("1000_literal_member_reads");
		const string key_label("1000_existing_key_member_reads");
		const string string_label("1000_vocabulary_strings");
		const measurement literals;
		peak_bytes = live_bytes;
		for (int64 i = 0; i < 1000; ++i) {
			(void)object.member("name");
		}
		report(literal_label, literals);
		const measurement keys;
		peak_bytes = live_bytes;
		for (int64 i = 0; i < 1000; ++i) {
			(void)object.member(key);
		}
		report(key_label, keys);
		const measurement strings;
		peak_bytes = live_bytes;
		for (int64 i = 0; i < 1000; ++i) {
			const string vocabulary("name type passing default requires_copy requires_transfer transfer_policy variadic");
		}
		report(string_label, strings);
		const string match_label("1000_vocabulary_matches");
		const measurement matches;
		peak_bytes = live_bytes;
		for (int64 i = 0; i < 1000; ++i) {
			if (!key.is_one_of("name type passing default requires_copy requires_transfer transfer_policy variadic")) {
				fail(string("Vocabulary probe failed"));
			}
		}
		if (uint64(allocation_count) != matches.allocations) {
			fail(string("Vocabulary matching allocated"));
		}
		report(match_label, matches);
		return 0;
	}
}

int main(int argc, char **argv)
{
	verify_allocation_hooks();
	return scpp::compiler_support::run_host(argc, argv, scpp::compiler::profile);
}
