#include "purchasing.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t SupplierBill::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SupplierBill::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t PayableRecord::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == PayableRecord::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

I PayableRecord::balance() {
	SCPP_CALL_DEPTH_GUARD("PayableRecord::balance", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 28);
	return ((((this->net_amount + this->tax_amount) - this->paid_amount) - this->credited_net) - this->credited_tax);
}

bool_t SupplierRecord::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SupplierRecord::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t PaymentProposalRow::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == PaymentProposalRow::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t PaymentProposal::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == PaymentProposal::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t PurchasingBook::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == PurchasingBook::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AccountingCheck PurchasingBook::register_supplier(const string_t& code, const string_t& name, I limit) {
	SCPP_CALL_DEPTH_GUARD("PurchasingBook::register_supplier", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 58);
	if (static_cast<bool>((((php::identical(code, string_t("")) || php::identical(name, string_t(""))) || (limit < static_cast<I >(0))) || (!MoneyRules::accepts(limit))))) {
		return accounting_failure(string_t("supplier.invalid"), string_t("Supplier identity and bounded limit are required"));
	}
	if (static_cast<bool>(php::condition_truthy(php::isset(this->suppliers, code)))) {
		return accounting_failure(string_t("supplier.duplicate"), code);
	}
	P_SupplierRecord supplier = create_SupplierRecord();
	supplier->code = code;
	supplier->name = name;
	supplier->approved_limit = limit;
	this->suppliers[convert_string_t(code)] = supplier;
	return accounting_success();
}

P_AccountingCheck PurchasingBook::hold_supplier(const string_t& code, bool_t hold) {
	SCPP_CALL_DEPTH_GUARD("PurchasingBook::hold_supplier", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 73);
	if (static_cast<bool>((!php::isset(this->suppliers, code)))) {
		return accounting_failure(string_t("supplier.missing"), code);
	}
	P_SupplierRecord supplier = this->suppliers.at(convert_string_t(code));
	supplier->on_hold = hold;
	return accounting_success();
}

string_t PurchasingBook::bill_key(const string_t& supplier_code, const string_t& number) {
	SCPP_CALL_DEPTH_GUARD("PurchasingBook::bill_key", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 82);
	return (convert_string_t(convert_string_t(str::length(supplier_code))) + string_t(":") + convert_string_t(supplier_code) + string_t(":") + convert_string_t(number));
}

I PurchasingBook::supplier_balance(const string_t& supplier_code) {
	SCPP_CALL_DEPTH_GUARD("PurchasingBook::supplier_balance", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 87);
	I total = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_89 = this->bill_order;
	for (auto __scpp_foreach_entry_89 : foreach_range(__scpp_foreach_source_89)) {
		auto key = __scpp_foreach_entry_89.value_copy();
		P_PayableRecord bill = this->bills.at(convert_string_t(key));
		if (static_cast<bool>(php::identical(bill->supplier_code, supplier_code))) {
			total += bill->balance();
		}
	}
	return total;
}

P_AccountingCheck PurchasingBook::record_bill(P_GeneralLedger ledger, P_SupplierBill draft) {
	SCPP_CALL_DEPTH_GUARD("PurchasingBook::record_bill", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 98);
	if (static_cast<bool>((php::identical(draft->number, string_t("")) || (!php::isset(this->suppliers, draft->supplier_code))))) {
		return accounting_failure(string_t("bill.identity"), string_t("Bill number or registered supplier is missing"));
	}
	P_SupplierRecord supplier = this->suppliers.at(convert_string_t(draft->supplier_code));
	if (static_cast<bool>(php::condition_truthy(supplier->on_hold))) {
		return accounting_failure(string_t("supplier.hold"), supplier->code);
	}
	string_t key = convert_string_t(this->bill_key(draft->supplier_code, draft->number));
	if (static_cast<bool>(php::condition_truthy(php::isset(this->bills, key)))) {
		return accounting_failure(string_t("bill.duplicate"), key);
	}
	if (static_cast<bool>(((((draft->net_amount <= static_cast<I >(0)) || (draft->tax_amount < static_cast<I >(0))) || (draft->due_day < static_cast<I >(0))) || (draft->due_day > static_cast<I >(36524))))) {
		return accounting_failure(string_t("bill.amount"), string_t("Bill amounts or due date are invalid"));
	}
	P_AmountResult gross = MoneyRules::add(draft->net_amount, draft->tax_amount);
	if (static_cast<bool>((!gross->valid))) {
		return accounting_failure(gross->reason, key);
	}
	if (static_cast<bool>(((this->supplier_balance(supplier->code) + gross->amount) > supplier->approved_limit))) {
		return accounting_failure(string_t("supplier.limit"), supplier->code);
	}
	string_t charge_account = convert_string_t(string_t("operating-expense"));
	if (static_cast<bool>(php::identical(draft->charge_kind, PurchaseChargeKind::ReceivedGoods))) {
		charge_account = string_t("goods-received");
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("bill:") + convert_string_t(key));
	entry->origin = string_t("purchasing");
	entry->period = ledger->current_period();
	entry->add_debit(charge_account, draft->net_amount, draft->number);
	if (static_cast<bool>((draft->tax_amount > static_cast<I >(0)))) {
		entry->add_debit(string_t("input-tax"), draft->tax_amount, draft->number);
	}
	entry->add_credit(string_t("payables"), gross->amount, draft->number);
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>((!check->accepted))) {
		return check;
	}
	P_PayableRecord record = create_PayableRecord();
	record->key = key;
	record->supplier_code = draft->supplier_code;
	record->number = draft->number;
	record->charge_account = charge_account;
	record->period = entry->period;
	record->net_amount = draft->net_amount;
	record->tax_amount = draft->tax_amount;
	record->due_day = draft->due_day;
	this->bills[convert_string_t(key)] = record;
	(void) this->bill_order.push_back(key);
	return accounting_success();
}

P_AccountingCheck PurchasingBook::pay(P_GeneralLedger ledger, const string_t& key, const string_t& reference, I amount) {
	SCPP_CALL_DEPTH_GUARD("PurchasingBook::pay", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 151);
	if (static_cast<bool>((!php::isset(this->bills, key)))) {
		return accounting_failure(string_t("bill.missing"), key);
	}
	if (static_cast<bool>(php::identical(reference, string_t("")))) {
		return accounting_failure(string_t("payment.reference"), string_t("Payment reference is required"));
	}
	P_PayableRecord bill = this->bills.at(convert_string_t(key));
	P_SupplierRecord supplier = this->suppliers.at(convert_string_t(bill->supplier_code));
	if (static_cast<bool>(php::condition_truthy(supplier->on_hold))) {
		return accounting_failure(string_t("supplier.hold"), supplier->code);
	}
	if (static_cast<bool>(((amount <= static_cast<I >(0)) || (amount > bill->balance())))) {
		return accounting_failure(string_t("payment.amount"), string_t("Payment exceeds the open bill balance"));
	}
	if (static_cast<bool>((amount > ledger->balance_of(string_t("bank"))))) {
		return accounting_failure(string_t("payment.funds"), string_t("Bank balance cannot fund payment"));
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("supplier-payment:") + convert_string_t(reference));
	entry->origin = string_t("purchasing");
	entry->period = ledger->current_period();
	entry->add_debit(string_t("payables"), amount, bill->number);
	entry->add_credit(string_t("bank"), amount, bill->number);
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>(php::condition_truthy(check->accepted))) {
		bill->paid_amount += amount;
	}
	return check;
}

P_AccountingCheck PurchasingBook::credit(P_GeneralLedger ledger, const string_t& key, const string_t& reference, I net, I tax) {
	SCPP_CALL_DEPTH_GUARD("PurchasingBook::credit", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 182);
	if (static_cast<bool>(((!php::isset(this->bills, key)) || php::identical(reference, string_t(""))))) {
		return accounting_failure(string_t("credit.identity"), string_t("Bill and credit reference are required"));
	}
	P_PayableRecord bill = this->bills.at(convert_string_t(key));
	if (static_cast<bool>((((net < static_cast<I >(0)) || (tax < static_cast<I >(0))) || (php::identical(net, static_cast<I >(0)) && php::identical(tax, static_cast<I >(0)))))) {
		return accounting_failure(string_t("credit.amount"), string_t("Credit must contain a positive amount"));
	}
	if (static_cast<bool>(((net > (bill->net_amount - bill->credited_net)) || (tax > (bill->tax_amount - bill->credited_tax))))) {
		return accounting_failure(string_t("credit.components"), string_t("Credit exceeds original net or tax component"));
	}
	P_AmountResult gross = MoneyRules::add(net, tax);
	if (static_cast<bool>(((!gross->valid) || (gross->amount > bill->balance())))) {
		return accounting_failure(string_t("credit.balance"), string_t("Credit exceeds remaining bill balance"));
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("supplier-credit:") + convert_string_t(reference));
	entry->origin = string_t("purchasing");
	entry->period = ledger->current_period();
	entry->add_debit(string_t("payables"), gross->amount, reference);
	if (static_cast<bool>((net > static_cast<I >(0)))) {
		entry->add_credit(bill->charge_account, net, reference);
	}
	if (static_cast<bool>((tax > static_cast<I >(0)))) {
		entry->add_credit(string_t("input-tax"), tax, reference);
	}
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>(php::condition_truthy(check->accepted))) {
		bill->credited_net += net;
		bill->credited_tax += tax;
	}
	return check;
}

P_PaymentProposal PurchasingBook::propose_payments(I through_day, I budget) {
	SCPP_CALL_DEPTH_GUARD("PurchasingBook::propose_payments", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 216);
	P_PaymentProposal proposal = create_PaymentProposal();
	if (static_cast<bool>(((budget <= static_cast<I >(0)) || (!MoneyRules::accepts(budget))))) {
		return proposal;
	}
	proposal->remaining_budget = budget;
	H_bool_t_string_t selected = {};
	while (static_cast<bool>(php::condition_truthy(static_cast<bool_t>(true)))) {
		string_t best_key = convert_string_t(string_t(""));
		I best_day = convert_I(static_cast<I >(36525));
		auto& __scpp_foreach_source_227 = this->bill_order;
		for (auto __scpp_foreach_entry_227 : foreach_range(__scpp_foreach_source_227)) {
			auto key = __scpp_foreach_entry_227.value_copy();
			if (static_cast<bool>(php::condition_truthy(php::isset(selected, key)))) {
				continue;
			}
			P_PayableRecord bill = this->bills.at(convert_string_t(key));
			P_SupplierRecord supplier = this->suppliers.at(convert_string_t(bill->supplier_code));
			if (static_cast<bool>(((supplier->on_hold || (bill->balance() <= static_cast<I >(0))) || (bill->due_day > through_day)))) {
				continue;
			}
			if (static_cast<bool>((bill->due_day < best_day))) {
				best_key = key;
				best_day = bill->due_day;
			}
		}
		if (static_cast<bool>((php::identical(best_key, string_t("")) || php::identical(proposal->remaining_budget, static_cast<I >(0))))) {
			break;
		}
		selected[convert_string_t(best_key)] = static_cast<bool_t>(true);
		P_PayableRecord bill = this->bills.at(convert_string_t(best_key));
		P_PaymentProposalRow row = create_PaymentProposalRow();
		row->bill_key = best_key;
		row->supplier_code = bill->supplier_code;
		row->due_day = bill->due_day;
		row->amount = bill->balance();
		if (static_cast<bool>((row->amount > proposal->remaining_budget))) {
			row->amount = proposal->remaining_budget;
		}
		(void) proposal->rows.push_back(row);
		proposal->total += row->amount;
		proposal->remaining_budget -= row->amount;
	}
	return proposal;
}

I PurchasingBook::total_balance() {
	SCPP_CALL_DEPTH_GUARD("PurchasingBook::total_balance", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 261);
	I total = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_263 = this->bill_order;
	for (auto __scpp_foreach_entry_263 : foreach_range(__scpp_foreach_source_263)) {
		auto key = __scpp_foreach_entry_263.value_copy();
		P_PayableRecord bill = this->bills.at(convert_string_t(key));
		total += bill->balance();
	}
	return total;
}

P_AccountingCheck PurchasingBook::reconcile(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("PurchasingBook::reconcile", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/purchasing.phs", 270);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(this->total_balance(), (-ledger->balance_of(string_t("payables"))))))) {
		return accounting_failure(string_t("payables.control"), string_t("Supplier balances do not reconcile with general ledger"));
	}
	return accounting_success();
}

}

