#pragma once
#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
struct record_I
{
public:
	virtual scpp::unique_p<scpp::int_t<>> dispatch_I__method_f(scpp::unique_p<scpp::int_t<>> argument_0) = 0;
scpp::unique_p<scpp::int_t<>> method_f(scpp::unique_p<scpp::int_t<>> argument_0);
public:
	virtual ~record_I() noexcept = default;
};

