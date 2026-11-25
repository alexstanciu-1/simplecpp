#include "stock.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t StockLot::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == StockLot::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t StockItem::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == StockItem::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t StockReservation::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == StockReservation::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t StockIssueResult::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == StockIssueResult::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t InventoryBook::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == InventoryBook::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

P_AccountingCheck InventoryBook::register_item(const string_t& sku, const string_t& description) {
	SCPP_CALL_DEPTH_GUARD("InventoryBook::register_item", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/stock.phs", 36);
	if (static_cast<bool>((php::identical(sku, string_t("")) || php::identical(description, string_t(""))))) {
		return accounting_failure(string_t("stock.identity"), string_t("SKU and description are required"));
	}
	if (static_cast<bool>(php::condition_truthy(php::isset(this->items, sku)))) {
		return accounting_failure(string_t("stock.duplicate"), sku);
	}
	P_StockItem item = create_StockItem();
	item->sku = sku;
	item->description = description;
	this->items[convert_string_t(sku)] = item;
	(void) this->item_order.push_back(sku);
	return accounting_success();
}

P_AccountingCheck InventoryBook::receive(P_GeneralLedger ledger, const string_t& reference, const string_t& sku, I quantity, I unit_cost) {
	SCPP_CALL_DEPTH_GUARD("InventoryBook::receive", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/stock.phs", 51);
	if (static_cast<bool>((!php::isset(this->items, sku)))) {
		return accounting_failure(string_t("stock.missing"), sku);
	}
	if (static_cast<bool>((php::identical(reference, string_t("")) || php::isset(this->movement_references, reference)))) {
		return accounting_failure(string_t("stock.reference"), reference);
	}
	if (static_cast<bool>(((quantity <= static_cast<I >(0)) || (unit_cost <= static_cast<I >(0))))) {
		return accounting_failure(string_t("stock.receipt"), string_t("Receipt quantity and unit cost must be positive"));
	}
	P_AmountResult cost = MoneyRules::multiply(unit_cost, quantity);
	if (static_cast<bool>((!cost->valid))) {
		return accounting_failure(cost->reason, string_t("Receipt cost exceeds workload limits"));
	}
	P_StockItem item = this->items.at(convert_string_t(sku));
	if (static_cast<bool>(((item->on_hand + quantity) > static_cast<I >(1000000)))) {
		return accounting_failure(string_t("stock.quantity-limit"), sku);
	}
	P_AmountResult new_value = MoneyRules::add(item->carrying_value, cost->amount);
	if (static_cast<bool>((!new_value->valid))) {
		return accounting_failure(new_value->reason, sku);
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("stock-receipt:") + convert_string_t(reference));
	entry->origin = string_t("inventory");
	entry->period = ledger->current_period();
	entry->add_debit(string_t("inventory"), cost->amount, sku);
	entry->add_credit(string_t("goods-received"), cost->amount, sku);
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>((!check->accepted))) {
		return check;
	}
	P_StockLot lot = create_StockLot();
	lot->reference = reference;
	lot->quantity = quantity;
	lot->unit_cost = unit_cost;
	(void) item->lots.push_back(lot);
	item->on_hand += quantity;
	item->carrying_value = new_value->amount;
	this->movement_references[convert_string_t(reference)] = static_cast<bool_t>(true);
	return accounting_success();
}

P_AccountingCheck InventoryBook::reserve(const string_t& reference, const string_t& sku, I quantity) {
	SCPP_CALL_DEPTH_GUARD("InventoryBook::reserve", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/stock.phs", 94);
	if (static_cast<bool>((php::identical(reference, string_t("")) || php::isset(this->reservations, reference)))) {
		return accounting_failure(string_t("reservation.reference"), reference);
	}
	if (static_cast<bool>((!php::isset(this->items, sku)))) {
		return accounting_failure(string_t("stock.missing"), sku);
	}
	P_StockItem item = this->items.at(convert_string_t(sku));
	if (static_cast<bool>(((quantity <= static_cast<I >(0)) || (quantity > (item->on_hand - item->reserved))))) {
		return accounting_failure(string_t("reservation.unavailable"), sku);
	}
	P_StockReservation reservation = create_StockReservation();
	reservation->reference = reference;
	reservation->sku = sku;
	reservation->quantity = quantity;
	this->reservations[convert_string_t(reference)] = reservation;
	item->reserved += quantity;
	return accounting_success();
}

P_AccountingCheck InventoryBook::release(const string_t& reference) {
	SCPP_CALL_DEPTH_GUARD("InventoryBook::release", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/stock.phs", 114);
	if (static_cast<bool>((!php::isset(this->reservations, reference)))) {
		return accounting_failure(string_t("reservation.missing"), reference);
	}
	P_StockReservation reservation = this->reservations.at(convert_string_t(reference));
	if (static_cast<bool>(php::condition_truthy(reservation->released))) {
		return accounting_failure(string_t("reservation.released"), reference);
	}
	P_StockItem item = this->items.at(convert_string_t(reservation->sku));
	item->reserved -= reservation->quantity;
	reservation->released = static_cast<bool_t>(true);
	return accounting_success();
}

P_StockIssueResult InventoryBook::quote_issue(const string_t& sku, I quantity) {
	SCPP_CALL_DEPTH_GUARD("InventoryBook::quote_issue", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/stock.phs", 128);
	P_StockIssueResult result = create_StockIssueResult();
	if (static_cast<bool>((!php::isset(this->items, sku)))) {
		result->code = string_t("stock.missing");
		return result;
	}
	P_StockItem item = this->items.at(convert_string_t(sku));
	if (static_cast<bool>(((quantity <= static_cast<I >(0)) || (quantity > (item->on_hand - item->reserved))))) {
		result->code = string_t("stock.unavailable");
		return result;
	}
	I remaining = convert_I(quantity);
	auto __scpp_foreach_source_140 = item->lots;
	for (auto __scpp_foreach_entry_140 : foreach_range(__scpp_foreach_source_140)) {
		auto lot = __scpp_foreach_entry_140.value_copy();
		if (static_cast<bool>(php::identical(remaining, static_cast<I >(0)))) {
			break;
		}
		I used = convert_I(lot->quantity);
		if (static_cast<bool>((used > remaining))) {
			used = remaining;
		}
		result->cost += (used * lot->unit_cost);
		remaining -= used;
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(remaining, static_cast<I >(0))))) {
		result->code = string_t("stock.lot-mismatch");
		return result;
	}
	result->quantity = quantity;
	result->accepted = static_cast<bool_t>(true);
	return result;
}

P_StockIssueResult InventoryBook::issue(P_GeneralLedger ledger, const string_t& reference, const string_t& sku, I quantity) {
	SCPP_CALL_DEPTH_GUARD("InventoryBook::issue", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/stock.phs", 160);
	P_StockIssueResult result = this->quote_issue(sku, quantity);
	if (static_cast<bool>((!result->accepted))) {
		return result;
	}
	if (static_cast<bool>((php::identical(reference, string_t("")) || php::isset(this->movement_references, reference)))) {
		result->accepted = static_cast<bool_t>(false);
		result->code = string_t("stock.reference");
		return result;
	}
	P_JournalEntry entry = create_JournalEntry();
	entry->reference = (string_t("stock-issue:") + convert_string_t(reference));
	entry->origin = string_t("inventory");
	entry->period = ledger->current_period();
	entry->add_debit(string_t("cost-of-sales"), result->cost, sku);
	entry->add_credit(string_t("inventory"), result->cost, sku);
	P_AccountingCheck check = ledger->post(entry);
	if (static_cast<bool>((!check->accepted))) {
		result->accepted = static_cast<bool_t>(false);
		result->code = check->code;
		return result;
	}
	P_StockItem item = this->items.at(convert_string_t(sku));
	I remaining = convert_I(quantity);
	for (auto index = static_cast<I >(0); static_cast<bool>((index < php::count(item->lots))); (index++)) {
		if (static_cast<bool>(php::identical(remaining, static_cast<I >(0)))) {
			break;
		}
		P_StockLot lot = item->lots.at(index);
		I used = convert_I(lot->quantity);
		if (static_cast<bool>((used > remaining))) {
			used = remaining;
		}
		lot->quantity -= used;
		remaining -= used;
	}
	item->on_hand -= quantity;
	item->carrying_value -= result->cost;
	this->movement_references[convert_string_t(reference)] = static_cast<bool_t>(true);
	return result;
}

I InventoryBook::quantity_of(const string_t& sku) {
	SCPP_CALL_DEPTH_GUARD("InventoryBook::quantity_of", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/stock.phs", 203);
	if (static_cast<bool>((!php::isset(this->items, sku)))) {
		return static_cast<I >(0);
	}
	P_StockItem item = this->items.at(convert_string_t(sku));
	return item->on_hand;
}

I InventoryBook::available_of(const string_t& sku) {
	SCPP_CALL_DEPTH_GUARD("InventoryBook::available_of", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/stock.phs", 211);
	if (static_cast<bool>((!php::isset(this->items, sku)))) {
		return static_cast<I >(0);
	}
	P_StockItem item = this->items.at(convert_string_t(sku));
	return (item->on_hand - item->reserved);
}

I InventoryBook::total_value() {
	SCPP_CALL_DEPTH_GUARD("InventoryBook::total_value", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/stock.phs", 219);
	I total = convert_I(static_cast<I >(0));
	auto& __scpp_foreach_source_221 = this->item_order;
	for (auto __scpp_foreach_entry_221 : foreach_range(__scpp_foreach_source_221)) {
		auto sku = __scpp_foreach_entry_221.value_copy();
		P_StockItem item = this->items.at(convert_string_t(sku));
		total += item->carrying_value;
	}
	return total;
}

P_AccountingCheck InventoryBook::reconcile(P_GeneralLedger ledger) {
	SCPP_CALL_DEPTH_GUARD("InventoryBook::reconcile", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/src/stock.phs", 228);
	auto& __scpp_foreach_source_229 = this->item_order;
	for (auto __scpp_foreach_entry_229 : foreach_range(__scpp_foreach_source_229)) {
		auto sku = __scpp_foreach_entry_229.value_copy();
		P_StockItem item = this->items.at(convert_string_t(sku));
		I quantity = convert_I(static_cast<I >(0));
		I value = convert_I(static_cast<I >(0));
		auto __scpp_foreach_source_233 = item->lots;
		for (auto __scpp_foreach_entry_233 : foreach_range(__scpp_foreach_source_233)) {
			auto lot = __scpp_foreach_entry_233.value_copy();
			if (static_cast<bool>(((lot->quantity < static_cast<I >(0)) || (lot->unit_cost <= static_cast<I >(0))))) {
				return accounting_failure(string_t("stock.corrupt-lot"), sku);
			}
			quantity += lot->quantity;
			value += (lot->quantity * lot->unit_cost);
		}
		if (static_cast<bool>((php::not_identical(quantity, item->on_hand) || php::not_identical(value, item->carrying_value)))) {
			return accounting_failure(string_t("stock.control"), sku);
		}
		if (static_cast<bool>(((item->reserved < static_cast<I >(0)) || (item->reserved > item->on_hand)))) {
			return accounting_failure(string_t("stock.reserved-control"), sku);
		}
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(this->total_value(), ledger->balance_of(string_t("inventory")))))) {
		return accounting_failure(string_t("stock.ledger-control"), string_t("Inventory does not reconcile with general ledger"));
	}
	return accounting_success();
}

}

