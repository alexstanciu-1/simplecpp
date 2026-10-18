#pragma once
#include "scpp/int_t.hpp"
struct record_I
{
public:
	virtual scpp::int_t<> dispatch_I__method_f() = 0;
scpp::int_t<> method_f();
public:
	virtual ~record_I() noexcept = default;
};

