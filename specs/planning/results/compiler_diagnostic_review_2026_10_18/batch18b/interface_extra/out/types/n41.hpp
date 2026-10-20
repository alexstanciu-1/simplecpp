#pragma once
#include "scpp/int_t.hpp"
#include "types/n49.hpp"
struct record_A : public virtual record_I
{
public:
public:
	scpp::int_t<> implementation_A__method_f();
	virtual scpp::int_t<> dispatch_A__method_f();
scpp::int_t<> method_f();
public:
	scpp::int_t<> dispatch_I__method_f() override;
public:
	virtual ~record_A() noexcept = default;
	record_A() = default;
	record_A(const record_A&) = default;
	record_A(record_A&&) = default;
	record_A& operator=(const record_A&) = default;
	record_A& operator=(record_A&&) = default;
};

