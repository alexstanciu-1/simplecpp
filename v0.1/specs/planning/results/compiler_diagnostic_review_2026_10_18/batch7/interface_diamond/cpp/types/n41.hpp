#pragma once
#include "scpp/int_t.hpp"
struct record_A
{
public:
	virtual scpp::int_t<> dispatch_A__method_f() = 0;
scpp::int_t<> method_f();
public:
	virtual ~record_A() noexcept = default;
};

