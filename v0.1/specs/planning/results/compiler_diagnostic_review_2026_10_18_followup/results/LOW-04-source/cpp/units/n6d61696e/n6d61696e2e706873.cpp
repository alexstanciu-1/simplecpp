#include "callables/n636c65616e7570.hpp"
#include "types/n43.hpp"
void function_cleanup()
{
}

record_C::~record_C() noexcept
{
	return ([&]() -> void {
	return function_cleanup();
}());
}

