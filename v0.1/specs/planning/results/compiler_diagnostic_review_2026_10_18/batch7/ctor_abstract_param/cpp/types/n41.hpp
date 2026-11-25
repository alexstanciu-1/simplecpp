#pragma once
#include "scpp/int_t.hpp"
struct record_A
{
public:
public:
	virtual scpp::int_t<> dispatch_A__method_f() = 0;
scpp::int_t<> method_f();
public:
	virtual ~record_A() noexcept = default;
	record_A() = default;
	record_A(const record_A&) = default;
	record_A(record_A&&) = default;
	record_A& operator=(const record_A&) = default;
	record_A& operator=(record_A&&) = default;
};

