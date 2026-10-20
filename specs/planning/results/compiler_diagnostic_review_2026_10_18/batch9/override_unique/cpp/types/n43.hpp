#pragma once
#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "types/n49.hpp"
struct record_C : public virtual record_I
{
public:
public:
	scpp::unique_p<scpp::int_t<>> implementation_C__method_f(scpp::unique_p<scpp::int_t<>> local_p);
	virtual scpp::unique_p<scpp::int_t<>> dispatch_C__method_f(scpp::unique_p<scpp::int_t<>> argument_0);
scpp::unique_p<scpp::int_t<>> method_f(scpp::unique_p<scpp::int_t<>> argument_0);
public:
	scpp::unique_p<scpp::int_t<>> dispatch_I__method_f(scpp::unique_p<scpp::int_t<>> argument_0) override;
public:
	virtual ~record_C() noexcept = default;
	record_C() = default;
	record_C(const record_C&) = default;
	record_C(record_C&&) = default;
	record_C& operator=(const record_C&) = default;
	record_C& operator=(record_C&&) = default;
};

