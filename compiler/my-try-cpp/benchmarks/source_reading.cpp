#include "sources/inventory.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/datetime.hpp"
#include "scpp_compiler/io.hpp"
#include "scpp_compiler/textbuilder.hpp"

// Thread-safe measurement-only ordinary C++ allocation counters.
extern "C" void *malloc(__SIZE_TYPE__);
extern "C" void free(void *);
static __UINT64_TYPE__ allocations = 0;
static __UINT64_TYPE__ requested_bytes = 0;

void *operator new(__SIZE_TYPE__ bytes)
{
	void *memory = malloc(bytes == 0 ? 1 : bytes);
	if (memory == nullptr) {
		__builtin_trap();
	}
	__atomic_fetch_add(&allocations, 1, __ATOMIC_RELAXED);
	__atomic_fetch_add(&requested_bytes, bytes, __ATOMIC_RELAXED);
	return memory;
}

void operator delete(void *memory) noexcept
{
	free(memory);
}

void *operator new[](__SIZE_TYPE__ bytes)
{
	return ::operator new(bytes);
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

namespace scpp::compiler
{
	/**
	 * Measure initial reads and pending-buffer reuse without scan or reporting allocations.
	 */
	static int profile(const vector_string &arguments)
	{
		if (arguments.size() != 1) {
			fail(string("Usage: source-reading-profile ROOT"));
		}
		vector_string roots(arguments);
		validate_source_roots(roots);
		source_inventory inventory;
		inventory.scan(roots, 8);
		for (int64 trial = 0; trial < 2; ++trial)
		{
			const uint64 before_count = __atomic_load_n(&allocations, __ATOMIC_RELAXED);
			const uint64 before_bytes = __atomic_load_n(&requested_bytes, __ATOMIC_RELAXED);
			const auto begin = monotonic_micros();
			inventory.load(roots, 8);
			const auto elapsed = monotonic_micros() - begin;
			const uint64 count = uint64(__atomic_load_n(&allocations, __ATOMIC_RELAXED)) - before_count;
			const uint64 bytes = uint64(__atomic_load_n(&requested_bytes, __ATOMIC_RELAXED)) - before_bytes;
			textbuilder report;
			report.append(trial == 0 ? "initial" : "unchanged");
			report.append(" files=");
			report.append_number(inventory.size());
			report.append(" peak_workers=");
			report.append_number(inventory.reads().peak_workers);
			report.append(" allocations=");
			report.append_number(count);
			report.append(" requested_bytes=");
			report.append_number(bytes);
			report.append(" elapsed_us=");
			report.append_number(elapsed);
			print_line(report.finish());
		}
		return 0;
	}
}

int main(int argc, char **argv)
{
	return scpp::compiler_support::run_host(argc, argv, scpp::compiler::profile);
}
