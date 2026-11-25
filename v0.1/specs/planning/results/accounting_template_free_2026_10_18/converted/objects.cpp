#include "all.hpp"
namespace scpp {
P_DepreciationPolicy::P_DepreciationPolicy(const P_StraightLineDepreciation& x) : owner(x.owner),ptr(static_cast<DepreciationPolicy*>(x.ptr)) {}
P_DepreciationPolicy::P_DepreciationPolicy(const P_DecliningBalanceDepreciation& x) : owner(x.owner),ptr(static_cast<DepreciationPolicy*>(x.ptr)) {}
static void drop_AccountingCheck(void *p) {delete static_cast<AccountingCheck*>(p);}
P_AccountingCheck create_AccountingCheck() {auto *p=new AccountingCheck();auto *state=make_owner(p,drop_AccountingCheck);return P_AccountingCheck(state,p);}
static void drop_AccrualRecord(void *p) {delete static_cast<AccrualRecord*>(p);}
P_AccrualRecord create_AccrualRecord() {auto *p=new AccrualRecord();auto *state=make_owner(p,drop_AccrualRecord);return P_AccrualRecord(state,p);}
static void drop_AdjustmentBook(void *p) {delete static_cast<AdjustmentBook*>(p);}
P_AdjustmentBook create_AdjustmentBook() {auto *p=new AdjustmentBook();auto *state=make_owner(p,drop_AdjustmentBook);return P_AdjustmentBook(state,p);}
static void drop_AllocationResult(void *p) {delete static_cast<AllocationResult*>(p);}
P_AllocationResult create_AllocationResult() {auto *p=new AllocationResult();auto *state=make_owner(p,drop_AllocationResult);return P_AllocationResult(state,p);}
static void drop_AmountResult(void *p) {delete static_cast<AmountResult*>(p);}
P_AmountResult create_AmountResult() {auto *p=new AmountResult();auto *state=make_owner(p,drop_AmountResult);return P_AmountResult(state,p);}
static void drop_AssetRegister(void *p) {delete static_cast<AssetRegister*>(p);}
P_AssetRegister create_AssetRegister() {auto *p=new AssetRegister();auto *state=make_owner(p,drop_AssetRegister);return P_AssetRegister(state,p);}
static void drop_BankMatch(void *p) {delete static_cast<BankMatch*>(p);}
P_BankMatch create_BankMatch() {auto *p=new BankMatch();auto *state=make_owner(p,drop_BankMatch);return P_BankMatch(state,p);}
static void drop_BankReconciliation(void *p) {delete static_cast<BankReconciliation*>(p);}
P_BankReconciliation create_BankReconciliation() {auto *p=new BankReconciliation();auto *state=make_owner(p,drop_BankReconciliation);return P_BankReconciliation(state,p);}
static void drop_BankReconciliationReport(void *p) {delete static_cast<BankReconciliationReport*>(p);}
P_BankReconciliationReport create_BankReconciliationReport() {auto *p=new BankReconciliationReport();auto *state=make_owner(p,drop_BankReconciliationReport);return P_BankReconciliationReport(state,p);}
static void drop_BankStatementLine(void *p) {delete static_cast<BankStatementLine*>(p);}
P_BankStatementLine create_BankStatementLine() {auto *p=new BankStatementLine();auto *state=make_owner(p,drop_BankStatementLine);return P_BankStatementLine(state,p);}
static void drop_BudgetBook(void *p) {delete static_cast<BudgetBook*>(p);}
P_BudgetBook create_BudgetBook() {auto *p=new BudgetBook();auto *state=make_owner(p,drop_BudgetBook);return P_BudgetBook(state,p);}
static void drop_BudgetLine(void *p) {delete static_cast<BudgetLine*>(p);}
P_BudgetLine create_BudgetLine() {auto *p=new BudgetLine();auto *state=make_owner(p,drop_BudgetLine);return P_BudgetLine(state,p);}
static void drop_BudgetReport(void *p) {delete static_cast<BudgetReport*>(p);}
P_BudgetReport create_BudgetReport() {auto *p=new BudgetReport();auto *state=make_owner(p,drop_BudgetReport);return P_BudgetReport(state,p);}
static void drop_BudgetVarianceRow(void *p) {delete static_cast<BudgetVarianceRow*>(p);}
P_BudgetVarianceRow create_BudgetVarianceRow() {auto *p=new BudgetVarianceRow();auto *state=make_owner(p,drop_BudgetVarianceRow);return P_BudgetVarianceRow(state,p);}
static void drop_DecliningBalanceDepreciation(void *p) {delete static_cast<DecliningBalanceDepreciation*>(p);}
P_DecliningBalanceDepreciation create_DecliningBalanceDepreciation() {auto *p=new DecliningBalanceDepreciation();auto *state=make_owner(p,drop_DecliningBalanceDepreciation);return P_DecliningBalanceDepreciation(state,p);}
static void drop_FinancialPosition(void *p) {delete static_cast<FinancialPosition*>(p);}
P_FinancialPosition create_FinancialPosition() {auto *p=new FinancialPosition();auto *state=make_owner(p,drop_FinancialPosition);return P_FinancialPosition(state,p);}
static void drop_FinancialReports(void *p) {delete static_cast<FinancialReports*>(p);}
P_FinancialReports create_FinancialReports() {auto *p=new FinancialReports();auto *state=make_owner(p,drop_FinancialReports);return P_FinancialReports(state,p);}
static void drop_FixedAsset(void *p) {delete static_cast<FixedAsset*>(p);}
P_FixedAsset create_FixedAsset() {auto *p=new FixedAsset();auto *state=make_owner(p,drop_FixedAsset);return P_FixedAsset(state,p);}
static void drop_GeneralLedger(void *p) {delete static_cast<GeneralLedger*>(p);}
P_GeneralLedger create_GeneralLedger() {auto *p=new GeneralLedger();auto *state=make_owner(p,drop_GeneralLedger);return P_GeneralLedger(state,p);}
static void drop_InventoryBook(void *p) {delete static_cast<InventoryBook*>(p);}
P_InventoryBook create_InventoryBook() {auto *p=new InventoryBook();auto *state=make_owner(p,drop_InventoryBook);return P_InventoryBook(state,p);}
static void drop_JournalEntry(void *p) {delete static_cast<JournalEntry*>(p);}
P_JournalEntry create_JournalEntry() {auto *p=new JournalEntry();auto *state=make_owner(p,drop_JournalEntry);return P_JournalEntry(state,p);}
static void drop_JournalLine(void *p) {delete static_cast<JournalLine*>(p);}
P_JournalLine create_JournalLine() {auto *p=new JournalLine();auto *state=make_owner(p,drop_JournalLine);return P_JournalLine(state,p);}
static void drop_LedgerAccount(void *p) {delete static_cast<LedgerAccount*>(p);}
P_LedgerAccount create_LedgerAccount() {auto *p=new LedgerAccount();auto *state=make_owner(p,drop_LedgerAccount);return P_LedgerAccount(state,p);}
static void drop_PayableRecord(void *p) {delete static_cast<PayableRecord*>(p);}
P_PayableRecord create_PayableRecord() {auto *p=new PayableRecord();auto *state=make_owner(p,drop_PayableRecord);return P_PayableRecord(state,p);}
static void drop_PaymentProposal(void *p) {delete static_cast<PaymentProposal*>(p);}
P_PaymentProposal create_PaymentProposal() {auto *p=new PaymentProposal();auto *state=make_owner(p,drop_PaymentProposal);return P_PaymentProposal(state,p);}
static void drop_PaymentProposalRow(void *p) {delete static_cast<PaymentProposalRow*>(p);}
P_PaymentProposalRow create_PaymentProposalRow() {auto *p=new PaymentProposalRow();auto *state=make_owner(p,drop_PaymentProposalRow);return P_PaymentProposalRow(state,p);}
static void drop_PaymentTerms(void *p) {delete static_cast<PaymentTerms*>(p);}
P_PaymentTerms create_PaymentTerms() {auto *p=new PaymentTerms();auto *state=make_owner(p,drop_PaymentTerms);return P_PaymentTerms(state,p);}
static void drop_PrepaymentSchedule(void *p) {delete static_cast<PrepaymentSchedule*>(p);}
P_PrepaymentSchedule create_PrepaymentSchedule() {auto *p=new PrepaymentSchedule();auto *state=make_owner(p,drop_PrepaymentSchedule);return P_PrepaymentSchedule(state,p);}
static void drop_ProfitAndLoss(void *p) {delete static_cast<ProfitAndLoss*>(p);}
P_ProfitAndLoss create_ProfitAndLoss() {auto *p=new ProfitAndLoss();auto *state=make_owner(p,drop_ProfitAndLoss);return P_ProfitAndLoss(state,p);}
static void drop_PurchasingBook(void *p) {delete static_cast<PurchasingBook*>(p);}
P_PurchasingBook create_PurchasingBook() {auto *p=new PurchasingBook();auto *state=make_owner(p,drop_PurchasingBook);return P_PurchasingBook(state,p);}
static void drop_SalesInvoice(void *p) {delete static_cast<SalesInvoice*>(p);}
P_SalesInvoice create_SalesInvoice() {auto *p=new SalesInvoice();auto *state=make_owner(p,drop_SalesInvoice);return P_SalesInvoice(state,p);}
static void drop_SalesService(void *p) {delete static_cast<SalesService*>(p);}
P_SalesService create_SalesService() {auto *p=new SalesService();auto *state=make_owner(p,drop_SalesService);return P_SalesService(state,p);}
static void drop_StockIssueResult(void *p) {delete static_cast<StockIssueResult*>(p);}
P_StockIssueResult create_StockIssueResult() {auto *p=new StockIssueResult();auto *state=make_owner(p,drop_StockIssueResult);return P_StockIssueResult(state,p);}
static void drop_StockItem(void *p) {delete static_cast<StockItem*>(p);}
P_StockItem create_StockItem() {auto *p=new StockItem();auto *state=make_owner(p,drop_StockItem);return P_StockItem(state,p);}
static void drop_StockLot(void *p) {delete static_cast<StockLot*>(p);}
P_StockLot create_StockLot() {auto *p=new StockLot();auto *state=make_owner(p,drop_StockLot);return P_StockLot(state,p);}
static void drop_StockReservation(void *p) {delete static_cast<StockReservation*>(p);}
P_StockReservation create_StockReservation() {auto *p=new StockReservation();auto *state=make_owner(p,drop_StockReservation);return P_StockReservation(state,p);}
static void drop_StraightLineDepreciation(void *p) {delete static_cast<StraightLineDepreciation*>(p);}
P_StraightLineDepreciation create_StraightLineDepreciation() {auto *p=new StraightLineDepreciation();auto *state=make_owner(p,drop_StraightLineDepreciation);return P_StraightLineDepreciation(state,p);}
static void drop_SupplierBill(void *p) {delete static_cast<SupplierBill*>(p);}
P_SupplierBill create_SupplierBill() {auto *p=new SupplierBill();auto *state=make_owner(p,drop_SupplierBill);return P_SupplierBill(state,p);}
static void drop_SupplierRecord(void *p) {delete static_cast<SupplierRecord*>(p);}
P_SupplierRecord create_SupplierRecord() {auto *p=new SupplierRecord();auto *state=make_owner(p,drop_SupplierRecord);return P_SupplierRecord(state,p);}
static void drop_TrialBalance(void *p) {delete static_cast<TrialBalance*>(p);}
P_TrialBalance create_TrialBalance() {auto *p=new TrialBalance();auto *state=make_owner(p,drop_TrialBalance);return P_TrialBalance(state,p);}
static void drop_TrialBalanceRow(void *p) {delete static_cast<TrialBalanceRow*>(p);}
P_TrialBalanceRow create_TrialBalanceRow() {auto *p=new TrialBalanceRow();auto *state=make_owner(p,drop_TrialBalanceRow);return P_TrialBalanceRow(state,p);}
static void drop_WorkingCapital(void *p) {delete static_cast<WorkingCapital*>(p);}
P_WorkingCapital create_WorkingCapital() {auto *p=new WorkingCapital();auto *state=make_owner(p,drop_WorkingCapital);return P_WorkingCapital(state,p);}
static void drop_WorkloadChecks(void *p) {delete static_cast<WorkloadChecks*>(p);}
P_WorkloadChecks create_WorkloadChecks() {auto *p=new WorkloadChecks();auto *state=make_owner(p,drop_WorkloadChecks);return P_WorkloadChecks(state,p);}
}
