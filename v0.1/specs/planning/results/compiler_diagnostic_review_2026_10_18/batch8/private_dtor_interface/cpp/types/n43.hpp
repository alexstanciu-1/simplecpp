#pragma once
#include "scpp/int_t.hpp"
#include "types/n49.hpp"
struct record_C : public virtual record_I
{
public:
public:
	scpp::int_t<> implementation_C__method_f();
	virtual scpp::int_t<> dispatch_C__method_f();
scpp::int_t<> method_f();
private:
	virtual ~record_C() noexcept;
public:
	scpp::int_t<> dispatch_I__method_f() override;
public:
	record_C() = default;
	record_C(const record_C&) = default;
	record_C(record_C&&) = default;
	record_C& operator=(const record_C&) = default;
	record_C& operator=(record_C&&) = default;
};

