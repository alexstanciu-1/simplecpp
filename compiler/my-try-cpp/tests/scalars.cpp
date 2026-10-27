#include "scpp_compiler/types.hpp"
#include "scpp_compiler/string.hpp"
#include "scpp_compiler/datetime.hpp"
#include "scpp_compiler/io.hpp"
#include "scpp_compiler/textbuilder.hpp"

// Test-only native allocation hooks: count allocations across the compiled boundary.
extern "C" void *malloc(__SIZE_TYPE__);
extern "C" void free(void *);
static unsigned long long allocations = 0;

void *operator new(__SIZE_TYPE__ size)
{
	++allocations;
	void *memory = malloc(size == 0 ? 1 : size);
	if (memory == nullptr) {
		__builtin_trap();
	}
	return memory;
}

void operator delete(void *memory) noexcept
{
	free(memory);
}

void *operator new[](__SIZE_TYPE__ size)
{
	return ::operator new(size);
}

void operator delete[](void *memory) noexcept
{
	::operator delete(memory);
}

namespace scpp::compiler
{
	/**
	 * Exercise defined scalar edge cases, runtime failures and allocation-free arithmetic.
	 */
	static int test()
	{
		static_assert(sizeof(int32) == 4 && sizeof(uint32) == 4);
		static_assert(sizeof(int64) == 8 && sizeof(uint64) == 8);
		static_assert(sizeof(boolean) == 1);

		int32 signed_value = -7;
		uint32 narrow = 4294967295u;
		uint64 wide = 18446744073709551615ull;
		++narrow;
		++wide;
		if (narrow != 0 || wide != 0 || signed_value / 2 != -3 || signed_value % 2 != -1) {
			return 1;
		}
		if (int64(signed_value) != -7 || uint64(uint32(42)) != 42 || int32(boolean(true)) != 1) {
			return 2;
		}

		uint64 preserved = 9;
		boolean division_failed = false;
		try {
			preserved /= 0;
		}
		catch (...) {
			division_failed = true;
		}
		if (!division_failed || preserved != 9) {
			return 3;
		}

		boolean remainder_failed = false;
		try {
			preserved %= 0;
		}
		catch (...) {
			remainder_failed = true;
		}
		if (!remainder_failed || preserved != 9) {
			return 4;
		}

		boolean reached = false;
		boolean disabled = false;
		if (disabled && (reached = true)) {
			return 5;
		}
		if (reached || !isset(boolean(false)) || !isset(uint64(0))) {
			return 6;
		}
		if (!empty(string("")) || empty(string("0")) || !isset(string("")) || strlen(string("a\0b", 3)) != 3) {
			return 7;
		}

		const auto start = monotonic_micros();
		const auto before = allocations;
		uint64 sum = 0;
		for (uint64 index = 0; index < 100000; ++index) {
			sum += index;
		}
		const auto allocated = allocations - before;
		const auto elapsed = monotonic_micros() - start;
		if (allocated != 0 || sum != 4999950000ull) {
			return 8;
		}

		textbuilder report;
		report.append("scalar loop: 100000 iterations, zero allocations, microseconds=");
		report.append_number(elapsed);
		print_line(report.finish());
		return 0;
	}
}

int main()
{
	return scpp::compiler::test();
}
