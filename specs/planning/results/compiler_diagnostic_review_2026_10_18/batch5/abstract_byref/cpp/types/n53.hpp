#pragma once
#include "scpp/int_t.hpp"
struct record_S
{
public:
public:
	virtual scpp::int_t<> dispatch_S__method_f() = 0;
scpp::int_t<> method_f();
public:
	virtual ~record_S() noexcept = default;
	record_S() = default;
	record_S(const record_S&) = default;
	record_S(record_S&&) = default;
	record_S& operator=(const record_S&) = default;
	record_S& operator=(record_S&&) = default;
};

