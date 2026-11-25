#include "calendar.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t CalendarRules::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == CalendarRules::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t CalendarRules::leap_year(I year) {
	SCPP_CALL_DEPTH_GUARD("CalendarRules::leap_year", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 8);
	return (php::identical((year % static_cast<I >(400)), static_cast<I >(0)) || (php::identical((year % static_cast<I >(4)), static_cast<I >(0)) && php::not_identical((year % static_cast<I >(100)), static_cast<I >(0))));
}

I CalendarRules::days_in_month(I year, I month) {
	SCPP_CALL_DEPTH_GUARD("CalendarRules::days_in_month", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 12);
	if (static_cast<bool>(((month < static_cast<I >(1)) || (month > static_cast<I >(12))))) {
		return static_cast<I >(0);
	}
	if (static_cast<bool>(php::identical(month, static_cast<I >(2)))) {
		if (static_cast<bool>(php::condition_truthy(CalendarRules::leap_year(year)))) {
			return static_cast<I >(29);
		}
		return static_cast<I >(28);
	}
	if (static_cast<bool>((((php::identical(month, static_cast<I >(4)) || php::identical(month, static_cast<I >(6))) || php::identical(month, static_cast<I >(9))) || php::identical(month, static_cast<I >(11))))) {
		return static_cast<I >(30);
	}
	return static_cast<I >(31);
}

bool_t CalendarRules::valid(AccountingDate date) {
	SCPP_CALL_DEPTH_GUARD("CalendarRules::valid", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 28);
	I year = convert_I(date->year);
	I month = convert_I(date->month);
	I day = convert_I(date->day);
	if (static_cast<bool>(((year < static_cast<I >(2000)) || (year > static_cast<I >(2099))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return ((day >= static_cast<I >(1)) && (day <= CalendarRules::days_in_month(year, month)));
}

I CalendarRules::ordinal(AccountingDate date) {
	SCPP_CALL_DEPTH_GUARD("CalendarRules::ordinal", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 38);
	if (static_cast<bool>((!CalendarRules::valid(date)))) {
		return (-static_cast<I >(1));
	}
	I days = convert_I(static_cast<I >(0));
	for (auto year = static_cast<I >(2000); static_cast<bool>((year < date->year)); (year++)) {
		days += static_cast<I >(365);
		if (static_cast<bool>(php::condition_truthy(CalendarRules::leap_year(year)))) {
			days += static_cast<I >(1);
		}
	}
	for (auto month = static_cast<I >(1); static_cast<bool>((month < date->month)); (month++)) {
		days += CalendarRules::days_in_month(date->year, month);
	}
	return ((days + date->day) - static_cast<I >(1));
}

AccountingDate CalendarRules::from_ordinal(I ordinal) {
	SCPP_CALL_DEPTH_GUARD("CalendarRules::from_ordinal", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 55);
	AccountingDate date = AccountingDate{};
	date->year = static_cast<I >(0);
	date->month = static_cast<I >(0);
	date->day = static_cast<I >(0);
	if (static_cast<bool>(((ordinal < static_cast<I >(0)) || (ordinal > static_cast<I >(36524))))) {
		return date;
	}
	I remaining = convert_I(ordinal);
	I year = convert_I(static_cast<I >(2000));
	while (static_cast<bool>((year <= static_cast<I >(2099)))) {
		I year_days = convert_I(static_cast<I >(365));
		if (static_cast<bool>(php::condition_truthy(CalendarRules::leap_year(year)))) {
			year_days = static_cast<I >(366);
		}
		if (static_cast<bool>((remaining < year_days))) {
			break;
		}
		remaining -= year_days;
		year += static_cast<I >(1);
	}
	I month = convert_I(static_cast<I >(1));
	while (static_cast<bool>((month <= static_cast<I >(12)))) {
		I month_days = convert_I(CalendarRules::days_in_month(year, month));
		if (static_cast<bool>((remaining < month_days))) {
			break;
		}
		remaining -= month_days;
		month += static_cast<I >(1);
	}
	date->year = year;
	date->month = month;
	date->day = (remaining + static_cast<I >(1));
	return date;
}

AccountingDate CalendarRules::add_days(AccountingDate date, I days) {
	SCPP_CALL_DEPTH_GUARD("CalendarRules::add_days", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 91);
	I ordinal = convert_I(CalendarRules::ordinal(date));
	if (static_cast<bool>((((ordinal < static_cast<I >(0)) || (days < (-static_cast<I >(36525)))) || (days > static_cast<I >(36525))))) {
		return CalendarRules::from_ordinal((-static_cast<I >(1)));
	}
	return CalendarRules::from_ordinal((ordinal + days));
}

AccountingDate CalendarRules::month_end(AccountingDate date) {
	SCPP_CALL_DEPTH_GUARD("CalendarRules::month_end", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 99);
	if (static_cast<bool>((!CalendarRules::valid(date)))) {
		return CalendarRules::from_ordinal((-static_cast<I >(1)));
	}
	AccountingDate result = date;
	result->day = CalendarRules::days_in_month(date->year, date->month);
	return result;
}

I CalendarRules::compare(AccountingDate left, AccountingDate right) {
	SCPP_CALL_DEPTH_GUARD("CalendarRules::compare", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 108);
	I left_day = convert_I(CalendarRules::ordinal(left));
	I right_day = convert_I(CalendarRules::ordinal(right));
	if (static_cast<bool>((left_day < right_day))) {
		return (-static_cast<I >(1));
	}
	if (static_cast<bool>((left_day > right_day))) {
		return static_cast<I >(1);
	}
	return static_cast<I >(0);
}

bool_t PaymentTerms::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == PaymentTerms::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AccountingCheck PaymentTerms::__scpp_return_validate(Tag_P_AccountingCheck) {
	SCPP_CALL_DEPTH_GUARD("PaymentTerms::validate", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 133);
	if (static_cast<bool>(((this->days < static_cast<I >(0)) || (this->days > static_cast<I >(365))))) {
		return accounting_failure(string_t("terms.days"), string_t("Credit days must be within one year"));
	}
	if (static_cast<bool>(((this->discount_days < static_cast<I >(0)) || (this->discount_days > this->days)))) {
		return accounting_failure(string_t("terms.discount-days"), string_t("Discount window exceeds credit days"));
	}
	if (static_cast<bool>(((this->discount_basis_points < static_cast<I >(0)) || (this->discount_basis_points > static_cast<I >(10000))))) {
		return accounting_failure(string_t("terms.discount-rate"), string_t("Discount rate is invalid"));
	}
	if (static_cast<bool>((php::identical(this->kind, PaymentTermKind::Immediate) && php::not_identical(this->days, static_cast<I >(0))))) {
		return accounting_failure(string_t("terms.immediate"), string_t("Immediate terms cannot grant credit days"));
	}
	return accounting_success();
}

AccountingDate PaymentTerms::due_date(AccountingDate invoice_date) {
	SCPP_CALL_DEPTH_GUARD("PaymentTerms::due_date", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 149);
	P_AccountingCheck check = this->validate();
	if (static_cast<bool>(((!check->accepted) || (!CalendarRules::valid(invoice_date))))) {
		return CalendarRules::from_ordinal((-static_cast<I >(1)));
	}
	if (static_cast<bool>(php::identical(this->kind, PaymentTermKind::Immediate))) {
		return invoice_date;
	}
	if (static_cast<bool>(php::identical(this->kind, PaymentTermKind::DaysAfterMonthEnd))) {
		return CalendarRules::add_days(CalendarRules::month_end(invoice_date), this->days);
	}
	return CalendarRules::add_days(invoice_date, this->days);
}

P_AmountResult PaymentTerms::settlement_discount(I balance, AccountingDate invoice_date, AccountingDate payment_date) {
	SCPP_CALL_DEPTH_GUARD("PaymentTerms::settlement_discount", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/calendar.phs", 163);
	P_AmountResult result = create_AmountResult();
	P_AccountingCheck check = this->validate();
	if (static_cast<bool>((((!check->accepted) || (balance < static_cast<I >(0))) || (!MoneyRules::accepts(balance))))) {
		result->reason = string_t("terms.invalid");
		return result;
	}
	I issued = convert_I(CalendarRules::ordinal(invoice_date));
	I paid = convert_I(CalendarRules::ordinal(payment_date));
	if (static_cast<bool>(((issued < static_cast<I >(0)) || (paid < issued)))) {
		result->reason = string_t("terms.payment-date");
		return result;
	}
	if (static_cast<bool>(((paid - issued) > this->discount_days))) {
		result->valid = static_cast<bool_t>(true);
		return result;
	}
	return MoneyRules::rate(balance, this->discount_basis_points);
}

}

