#pragma once
#ifdef NDEBUG
#undef NDEBUG
#endif
#include "scpp/compiler.hpp"
#include "scpp/memory.hpp"
#include <cassert>
#include <cstdlib>
#include <new>
#include <vector>

static long allocations_until_failure = -1;
void *operator new(std::size_t size) {
	if (allocations_until_failure == 0) throw std::bad_alloc();
	if (allocations_until_failure > 0) --allocations_until_failure;
	if (void *memory = std::malloc(size ? size : 1)) return memory;
	throw std::bad_alloc();
}
[[gnu::noinline]] void operator delete(void *memory) noexcept { std::free(memory); }
[[gnu::noinline]] void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }

using namespace scpp::compiler;
struct record {
	static inline int alive = 0;
	static inline int destroyed = 0;
	int value;
	explicit record(int value) : value(value) { ++alive; }
	record(const record &) = delete;
	record(record &&) = delete;
	~record() { --alive; ++destroyed; }
};
template<class E, class F> void rejects(F &&operation) {
	bool caught = false;
	try { operation(); } catch (const E &) { caught = true; }
	assert(caught);
}
// Sweep each allocation site until the operation succeeds. Each failure must
// preserve the container, which must also accept a subsequent ordinary mutation.
template<class F> void allocation_sweep(F &&trial) {
	for (long n = 0; n < 32; ++n) if (trial(n)) return;
	assert(false && "allocation sweep never reached success");
}
