#pragma once
#include "scpp/int_t.hpp"
#include "types/n49.hpp"
struct record_S : public virtual record_I
{
public:
public:
	scpp::int_t<> implementation_S__method_f();
	virtual scpp::int_t<> dispatch_S__method_f();
scpp::int_t<> method_f();
public:
	scpp::int_t<> dispatch_I__method_f() override;
public:
	virtual ~record_S() noexcept = default;
	record_S() = default;
	record_S(const record_S&) = default;
	record_S(record_S&&) = default;
	record_S& operator=(const record_S&) = default;
	record_S& operator=(record_S&&) = default;
};

