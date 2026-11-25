#include "callables/n67.hpp"
#include "callables/n66.hpp"
void function_g()
{
}

void function_f()
{
	([&]() -> void {
	return function_g();
}());
	return;
}

