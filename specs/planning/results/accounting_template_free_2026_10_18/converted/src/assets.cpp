#include "assets.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t DepreciationPolicy::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == DepreciationPolicy::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	else if (__scpp_token == StraightLineDepreciation::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	else if (__scpp_token == DecliningBalanceDepreciation::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t DepreciationPolicy::valid_inputs(I cost, I residual, I life, I elapsed, I accumulated) {
	SCPP_CALL_DEPTH_GUARD("DepreciationPolicy::valid_inputs", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/assets.phs", 4);
	if (static_cast<bool>(((((cost <= static_cast<I >(0)) || (!MoneyRules::accepts(cost))) || (residual < static_cast<I >(0))) || (residual > cost)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>(((((life <= static_cast<I >(0)) || (life > static_cast<I >(1200))) || (elapsed < static_cast<I >(0))) || (elapsed >= life)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return ((accumulated >= static_cast<I >(0)) && (accumulated <= (cost - residual)));
}

bool_t StraightLineDepreciation::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == StraightLineDepreciation::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AmountResult StraightLineDepreciation::charge(I cost, I residual, I life, I elapsed, I accumulated) {
	SCPP_CALL_DEPTH_GUARD("StraightLineDepreciation::charge", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/assets.phs", 16);
	P_AmountResult result = create_AmountResult();
	if (static_cast<bool>((!this->valid_inputs(cost, residual, life, elapsed, accumulated)))) {
		result->reason = string_t("depreciation.inputs");
		return result;
	}
	P_AmountResult target = MoneyRules::prorate((cost - residual), (elapsed + static_cast<I >(1)), life);
	if (static_cast<bool>(((!target->valid) || (target->amount < accumulated)))) {
		result->reason = string_t("depreciation.schedule");
		return result;
	}
	result->valid = static_cast<bool_t>(true);
	result->amount = (target->amount - accumulated);
	return result;
}

bool_t DecliningBalanceDepreciation::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == DecliningBalanceDepreciation::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AmountResult DecliningBalanceDepreciation::charge(I cost, I residual, I life, I elapsed, I accumulated) {
	SCPP_CALL_DEPTH_GUARD("DecliningBalanceDepreciation::charge", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/assets.phs", 36);
	P_AmountResult result = create_AmountResult();
	if (static_cast<bool>((!this->valid_inputs(cost, residual, life, elapsed, accumulated)))) {
		result->reason = string_t("depreciation.inputs");
		return result;
	}
	if (static_cast<bool>(((this->basis_points < static_cast<I >(1)) || (this->basis_points > static_cast<I >(10000))))) {
		result->reason = string_t("depreciation.rate");
		return result;
	}
	I remaining = convert_I(((cost - residual) - accumulated));
	if (static_cast<bool>(php::identical((elapsed + static_cast<I >(1)), life))) {
		result->valid = static_cast<bool_t>(true);
		result->amount = remaining;
		return result;
	}
	P_AmountResult rated = MoneyRules::rate((cost - accumulated), this->basis_points);
	if (static_cast<bool>((!rated->valid))) {
		return rated;
	}
	result->valid = static_cast<bool_t>(true);
	result->amount = rated->amount;
	if (static_cast<bool>((result->amount > remaining))) {
		result->amount = remaining;
	}
	return result;
}

bool_t FixedAsset::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == FixedAsset::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

I FixedAsset::carrying_amount() {
	SCPP_CALL_DEPTH_GUARD("FixedAsset::carrying_amount", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/assets.phs", 77);
	if (static_cast<bool>(php::condition_truthy(this->disposed))) {
		return static_cast<I >(0);
	}
	return (this->cost - this->accumulated);
}

bool_t AssetRegister::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == AssetRegister::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AccountingCheck AssetRegister::acquire(P_GeneralLedger ledger, const string_t& code, const string_t& description, I cost, I residual, I life, P_DepreciationPolicy policy) {
	SCPP_CALL_DEPTH_GUARD("AssetRegister::acquire", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/assets.phs", 89);
	if (static_cast<bool>(((php::identical(code, string_t("")) || php::identical(description, string_t(""))) || php::isset(this->assets, code)))) {
		return accounting_failure(string_t("asset.identity"), string_t("Asset code and description must be new and nonempty"));
	}
	if (static_cast<bool>((!policy->valid_inputs(cost, residual, life, static_cast<I >(0), static_cast<I >(0))))) {
		return accounting_failure(string_t("asset.amounts"), string_t("Asset cost, residual value, or life is invalid"));
	}
	P_AmountResult preview = policy->charge(cost, residual, life, static_cast<I >(0), static_cast<I >(0));
	if (static_cast<bool>((!preview->valid))) {
		return accounting_failure(preview->reason, string_t("Depreciation policy rejected acquisition"));
	}
	if (static_cast<bool>((cost > ledger->balance_of(string_t("bank"))))) {
		return accounting_failure(string_t("asset.funds"), string_t("Bank cannot fund asset acquisition"));
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("asset-acquisition:") + convert_string_t(code));
	entry->origin = string_t("fixed-assets");
	entry->period = ledger->current_period();
	entry->add_debit(string_t("fixed-assets"), cost, description);
	entry->add_credit(string_t("bank"), cost, description);
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>((!check->accepted))) {
		return check;
	}
	P_FixedAsset asset = create_FixedAsset();
	asset->code = code;
	asset->description = description;
	asset->cost = cost;
	asset->residual = residual;
	asset->life = life;
	asset->policy = policy;
	this->assets[convert_string_t(code)] = asset;
	(void) this->asset_order.push_back(code);
	return accounting_success();
}

P_AccountingCheck AssetRegister::depreciate(P_GeneralLedger ledger, const string_t& code) {
	SCPP_CALL_DEPTH_GUARD("AssetRegister::depreciate", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/assets.phs", 125);
	if (static_cast<bool>((!php::isset(this->assets, code)))) {
		return accounting_failure(string_t("asset.missing"), code);
	}
	P_FixedAsset asset = this->assets.at(convert_string_t(code));
	if (static_cast<bool>((asset->disposed || (asset->elapsed >= asset->life)))) {
		return accounting_failure(string_t("asset.finished"), code);
	}
	I period = convert_I(ledger->current_period());
	if (static_cast<bool>(php::identical(asset->last_depreciation_period, period))) {
		return accounting_failure(string_t("asset.period-duplicate"), code);
	}
	P_AmountResult charge = asset->policy->charge(asset->cost, asset->residual, asset->life, asset->elapsed, asset->accumulated);
	if (static_cast<bool>((((!charge->valid) || (charge->amount < static_cast<I >(0))) || (charge->amount > ((asset->cost - asset->residual) - asset->accumulated))))) {
		return accounting_failure(string_t("asset.charge"), string_t("Depreciation policy returned an invalid charge"));
	}
	if (static_cast<bool>((charge->amount > static_cast<I >(0)))) {
		P_JournalEntry entry = create_JournalEntry();
		entry->reference = (string_t("depreciation:") + convert_string_t(convert_string_t(period)) + string_t(":") + convert_string_t(code));
		entry->origin = string_t("fixed-assets");
		entry->period = period;
		entry->add_debit(string_t("depreciation-expense"), charge->amount, code);
		entry->add_credit(string_t("accumulated-depreciation"), charge->amount, code);
		P_AccountingCheck check = ledger->post(entry);
		if (static_cast<bool>((!check->accepted))) {
			return check;
		}
	}
	asset->accumulated += charge->amount;
	asset->elapsed += static_cast<I >(1);
	asset->last_depreciation_period = period;
	return accounting_success();
}

P_AccountingCheck AssetRegister::dispose(P_GeneralLedger ledger, const string_t& code, const string_t& reference, I proceeds) {
	SCPP_CALL_DEPTH_GUARD("AssetRegister::dispose", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/assets.phs", 159);
	if (static_cast<bool>(((!php::isset(this->assets, code)) || php::identical(reference, string_t(""))))) {
		return accounting_failure(string_t("asset.disposal-identity"), string_t("Asset and disposal reference are required"));
	}
	P_FixedAsset asset = this->assets.at(convert_string_t(code));
	if (static_cast<bool>(php::condition_truthy(asset->disposed))) {
		return accounting_failure(string_t("asset.disposed"), code);
	}
	if (static_cast<bool>(((proceeds < static_cast<I >(0)) || (!MoneyRules::accepts(proceeds))))) {
		return accounting_failure(string_t("asset.proceeds"), string_t("Disposal proceeds must be nonnegative and bounded"));
	}
	I carrying = convert_I(asset->carrying_amount());
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("asset-disposal:") + convert_string_t(reference));
	entry->origin = string_t("fixed-assets");
	entry->period = ledger->current_period();
	if (static_cast<bool>((proceeds > static_cast<I >(0)))) {
		entry->add_debit(string_t("bank"), proceeds, code);
	}
	if (static_cast<bool>((asset->accumulated > static_cast<I >(0)))) {
		entry->add_debit(string_t("accumulated-depreciation"), asset->accumulated, code);
	}
	entry->add_credit(string_t("fixed-assets"), asset->cost, code);
	if (static_cast<bool>((proceeds < carrying))) {
		entry->add_debit(string_t("asset-loss"), (carrying - proceeds), code);
	}
	else {
		if (static_cast<bool>((proceeds > carrying))) {
			entry->add_credit(string_t("asset-gain"), (proceeds - carrying), code);
		}
	}
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>(php::condition_truthy(check->accepted))) {
		asset->disposed = static_cast<bool_t>(true);
	}
	return check;
}

I AssetRegister::total_cost() {
	SCPP_CALL_DEPTH_GUARD("AssetRegister::total_cost", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/assets.phs", 194);
	I total = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_196 = this->asset_order;
	for (auto __scpp_foreach_entry_196 : foreach_range(__scpp_foreach_source_196)) {
		auto code = __scpp_foreach_entry_196.value_copy();
		P_FixedAsset asset = this->assets.at(convert_string_t(code));
		if (static_cast<bool>((!asset->disposed))) {
			total += asset->cost;
		}
	}
	return total;
}

I AssetRegister::total_depreciation() {
	SCPP_CALL_DEPTH_GUARD("AssetRegister::total_depreciation", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/assets.phs", 205);
	I total = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_207 = this->asset_order;
	for (auto __scpp_foreach_entry_207 : foreach_range(__scpp_foreach_source_207)) {
		auto code = __scpp_foreach_entry_207.value_copy();
		P_FixedAsset asset = this->assets.at(convert_string_t(code));
		if (static_cast<bool>((!asset->disposed))) {
			total += asset->accumulated;
		}
	}
	return total;
}

P_AccountingCheck AssetRegister::reconcile(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("AssetRegister::reconcile", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/assets.phs", 216);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(this->total_cost(), ledger->balance_of(string_t("fixed-assets")))))) {
		return accounting_failure(string_t("assets.cost-control"), string_t("Asset register cost differs from general ledger"));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(this->total_depreciation(), (-ledger->balance_of(string_t("accumulated-depreciation"))))))) {
		return accounting_failure(string_t("assets.depreciation-control"), string_t("Asset register depreciation differs from general ledger"));
	}
	return accounting_success();
}

}

