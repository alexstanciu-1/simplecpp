#pragma once

#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>

namespace scpp {

class AllocationResult;
class AmountResult;
class MoneyAllocation;
class MoneyRules;

class AmountResult : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	bool_t valid = bool_t(static_cast<bool_t>(false));
	int_t<> amount = static_cast<int_t<> >(0);
	string_t reason = string_t("");
};

class MoneyRules : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	static int_t<> maximum();
	static bool_t accepts(int_t<> amount);
	static shared_p<AmountResult> add(int_t<> left, int_t<> right);
	static shared_p<AmountResult> multiply(int_t<> unit_amount, int_t<> quantity);
	static shared_p<AmountResult> rate(int_t<> base, int_t<> basis_points);
	static shared_p<AmountResult> prorate(int_t<> amount, int_t<> numerator, int_t<> denominator);
};

}
