#include "money.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t AmountResult::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == AmountResult::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t MoneyRules::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == MoneyRules::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

I MoneyRules::maximum() {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::maximum", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 10);
	return static_cast<I >(1000000000);
}

bool_t MoneyRules::accepts(I amount) {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::accepts", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 14);
	return ((amount >= (-static_cast<I >(1000000000))) && (amount <= static_cast<I >(1000000000)));
}

P_AmountResult MoneyRules::add(I left, I right) {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::add", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 18);
	P_AmountResult result = create_AmountResult();
	if (static_cast<bool>(((!MoneyRules::accepts(left)) || (!MoneyRules::accepts(right))))) {
		result->reason = string_t("amount.operand");
		return result;
	}
	I sum = convert_I((left + right));
	if (static_cast<bool>((!MoneyRules::accepts(sum)))) {
		result->reason = string_t("amount.overflow");
		return result;
	}
	result->valid = static_cast<bool_t>(true);
	result->amount = sum;
	return result;
}

P_AmountResult MoneyRules::multiply(I unit_amount, I quantity) {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::multiply", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 34);
	P_AmountResult result = create_AmountResult();
	if (static_cast<bool>((((!MoneyRules::accepts(unit_amount)) || (quantity < static_cast<I >(0))) || (quantity > static_cast<I >(1000000))))) {
		result->reason = string_t("amount.quantity");
		return result;
	}
	I product = convert_I((unit_amount * quantity));
	if (static_cast<bool>((!MoneyRules::accepts(product)))) {
		result->reason = string_t("amount.overflow");
		return result;
	}
	result->valid = static_cast<bool_t>(true);
	result->amount = product;
	return result;
}

P_AmountResult MoneyRules::rate(I base, I basis_points) {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::rate", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 50);
	P_AmountResult result = create_AmountResult();
	if (static_cast<bool>((((!MoneyRules::accepts(base)) || (basis_points < static_cast<I >(0))) || (basis_points > static_cast<I >(10000))))) {
		result->reason = string_t("amount.rate");
		return result;
	}
	bool_t negative = convert_bool_t((base < static_cast<I >(0)));
	I magnitude = convert_I(base);
	if (static_cast<bool>(php::condition_truthy(negative))) {
		magnitude = (-base);
	}
	I scaled = convert_I((magnitude * basis_points));
	I remainder = convert_I((scaled % static_cast<I >(10000)));
	I rounded = convert_I(convert_I(((scaled - remainder) / static_cast<I >(10000))));
	if (static_cast<bool>(php::condition_truthy((remainder >= static_cast<I >(5000))))) {
		rounded += static_cast<I >(1);
	}
	if (static_cast<bool>(php::condition_truthy(negative))) {
		rounded = (-rounded);
	}
	result->valid = static_cast<bool_t>(true);
	result->amount = rounded;
	return result;
}

P_AmountResult MoneyRules::prorate(I amount, I numerator, I denominator) {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::prorate", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 76);
	P_AmountResult result = create_AmountResult();
	if (static_cast<bool>((((!MoneyRules::accepts(amount)) || (numerator < static_cast<I >(0))) || (denominator <= static_cast<I >(0))))) {
		result->reason = string_t("amount.ratio");
		return result;
	}
	if (static_cast<bool>(((numerator > denominator) || (denominator > static_cast<I >(1000000))))) {
		result->reason = string_t("amount.ratio");
		return result;
	}
	I magnitude = convert_I(amount);
	if (static_cast<bool>((amount < static_cast<I >(0)))) {
		magnitude = (-amount);
	}
	I scaled = convert_I((magnitude * numerator));
	I remainder = convert_I((scaled % denominator));
	I rounded = convert_I(convert_I(((scaled - remainder) / denominator)));
	if (static_cast<bool>(php::condition_truthy(((remainder * static_cast<I >(2)) >= denominator)))) {
		rounded += static_cast<I >(1);
	}
	if (static_cast<bool>((amount < static_cast<I >(0)))) {
		rounded = (-rounded);
	}
	result->valid = static_cast<bool_t>(true);
	result->amount = rounded;
	return result;
}

bool_t AllocationResult::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == AllocationResult::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t MoneyAllocation::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == MoneyAllocation::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AllocationResult MoneyAllocation::equally(I amount, I parts) {
	SCPP_CALL_DEPTH_GUARD("MoneyAllocation::equally", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 112);
	P_AllocationResult result = create_AllocationResult();
	if (static_cast<bool>((((!MoneyRules::accepts(amount)) || (parts <= static_cast<I >(0))) || (parts > static_cast<I >(10000))))) {
		result->reason = string_t("allocation.range");
		return result;
	}
	I magnitude = convert_I(amount);
	if (static_cast<bool>((amount < static_cast<I >(0)))) {
		magnitude = (-amount);
	}
	I remainder = convert_I((magnitude % parts));
	I quotient = convert_I(convert_I(((magnitude - remainder) / parts)));
	for (auto index = static_cast<I >(0); static_cast<bool>((index < parts)); (index++)) {
		I portion = convert_I(quotient);
		if (static_cast<bool>((index < remainder))) {
			portion += static_cast<I >(1);
		}
		if (static_cast<bool>((amount < static_cast<I >(0)))) {
			portion = (-portion);
		}
		(void) result->amounts.push_back(portion);
	}
	result->valid = static_cast<bool_t>(true);
	return result;
}

P_AllocationResult MoneyAllocation::weighted(I amount, const V_I& weights) {
	SCPP_CALL_DEPTH_GUARD("MoneyAllocation::weighted", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 138);
	P_AllocationResult result = create_AllocationResult();
	if (static_cast<bool>((((!MoneyRules::accepts(amount)) || php::identical(php::count(weights), static_cast<I >(0))) || (php::count(weights) > static_cast<I >(10000))))) {
		result->reason = string_t("allocation.range");
		return result;
	}
	I total_weight = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_145 = weights;
	for (auto __scpp_foreach_entry_145 : foreach_range(__scpp_foreach_source_145)) {
		auto weight = __scpp_foreach_entry_145.value_copy();
		if (static_cast<bool>(((weight < static_cast<I >(0)) || (weight > static_cast<I >(1000000))))) {
			result->reason = string_t("allocation.weight");
			return result;
		}
		total_weight += weight;
		if (static_cast<bool>((total_weight > static_cast<I >(1000000)))) {
			result->reason = string_t("allocation.weight-total");
			return result;
		}
	}
	if (static_cast<bool>(php::identical(total_weight, static_cast<I >(0)))) {
		result->reason = string_t("allocation.zero-weight");
		return result;
	}
	I magnitude = convert_I(amount);
	if (static_cast<bool>((amount < static_cast<I >(0)))) {
		magnitude = (-amount);
	}
	V_I remainders = {};
	I assigned = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_166 = weights;
	for (auto __scpp_foreach_entry_166 : foreach_range(__scpp_foreach_source_166)) {
		auto weight = __scpp_foreach_entry_166.value_copy();
		I scaled = convert_I((magnitude * weight));
		I remainder = convert_I((scaled % total_weight));
		I portion = convert_I(convert_I(((scaled - remainder) / total_weight)));
		(void) result->amounts.push_back(portion);
		(void) remainders.push_back(remainder);
		assigned += portion;
	}
	I leftover = convert_I((magnitude - assigned));
	while (static_cast<bool>((leftover > static_cast<I >(0)))) {
		I best = convert_I((-static_cast<I >(1)));
		I best_remainder = convert_I((-static_cast<I >(1)));
		for (auto index = static_cast<I >(0); static_cast<bool>((index < php::count(remainders))); (index++)) {
			if (static_cast<bool>((remainders.at(index) > best_remainder))) {
				best = index;
				best_remainder = remainders.at(index);
			}
		}
		if (static_cast<bool>((best < static_cast<I >(0)))) {
			result->reason = string_t("allocation.internal");
			return result;
		}
		result->amounts.at(best) += static_cast<I >(1);
		remainders.at(best) = (-static_cast<I >(1));
		leftover -= static_cast<I >(1);
	}
	if (static_cast<bool>((amount < static_cast<I >(0)))) {
		for (auto index = static_cast<I >(0); static_cast<bool>((index < php::count(result->amounts))); (index++)) {
			result->amounts.at(index) = (-result->amounts.at(index));
		}
	}
	result->valid = static_cast<bool_t>(true);
	return result;
}

}

