#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

I run_accounting_workload() {
	SCPP_CALL_DEPTH_GUARD("run_accounting_workload", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/main.phs", 1);
	P_GeneralLedger ledger = create_GeneralLedger();
	ledger->add_account(string_t("bank"), string_t("Operating bank"), AccountCategory::Asset);
	ledger->add_account(string_t("receivables"), string_t("Trade receivables"), AccountCategory::Asset);
	ledger->add_account(string_t("revenue"), string_t("Sales revenue"), AccountCategory::Revenue);
	ledger->add_account(string_t("tax-payable"), string_t("Output tax"), AccountCategory::Liability);
	P_SalesInvoice invoice = create_SalesInvoice();
	invoice->number = string_t("INV-001");
	invoice->net_amount = static_cast<I >(10000);
	invoice->tax_amount = static_cast<I >(2000);
	P_SalesService sales = create_SalesService();
	P_AccountingCheck issued = sales->issue(ledger, invoice);
	if (static_cast<bool>((!issued->accepted))) {
		php::echo_one(issued->code);
		php::echo_one(string_t("\n"));
		return static_cast<I >(1);
	}
	P_AccountingCheck paid = sales->receive(ledger, invoice, string_t("PAY-001"), static_cast<I >(7000));
	if (static_cast<bool>((!paid->accepted))) {
		php::echo_one(paid->code);
		php::echo_one(string_t("\n"));
		return static_cast<I >(2);
	}
	I expected = convert_I(static_cast<I >(5000));
	auto matches = [expected](I actual) mutable -> bool_t {
	return bool_t(php::identical(actual, expected));
};
	P_TrialBalance report = ledger->trial_balance();
	if (static_cast<bool>(((!report->is_balanced()) || (!matches(invoice->outstanding()))))) {
		return static_cast<I >(3);
	}
	if (static_cast<bool>((php::not_identical(ledger->balance_of(string_t("bank")), static_cast<I >(7000)) || php::not_identical(ledger->balance_of(string_t("receivables")), static_cast<I >(5000))))) {
		return static_cast<I >(4);
	}
	P_AccountingCheck duplicate = sales->receive(ledger, invoice, string_t("PAY-001"), static_cast<I >(1000));
	if (static_cast<bool>(((duplicate->accepted || php::not_identical(ledger->entry_count(), static_cast<I >(2))) || php::not_identical(invoice->outstanding(), static_cast<I >(5000))))) {
		return static_cast<I >(5);
	}
	php::echo_one(string_t("accounting workload: invoice/payment/trial-balance checks passed\n"));
	return static_cast<I >(0);
}

int __scpp_main() {
	I argc = php::cli_argc();
	cli_arguments argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/examples/accounting_workload/main.phs", 0);
	I status = convert_I(run_accounting_workload());
	P_WorkloadChecks checks = create_WorkloadChecks();
	checks->equal(status, static_cast<I >(0), string_t("invoice/payment/trial-balance vertical slice"));
	check_money_rules(checks);
	check_calendar_rules(checks);
	check_ledger_rejections(checks);
	check_inventory_flow(checks);
	check_purchasing_flow(checks);
	check_asset_flow(checks);
	check_budget_flow(checks);
	check_sales_snapshot(checks);
	check_bank_flow(checks);
	check_adjustment_flow(checks);
	check_integrated_company(checks);
	checks->report();
	if (static_cast<bool>(php::condition_truthy(php::not_identical(status, static_cast<I >(0))))) {
		php::echo_one(string_t("accounting workload failed: "));
		php::echo_one(status);
		php::echo_one(string_t("\n"));
	}
	return 0;
}
}

int main(int __scpp_argc, char** __scpp_argv) {
	try {
		::scpp::php::set_cli_args(__scpp_argc, __scpp_argv);
		return scpp::__scpp_main();
	} catch (const ::scpp::runtime_failure &exception) {
		::scpp::print_runtime_exception(exception);
		return 1;
	}
}

