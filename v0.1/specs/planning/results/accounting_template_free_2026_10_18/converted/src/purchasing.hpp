#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
class GeneralLedger;
class PayableRecord;
class PaymentProposal;
class PaymentProposalRow;
class PurchasingBook;
class SupplierBill;
class SupplierRecord;

enum class PurchaseChargeKind : int32_t {
	Expense = 1,
	ReceivedGoods = 2
};

class SupplierBill : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t number = string_t("");
	string_t supplier_code = string_t("");
	PurchaseChargeKind charge_kind = PurchaseChargeKind::Expense;
	I net_amount = static_cast<I >(0);
	I tax_amount = static_cast<I >(0);
	I due_day = static_cast<I >(0);
};

class PayableRecord : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t key = string_t("");
	string_t supplier_code = string_t("");
	string_t number = string_t("");
	string_t charge_account = string_t("");
	I period = static_cast<I >(1);
	I net_amount = static_cast<I >(0);
	I tax_amount = static_cast<I >(0);
	I paid_amount = static_cast<I >(0);
	I credited_net = static_cast<I >(0);
	I credited_tax = static_cast<I >(0);
	I due_day = static_cast<I >(0);
	virtual I balance();
};

class SupplierRecord : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t code = string_t("");
	string_t name = string_t("");
	bool_t on_hold = bool_t(static_cast<bool_t>(false));
	I approved_limit = static_cast<I >(1000000000);
};

class PaymentProposalRow : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t bill_key = string_t("");
	string_t supplier_code = string_t("");
	I due_day = static_cast<I >(0);
	I amount = static_cast<I >(0);
};

class PaymentProposal : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	V_P_PaymentProposalRow rows = V_P_PaymentProposalRow{};
	I total = static_cast<I >(0);
	I remaining_budget = static_cast<I >(0);
};

class PurchasingBook : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
private:
	H_P_SupplierRecord_string_t suppliers = [&]() -> H_P_SupplierRecord_string_t {
	H_P_SupplierRecord_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	H_P_PayableRecord_string_t bills = [&]() -> H_P_PayableRecord_string_t {
	H_P_PayableRecord_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	V_string_t bill_order = V_string_t{};
public:
	virtual P_AccountingCheck register_supplier(const string_t& code, const string_t& name, I limit);
	virtual P_AccountingCheck hold_supplier(const string_t& code, bool_t hold);
	virtual string_t bill_key(const string_t& supplier_code, const string_t& number);
	virtual I supplier_balance(const string_t& supplier_code);
	virtual P_AccountingCheck record_bill(P_GeneralLedger ledger, P_SupplierBill draft);
	virtual P_AccountingCheck pay(P_GeneralLedger ledger, const string_t& key, const string_t& reference, I amount);
	virtual P_AccountingCheck credit(P_GeneralLedger ledger, const string_t& key, const string_t& reference, I net, I tax);
	virtual P_PaymentProposal propose_payments(I through_day, I budget);
	virtual I total_balance();
	virtual P_AccountingCheck reconcile(P_GeneralLedger ledger);
};

}

