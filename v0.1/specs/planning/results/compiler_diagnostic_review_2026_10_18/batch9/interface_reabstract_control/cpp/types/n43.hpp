#pragma once
#include "scpp/int_t.hpp"
#include "types/n42.hpp"
struct record_C : public record_B
{
public:
public:
	scpp::int_t<> implementation_C__method_f();
	virtual scpp::int_t<> dispatch_C__method_f();
scpp::int_t<> method_f();
public:
	scpp::int_t<> dispatch_B__method_f() override;
public:
	scpp::int_t<> dispatch_I__method_f() override;
public:
	virtual ~record_C() noexcept = default;
	record_C() = default;
	record_C(const record_C&) = default;
	record_C(record_C&&) = default;
	record_C& operator=(const record_C&) = default;
	record_C& operator=(record_C&&) = default;
};

