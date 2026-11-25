#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
class GeneralLedger;
class SalesInvoice;
class SalesService;

class SalesInvoice : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t number = string_t("");
	I period = static_cast<I >(1);
	I net_amount = static_cast<I >(0);
	I tax_amount = static_cast<I >(0);
	I paid_amount = static_cast<I >(0);
	bool_t posted = bool_t(static_cast<bool_t>(false));
	virtual I gross_amount();
	virtual I outstanding();
};

class SalesService : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
private:
	H_P_SalesInvoice_string_t issued_invoices = [&]() -> H_P_SalesInvoice_string_t {
	H_P_SalesInvoice_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
public:
	virtual P_AccountingCheck issue(P_GeneralLedger ledger, P_SalesInvoice invoice);
	virtual P_AccountingCheck receive(P_GeneralLedger ledger, P_SalesInvoice invoice, const string_t& receipt, I amount);
	virtual I total_outstanding();
	virtual P_AccountingCheck reconcile(P_GeneralLedger ledger);
};

}

