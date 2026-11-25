#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
class AmountResult;
class AssetRegister;
class DecliningBalanceDepreciation;
class DepreciationPolicy;
class FixedAsset;
class GeneralLedger;
class StraightLineDepreciation;

class DepreciationPolicy : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	virtual P_AmountResult charge(I cost, I residual, I life, I elapsed, I accumulated) = 0;
	virtual bool_t valid_inputs(I cost, I residual, I life, I elapsed, I accumulated);
};

class StraightLineDepreciation : public DepreciationPolicy, public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	virtual P_AmountResult charge(I cost, I residual, I life, I elapsed, I accumulated);
};

class DecliningBalanceDepreciation : public DepreciationPolicy, public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	I basis_points = static_cast<I >(2000);
	virtual P_AmountResult charge(I cost, I residual, I life, I elapsed, I accumulated);
};

class FixedAsset : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t code = string_t("");
	string_t description = string_t("");
	I cost = static_cast<I >(0);
	I residual = static_cast<I >(0);
	I life = static_cast<I >(0);
	I elapsed = static_cast<I >(0);
	I accumulated = static_cast<I >(0);
	I last_depreciation_period = static_cast<I >(0);
	bool_t disposed = bool_t(static_cast<bool_t>(false));
	P_DepreciationPolicy policy;
	virtual I carrying_amount();
};

class AssetRegister : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
private:
	H_P_FixedAsset_string_t assets = [&]() -> H_P_FixedAsset_string_t {
	H_P_FixedAsset_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	V_string_t asset_order = V_string_t{};
public:
	virtual P_AccountingCheck acquire(P_GeneralLedger ledger, const string_t& code, const string_t& description, I cost, I residual, I life, P_DepreciationPolicy policy);
	virtual P_AccountingCheck depreciate(P_GeneralLedger ledger, const string_t& code);
	virtual P_AccountingCheck dispose(P_GeneralLedger ledger, const string_t& code, const string_t& reference, I proceeds);
	virtual I total_cost();
	virtual I total_depreciation();
	virtual P_AccountingCheck reconcile(P_GeneralLedger ledger);
};

}

