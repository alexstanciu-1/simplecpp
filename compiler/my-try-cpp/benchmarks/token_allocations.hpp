#pragma once
// Thread-safe measurement-only ordinary C++ allocation counters.
extern "C" void *malloc(__SIZE_TYPE__);
extern "C" void free(void *);
static bool tracking = false;
static __UINT64_TYPE__ allocations = 0;
static __UINT64_TYPE__ requested_bytes = 0;

void *operator new(__SIZE_TYPE__ bytes)
{
	void *memory = malloc(bytes == 0 ? 1 : bytes);
	if (memory == nullptr) {
		__builtin_trap();
	}
	if (tracking) {
		__atomic_fetch_add(&allocations, 1, __ATOMIC_RELAXED);
		__atomic_fetch_add(&requested_bytes, bytes, __ATOMIC_RELAXED);
	}
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
