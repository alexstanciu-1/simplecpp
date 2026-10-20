#pragma once
#include "types/n42.hpp"
#include "types/n49.hpp"
#include "scpp/int_t.hpp"
struct record_C : public record_B, public virtual record_I
{
public:
public:
	virtual scpp::int_t<> dispatch_C__method_f() final;
scpp::int_t<> method_f();
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

