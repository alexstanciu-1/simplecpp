#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

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
	I amount = static_cast<I >(0);
	string_t reason = string_t("");
};

class MoneyRules : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	static I maximum();
	static bool_t accepts(I amount);
	static P_AmountResult add(I left, I right);
	static P_AmountResult multiply(I unit_amount, I quantity);
	static P_AmountResult rate(I base, I basis_points);
	static P_AmountResult prorate(I amount, I numerator, I denominator);
};

class AllocationResult : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	bool_t valid = bool_t(static_cast<bool_t>(false));
	string_t reason = string_t("");
	V_I amounts = V_I{};
};

class MoneyAllocation : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	static P_AllocationResult equally(I amount, I parts);
	static P_AllocationResult weighted(I amount, const V_I& weights);
};

}

