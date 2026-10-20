#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
struct AccountingDate;
class AmountResult;
class CalendarRules;
class PaymentTerms;

struct AccountingDate {
	AccountingDate* operator->() { return this; }
	const AccountingDate* operator->() const { return this; }
	I year;
	I month;
	I day;
};

class CalendarRules : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	static bool_t leap_year(I year);
	static I days_in_month(I year, I month);
	static bool_t valid(AccountingDate date);
	static I ordinal(AccountingDate date);
	static AccountingDate from_ordinal(I ordinal);
	static AccountingDate add_days(AccountingDate date, I days);
	static AccountingDate month_end(AccountingDate date);
	static I compare(AccountingDate left, AccountingDate right);
};

enum class PaymentTermKind : int32_t {
	DaysAfterInvoice = 1,
	DaysAfterMonthEnd = 2,
	Immediate = 3
};

class PaymentTerms : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	PaymentTermKind kind = PaymentTermKind::DaysAfterInvoice;
	I days = static_cast<I >(30);
	I discount_days = static_cast<I >(0);
	I discount_basis_points = static_cast<I >(0);
	P_AccountingCheck validate() { return __scpp_return_validate(Tag_P_AccountingCheck{}); }
	virtual P_AccountingCheck __scpp_return_validate(Tag_P_AccountingCheck);
	virtual AccountingDate due_date(AccountingDate invoice_date);
	virtual P_AmountResult settlement_discount(I balance, AccountingDate invoice_date, AccountingDate payment_date);
};

}

