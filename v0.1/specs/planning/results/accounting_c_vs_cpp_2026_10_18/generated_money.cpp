#include "generated_money.hpp"

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

int_t<> MoneyRules::maximum() {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::maximum", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 10);
	return static_cast<int_t<> >(1000000000);
}

bool_t MoneyRules::accepts(int_t<> amount) {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::accepts", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 14);
	return ((amount >= (-static_cast<int_t<> >(1000000000))) && (amount <= static_cast<int_t<> >(1000000000)));
}

shared_p<AmountResult> MoneyRules::add(int_t<> left, int_t<> right) {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::add", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 18);
	shared_p<AmountResult> result = create<AmountResult>();
	if (static_cast<bool>(((!MoneyRules::accepts(left)) || (!MoneyRules::accepts(right))))) {
		result->reason = string_t("amount.operand");
		return result;
	}
	int_t<> sum = required_cast<int_t<>>((left + right));
	if (static_cast<bool>((!MoneyRules::accepts(sum)))) {
		result->reason = string_t("amount.overflow");
		return result;
	}
	result->valid = static_cast<bool_t>(true);
	result->amount = sum;
	return result;
}

shared_p<AmountResult> MoneyRules::multiply(int_t<> unit_amount, int_t<> quantity) {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::multiply", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 34);
	shared_p<AmountResult> result = create<AmountResult>();
	if (static_cast<bool>((((!MoneyRules::accepts(unit_amount)) || (quantity < static_cast<int_t<> >(0))) || (quantity > static_cast<int_t<> >(1000000))))) {
		result->reason = string_t("amount.quantity");
		return result;
	}
	int_t<> product = required_cast<int_t<>>((unit_amount * quantity));
	if (static_cast<bool>((!MoneyRules::accepts(product)))) {
		result->reason = string_t("amount.overflow");
		return result;
	}
	result->valid = static_cast<bool_t>(true);
	result->amount = product;
	return result;
}

shared_p<AmountResult> MoneyRules::rate(int_t<> base, int_t<> basis_points) {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::rate", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 50);
	shared_p<AmountResult> result = create<AmountResult>();
	if (static_cast<bool>((((!MoneyRules::accepts(base)) || (basis_points < static_cast<int_t<> >(0))) || (basis_points > static_cast<int_t<> >(10000))))) {
		result->reason = string_t("amount.rate");
		return result;
	}
	bool_t negative = required_cast<bool_t>((base < static_cast<int_t<> >(0)));
	int_t<> magnitude = required_cast<int_t<>>(base);
	if (static_cast<bool>(php::condition_truthy(negative))) {
		magnitude = (-base);
	}
	int_t<> scaled = required_cast<int_t<>>((magnitude * basis_points));
	int_t<> remainder = required_cast<int_t<>>((scaled % static_cast<int_t<> >(10000)));
	int_t<> rounded = required_cast<int_t<>>(cast<int_t<>>(((scaled - remainder) / static_cast<int_t<> >(10000))));
	if (static_cast<bool>(php::condition_truthy((remainder >= static_cast<int_t<> >(5000))))) {
		rounded += static_cast<int_t<> >(1);
	}
	if (static_cast<bool>(php::condition_truthy(negative))) {
		rounded = (-rounded);
	}
	result->valid = static_cast<bool_t>(true);
	result->amount = rounded;
	return result;
}

shared_p<AmountResult> MoneyRules::prorate(int_t<> amount, int_t<> numerator, int_t<> denominator) {
	SCPP_CALL_DEPTH_GUARD("MoneyRules::prorate", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/money.phs", 76);
	shared_p<AmountResult> result = create<AmountResult>();
	if (static_cast<bool>((((!MoneyRules::accepts(amount)) || (numerator < static_cast<int_t<> >(0))) || (denominator <= static_cast<int_t<> >(0))))) {
		result->reason = string_t("amount.ratio");
		return result;
	}
	if (static_cast<bool>(((numerator > denominator) || (denominator > static_cast<int_t<> >(1000000))))) {
		result->reason = string_t("amount.ratio");
		return result;
	}
	int_t<> magnitude = required_cast<int_t<>>(amount);
	if (static_cast<bool>((amount < static_cast<int_t<> >(0)))) {
		magnitude = (-amount);
	}
	int_t<> scaled = required_cast<int_t<>>((magnitude * numerator));
	int_t<> remainder = required_cast<int_t<>>((scaled % denominator));
	int_t<> rounded = required_cast<int_t<>>(cast<int_t<>>(((scaled - remainder) / denominator)));
	if (static_cast<bool>(php::condition_truthy(((remainder * static_cast<int_t<> >(2)) >= denominator)))) {
		rounded += static_cast<int_t<> >(1);
	}
	if (static_cast<bool>((amount < static_cast<int_t<> >(0)))) {
		rounded = (-rounded);
	}
	result->valid = static_cast<bool_t>(true);
	result->amount = rounded;
	return result;
}

}
