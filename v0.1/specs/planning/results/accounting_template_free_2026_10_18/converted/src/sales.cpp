#include "sales.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t SalesInvoice::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SalesInvoice::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

I SalesInvoice::gross_amount() {
	SCPP_CALL_DEPTH_GUARD("SalesInvoice::gross_amount", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/sales.phs", 9);
	return (this->net_amount + this->tax_amount);
}

I SalesInvoice::outstanding() {
	SCPP_CALL_DEPTH_GUARD("SalesInvoice::outstanding", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/sales.phs", 13);
	return (this->gross_amount() - this->paid_amount);
}

bool_t SalesService::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SalesService::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AccountingCheck SalesService::issue(P_GeneralLedger ledger, P_SalesInvoice invoice) {
	SCPP_CALL_DEPTH_GUARD("SalesService::issue", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/sales.phs", 21);
	if (static_cast<bool>((invoice->posted || php::isset(this->issued_invoices, invoice->number)))) {
		return accounting_failure(string_t("invoice.posted"), invoice->number);
	}
	if (static_cast<bool>((((php::identical(invoice->number, string_t("")) || (invoice->net_amount <= static_cast<I >(0))) || (invoice->tax_amount < static_cast<I >(0))) || php::not_identical(invoice->paid_amount, static_cast<I >(0))))) {
		return accounting_failure(string_t("invoice.invalid"), string_t("Invoice requires a number and valid amounts"));
	}
	P_AmountResult gross = MoneyRules::add(invoice->net_amount, invoice->tax_amount);
	if (static_cast<bool>((!gross->valid))) {
		return accounting_failure(gross->reason, invoice->number);
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("sale:") + convert_string_t(invoice->number));
	entry->origin = string_t("sales");
	entry->period = invoice->period;
	entry->add_debit(string_t("receivables"), invoice->gross_amount(), invoice->number);
	entry->add_credit(string_t("revenue"), invoice->net_amount, invoice->number);
	if (static_cast<bool>((invoice->tax_amount > static_cast<I >(0)))) {
		entry->add_credit(string_t("tax-payable"), invoice->tax_amount, invoice->number);
	}
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>(php::condition_truthy(check->accepted))) {
		invoice->posted = static_cast<bool_t>(true);
		P_SalesInvoice stored = create_SalesInvoice();
		stored->number = invoice->number;
		stored->period = invoice->period;
		stored->net_amount = invoice->net_amount;
		stored->tax_amount = invoice->tax_amount;
		stored->posted = static_cast<bool_t>(true);
		this->issued_invoices[convert_string_t(invoice->number)] = stored;
	}
	return check;
}

P_AccountingCheck SalesService::receive(P_GeneralLedger ledger, P_SalesInvoice invoice, const string_t& receipt, I amount) {
	SCPP_CALL_DEPTH_GUARD("SalesService::receive", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/sales.phs", 55);
	if (static_cast<bool>((!php::isset(this->issued_invoices, invoice->number)))) {
		return accounting_failure(string_t("receipt.draft"), invoice->number);
	}
	P_SalesInvoice stored = this->issued_invoices.at(convert_string_t(invoice->number));
	if (static_cast<bool>(((php::identical(receipt, string_t("")) || (amount <= static_cast<I >(0))) || (amount > stored->outstanding())))) {
		return accounting_failure(string_t("receipt.invalid"), string_t("Receipt exceeds outstanding amount or is invalid"));
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("receipt:") + convert_string_t(receipt));
	entry->origin = string_t("receipts");
	entry->period = ledger->current_period();
	entry->add_debit(string_t("bank"), amount, invoice->number);
	entry->add_credit(string_t("receivables"), amount, invoice->number);
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>(php::condition_truthy(check->accepted))) {
		stored->paid_amount += amount;
		invoice->net_amount = stored->net_amount;
		invoice->tax_amount = stored->tax_amount;
		invoice->paid_amount = stored->paid_amount;
		invoice->posted = static_cast<bool_t>(true);
	}
	return check;
}

I SalesService::total_outstanding() {
	SCPP_CALL_DEPTH_GUARD("SalesService::total_outstanding", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/sales.phs", 80);
	I total = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_82 = this->issued_invoices;
	for (auto __scpp_foreach_entry_82 : foreach_range(__scpp_foreach_source_82)) {
		auto&& number = __scpp_foreach_entry_82.key();
		auto invoice = __scpp_foreach_entry_82.value_copy();
		total += invoice->outstanding();
	}
	return total;
}

P_AccountingCheck SalesService::reconcile(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("SalesService::reconcile", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/sales.phs", 88);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(this->total_outstanding(), ledger->balance_of(string_t("receivables")))))) {
		return accounting_failure(string_t("receivables.control"), string_t("Invoices do not reconcile with general ledger"));
	}
	return accounting_success();
}

}

