#pragma once

#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"
#include "concrete.hpp"

namespace scpp {

class AccountingCheck;
class GeneralLedger;
class InventoryBook;
class StockIssueResult;
class StockItem;
class StockLot;
class StockReservation;

class StockLot : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t reference = string_t("");
	I quantity = static_cast<I >(0);
	I unit_cost = static_cast<I >(0);
};

class StockItem : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t sku = string_t("");
	string_t description = string_t("");
	I on_hand = static_cast<I >(0);
	I reserved = static_cast<I >(0);
	I carrying_value = static_cast<I >(0);
	V_P_StockLot lots = V_P_StockLot{};
};

class StockReservation : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	string_t reference = string_t("");
	string_t sku = string_t("");
	I quantity = static_cast<I >(0);
	bool_t released = bool_t(static_cast<bool_t>(false));
};

class StockIssueResult : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	bool_t accepted = bool_t(static_cast<bool_t>(false));
	string_t code = string_t("");
	I cost = static_cast<I >(0);
	I quantity = static_cast<I >(0);
};

class InventoryBook : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
private:
	H_P_StockItem_string_t items = [&]() -> H_P_StockItem_string_t {
	H_P_StockItem_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	V_string_t item_order = V_string_t{};
	H_P_StockReservation_string_t reservations = [&]() -> H_P_StockReservation_string_t {
	H_P_StockReservation_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
	H_bool_t_string_t movement_references = [&]() -> H_bool_t_string_t {
	H_bool_t_string_t __scpp_hash_value{};
	return __scpp_hash_value;
}();
public:
	virtual P_AccountingCheck register_item(const string_t& sku, const string_t& description);
	virtual P_AccountingCheck receive(P_GeneralLedger ledger, const string_t& reference, const string_t& sku, I quantity, I unit_cost);
	virtual P_AccountingCheck reserve(const string_t& reference, const string_t& sku, I quantity);
	virtual P_AccountingCheck release(const string_t& reference);
	virtual P_StockIssueResult quote_issue(const string_t& sku, I quantity);
	virtual P_StockIssueResult issue(P_GeneralLedger ledger, const string_t& reference, const string_t& sku, I quantity);
	virtual I quantity_of(const string_t& sku);
	virtual I available_of(const string_t& sku);
	virtual I total_value();
	virtual P_AccountingCheck reconcile(P_GeneralLedger ledger);
};

}

