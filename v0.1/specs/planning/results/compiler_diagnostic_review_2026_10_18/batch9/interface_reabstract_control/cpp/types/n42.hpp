#pragma once
#include "scpp/int_t.hpp"
#include "types/n41.hpp"
struct record_B : public record_A
{
public:
public:
	virtual scpp::int_t<> dispatch_B__method_f() = 0;
scpp::int_t<> method_f();
public:
	scpp::int_t<> dispatch_A__method_f() override;
public:
	scpp::int_t<> dispatch_I__method_f() override;
public:
	virtual ~record_B() noexcept = default;
	record_B() = default;
	record_B(const record_B&) = default;
	record_B(record_B&&) = default;
	record_B& operator=(const record_B&) = default;
	record_B& operator=(record_B&&) = default;
};

