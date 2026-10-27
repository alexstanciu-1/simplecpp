// Standalone measurement; compile without ThreadSanitizer (which owns allocation hooks).
#include "scpp/integers.hpp"
#include <chrono>
#include <cstdio>
#include <cstdlib>
#include <new>

static unsigned long long allocations = 0;
void *operator new(__SIZE_TYPE__ size)
{
	++allocations;
	if (void *memory = std::malloc(size == 0 ? 1 : size)) { return memory; }
	throw std::bad_alloc();
}
void operator delete(void *memory) noexcept { std::free(memory); }
void *operator new[](__SIZE_TYPE__ size) { return ::operator new(size); }
void operator delete[](void *memory) noexcept { ::operator delete(memory); }
void operator delete(void *memory, __SIZE_TYPE__) noexcept { std::free(memory); }
void operator delete[](void *memory, __SIZE_TYPE__) noexcept { std::free(memory); }

int main()
{
	const auto before = allocations;
	const auto start = std::chrono::steady_clock::now();
	scpp::uint64 sum = 0;
	for (scpp::uint64 index = 0; index < 100000; ++index) { sum += index; }
	const auto elapsed = std::chrono::duration_cast<std::chrono::microseconds>(
		std::chrono::steady_clock::now() - start).count();
	const auto allocated = allocations - before;
	std::printf("100000 iterations: %lld us, %llu new/new[] calls, sum=%llu\n",
		static_cast<long long>(elapsed), allocated, static_cast<unsigned long long>(sum.native_value()));
	return allocated == 0 && sum.native_value() == 4999950000ULL ? 0 : 1;
}
