#pragma once
#include "base.hpp"
#include "__project_fwd.hpp"
namespace scpp {
enum class AccountCategory : int32_t;
enum class PaymentTermKind : int32_t;
enum class PurchaseChargeKind : int32_t;
struct P_AccountingCheck;
struct P_AccrualRecord;
struct P_AdjustmentBook;
struct P_AllocationResult;
struct P_AmountResult;
struct P_AssetRegister;
struct P_BankMatch;
struct P_BankReconciliation;
struct P_BankReconciliationReport;
struct P_BankStatementLine;
struct P_BudgetBook;
struct P_BudgetLine;
struct P_BudgetReport;
struct P_BudgetVarianceRow;
struct P_DecliningBalanceDepreciation;
struct P_DepreciationPolicy;
struct P_FinancialPosition;
struct P_FinancialReports;
struct P_FixedAsset;
struct P_GeneralLedger;
struct P_InventoryBook;
struct P_JournalEntry;
struct P_JournalLine;
struct P_LedgerAccount;
struct P_PayableRecord;
struct P_PaymentProposal;
struct P_PaymentProposalRow;
struct P_PaymentTerms;
struct P_PrepaymentSchedule;
struct P_ProfitAndLoss;
struct P_PurchasingBook;
struct P_SalesInvoice;
struct P_SalesService;
struct P_StockIssueResult;
struct P_StockItem;
struct P_StockLot;
struct P_StockReservation;
struct P_StraightLineDepreciation;
struct P_SupplierBill;
struct P_SupplierRecord;
struct P_TrialBalance;
struct P_TrialBalanceRow;
struct P_WorkingCapital;
struct P_WorkloadChecks;
struct P_AccountingCheck {
    owner_ref owner;
    AccountingCheck *ptr=nullptr;
    P_AccountingCheck()=default;
    P_AccountingCheck(const P_AccountingCheck&)=default;
    P_AccountingCheck& operator=(const P_AccountingCheck&)=default;
    P_AccountingCheck(P_AccountingCheck&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_AccountingCheck& operator=(P_AccountingCheck&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_AccountingCheck(owner_state *state,AccountingCheck *value) : owner(state),ptr(value) {}
    AccountingCheck *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_AccrualRecord {
    owner_ref owner;
    AccrualRecord *ptr=nullptr;
    P_AccrualRecord()=default;
    P_AccrualRecord(const P_AccrualRecord&)=default;
    P_AccrualRecord& operator=(const P_AccrualRecord&)=default;
    P_AccrualRecord(P_AccrualRecord&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_AccrualRecord& operator=(P_AccrualRecord&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_AccrualRecord(owner_state *state,AccrualRecord *value) : owner(state),ptr(value) {}
    AccrualRecord *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_AdjustmentBook {
    owner_ref owner;
    AdjustmentBook *ptr=nullptr;
    P_AdjustmentBook()=default;
    P_AdjustmentBook(const P_AdjustmentBook&)=default;
    P_AdjustmentBook& operator=(const P_AdjustmentBook&)=default;
    P_AdjustmentBook(P_AdjustmentBook&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_AdjustmentBook& operator=(P_AdjustmentBook&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_AdjustmentBook(owner_state *state,AdjustmentBook *value) : owner(state),ptr(value) {}
    AdjustmentBook *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_AllocationResult {
    owner_ref owner;
    AllocationResult *ptr=nullptr;
    P_AllocationResult()=default;
    P_AllocationResult(const P_AllocationResult&)=default;
    P_AllocationResult& operator=(const P_AllocationResult&)=default;
    P_AllocationResult(P_AllocationResult&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_AllocationResult& operator=(P_AllocationResult&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_AllocationResult(owner_state *state,AllocationResult *value) : owner(state),ptr(value) {}
    AllocationResult *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_AmountResult {
    owner_ref owner;
    AmountResult *ptr=nullptr;
    P_AmountResult()=default;
    P_AmountResult(const P_AmountResult&)=default;
    P_AmountResult& operator=(const P_AmountResult&)=default;
    P_AmountResult(P_AmountResult&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_AmountResult& operator=(P_AmountResult&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_AmountResult(owner_state *state,AmountResult *value) : owner(state),ptr(value) {}
    AmountResult *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_AssetRegister {
    owner_ref owner;
    AssetRegister *ptr=nullptr;
    P_AssetRegister()=default;
    P_AssetRegister(const P_AssetRegister&)=default;
    P_AssetRegister& operator=(const P_AssetRegister&)=default;
    P_AssetRegister(P_AssetRegister&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_AssetRegister& operator=(P_AssetRegister&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_AssetRegister(owner_state *state,AssetRegister *value) : owner(state),ptr(value) {}
    AssetRegister *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_BankMatch {
    owner_ref owner;
    BankMatch *ptr=nullptr;
    P_BankMatch()=default;
    P_BankMatch(const P_BankMatch&)=default;
    P_BankMatch& operator=(const P_BankMatch&)=default;
    P_BankMatch(P_BankMatch&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_BankMatch& operator=(P_BankMatch&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_BankMatch(owner_state *state,BankMatch *value) : owner(state),ptr(value) {}
    BankMatch *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_BankReconciliation {
    owner_ref owner;
    BankReconciliation *ptr=nullptr;
    P_BankReconciliation()=default;
    P_BankReconciliation(const P_BankReconciliation&)=default;
    P_BankReconciliation& operator=(const P_BankReconciliation&)=default;
    P_BankReconciliation(P_BankReconciliation&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_BankReconciliation& operator=(P_BankReconciliation&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_BankReconciliation(owner_state *state,BankReconciliation *value) : owner(state),ptr(value) {}
    BankReconciliation *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_BankReconciliationReport {
    owner_ref owner;
    BankReconciliationReport *ptr=nullptr;
    P_BankReconciliationReport()=default;
    P_BankReconciliationReport(const P_BankReconciliationReport&)=default;
    P_BankReconciliationReport& operator=(const P_BankReconciliationReport&)=default;
    P_BankReconciliationReport(P_BankReconciliationReport&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_BankReconciliationReport& operator=(P_BankReconciliationReport&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_BankReconciliationReport(owner_state *state,BankReconciliationReport *value) : owner(state),ptr(value) {}
    BankReconciliationReport *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_BankStatementLine {
    owner_ref owner;
    BankStatementLine *ptr=nullptr;
    P_BankStatementLine()=default;
    P_BankStatementLine(const P_BankStatementLine&)=default;
    P_BankStatementLine& operator=(const P_BankStatementLine&)=default;
    P_BankStatementLine(P_BankStatementLine&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_BankStatementLine& operator=(P_BankStatementLine&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_BankStatementLine(owner_state *state,BankStatementLine *value) : owner(state),ptr(value) {}
    BankStatementLine *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_BudgetBook {
    owner_ref owner;
    BudgetBook *ptr=nullptr;
    P_BudgetBook()=default;
    P_BudgetBook(const P_BudgetBook&)=default;
    P_BudgetBook& operator=(const P_BudgetBook&)=default;
    P_BudgetBook(P_BudgetBook&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_BudgetBook& operator=(P_BudgetBook&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_BudgetBook(owner_state *state,BudgetBook *value) : owner(state),ptr(value) {}
    BudgetBook *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_BudgetLine {
    owner_ref owner;
    BudgetLine *ptr=nullptr;
    P_BudgetLine()=default;
    P_BudgetLine(const P_BudgetLine&)=default;
    P_BudgetLine& operator=(const P_BudgetLine&)=default;
    P_BudgetLine(P_BudgetLine&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_BudgetLine& operator=(P_BudgetLine&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_BudgetLine(owner_state *state,BudgetLine *value) : owner(state),ptr(value) {}
    BudgetLine *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_BudgetReport {
    owner_ref owner;
    BudgetReport *ptr=nullptr;
    P_BudgetReport()=default;
    P_BudgetReport(const P_BudgetReport&)=default;
    P_BudgetReport& operator=(const P_BudgetReport&)=default;
    P_BudgetReport(P_BudgetReport&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_BudgetReport& operator=(P_BudgetReport&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_BudgetReport(owner_state *state,BudgetReport *value) : owner(state),ptr(value) {}
    BudgetReport *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_BudgetVarianceRow {
    owner_ref owner;
    BudgetVarianceRow *ptr=nullptr;
    P_BudgetVarianceRow()=default;
    P_BudgetVarianceRow(const P_BudgetVarianceRow&)=default;
    P_BudgetVarianceRow& operator=(const P_BudgetVarianceRow&)=default;
    P_BudgetVarianceRow(P_BudgetVarianceRow&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_BudgetVarianceRow& operator=(P_BudgetVarianceRow&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_BudgetVarianceRow(owner_state *state,BudgetVarianceRow *value) : owner(state),ptr(value) {}
    BudgetVarianceRow *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_DecliningBalanceDepreciation {
    owner_ref owner;
    DecliningBalanceDepreciation *ptr=nullptr;
    P_DecliningBalanceDepreciation()=default;
    P_DecliningBalanceDepreciation(const P_DecliningBalanceDepreciation&)=default;
    P_DecliningBalanceDepreciation& operator=(const P_DecliningBalanceDepreciation&)=default;
    P_DecliningBalanceDepreciation(P_DecliningBalanceDepreciation&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_DecliningBalanceDepreciation& operator=(P_DecliningBalanceDepreciation&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_DecliningBalanceDepreciation(owner_state *state,DecliningBalanceDepreciation *value) : owner(state),ptr(value) {}
    DecliningBalanceDepreciation *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_DepreciationPolicy {
    owner_ref owner;
    DepreciationPolicy *ptr=nullptr;
    P_DepreciationPolicy()=default;
    P_DepreciationPolicy(const P_DepreciationPolicy&)=default;
    P_DepreciationPolicy& operator=(const P_DepreciationPolicy&)=default;
    P_DepreciationPolicy(P_DepreciationPolicy&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_DepreciationPolicy& operator=(P_DepreciationPolicy&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_DepreciationPolicy(owner_state *state,DepreciationPolicy *value) : owner(state),ptr(value) {}
    P_DepreciationPolicy(const P_StraightLineDepreciation&);
    P_DepreciationPolicy(const P_DecliningBalanceDepreciation&);
    DepreciationPolicy *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_FinancialPosition {
    owner_ref owner;
    FinancialPosition *ptr=nullptr;
    P_FinancialPosition()=default;
    P_FinancialPosition(const P_FinancialPosition&)=default;
    P_FinancialPosition& operator=(const P_FinancialPosition&)=default;
    P_FinancialPosition(P_FinancialPosition&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_FinancialPosition& operator=(P_FinancialPosition&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_FinancialPosition(owner_state *state,FinancialPosition *value) : owner(state),ptr(value) {}
    FinancialPosition *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_FinancialReports {
    owner_ref owner;
    FinancialReports *ptr=nullptr;
    P_FinancialReports()=default;
    P_FinancialReports(const P_FinancialReports&)=default;
    P_FinancialReports& operator=(const P_FinancialReports&)=default;
    P_FinancialReports(P_FinancialReports&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_FinancialReports& operator=(P_FinancialReports&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_FinancialReports(owner_state *state,FinancialReports *value) : owner(state),ptr(value) {}
    FinancialReports *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_FixedAsset {
    owner_ref owner;
    FixedAsset *ptr=nullptr;
    P_FixedAsset()=default;
    P_FixedAsset(const P_FixedAsset&)=default;
    P_FixedAsset& operator=(const P_FixedAsset&)=default;
    P_FixedAsset(P_FixedAsset&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_FixedAsset& operator=(P_FixedAsset&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_FixedAsset(owner_state *state,FixedAsset *value) : owner(state),ptr(value) {}
    FixedAsset *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_GeneralLedger {
    owner_ref owner;
    GeneralLedger *ptr=nullptr;
    P_GeneralLedger()=default;
    P_GeneralLedger(const P_GeneralLedger&)=default;
    P_GeneralLedger& operator=(const P_GeneralLedger&)=default;
    P_GeneralLedger(P_GeneralLedger&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_GeneralLedger& operator=(P_GeneralLedger&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_GeneralLedger(owner_state *state,GeneralLedger *value) : owner(state),ptr(value) {}
    GeneralLedger *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_InventoryBook {
    owner_ref owner;
    InventoryBook *ptr=nullptr;
    P_InventoryBook()=default;
    P_InventoryBook(const P_InventoryBook&)=default;
    P_InventoryBook& operator=(const P_InventoryBook&)=default;
    P_InventoryBook(P_InventoryBook&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_InventoryBook& operator=(P_InventoryBook&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_InventoryBook(owner_state *state,InventoryBook *value) : owner(state),ptr(value) {}
    InventoryBook *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_JournalEntry {
    owner_ref owner;
    JournalEntry *ptr=nullptr;
    P_JournalEntry()=default;
    P_JournalEntry(const P_JournalEntry&)=default;
    P_JournalEntry& operator=(const P_JournalEntry&)=default;
    P_JournalEntry(P_JournalEntry&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_JournalEntry& operator=(P_JournalEntry&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_JournalEntry(owner_state *state,JournalEntry *value) : owner(state),ptr(value) {}
    JournalEntry *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_JournalLine {
    owner_ref owner;
    JournalLine *ptr=nullptr;
    P_JournalLine()=default;
    P_JournalLine(const P_JournalLine&)=default;
    P_JournalLine& operator=(const P_JournalLine&)=default;
    P_JournalLine(P_JournalLine&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_JournalLine& operator=(P_JournalLine&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_JournalLine(owner_state *state,JournalLine *value) : owner(state),ptr(value) {}
    JournalLine *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_LedgerAccount {
    owner_ref owner;
    LedgerAccount *ptr=nullptr;
    P_LedgerAccount()=default;
    P_LedgerAccount(const P_LedgerAccount&)=default;
    P_LedgerAccount& operator=(const P_LedgerAccount&)=default;
    P_LedgerAccount(P_LedgerAccount&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_LedgerAccount& operator=(P_LedgerAccount&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_LedgerAccount(owner_state *state,LedgerAccount *value) : owner(state),ptr(value) {}
    LedgerAccount *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_PayableRecord {
    owner_ref owner;
    PayableRecord *ptr=nullptr;
    P_PayableRecord()=default;
    P_PayableRecord(const P_PayableRecord&)=default;
    P_PayableRecord& operator=(const P_PayableRecord&)=default;
    P_PayableRecord(P_PayableRecord&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_PayableRecord& operator=(P_PayableRecord&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_PayableRecord(owner_state *state,PayableRecord *value) : owner(state),ptr(value) {}
    PayableRecord *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_PaymentProposal {
    owner_ref owner;
    PaymentProposal *ptr=nullptr;
    P_PaymentProposal()=default;
    P_PaymentProposal(const P_PaymentProposal&)=default;
    P_PaymentProposal& operator=(const P_PaymentProposal&)=default;
    P_PaymentProposal(P_PaymentProposal&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_PaymentProposal& operator=(P_PaymentProposal&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_PaymentProposal(owner_state *state,PaymentProposal *value) : owner(state),ptr(value) {}
    PaymentProposal *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_PaymentProposalRow {
    owner_ref owner;
    PaymentProposalRow *ptr=nullptr;
    P_PaymentProposalRow()=default;
    P_PaymentProposalRow(const P_PaymentProposalRow&)=default;
    P_PaymentProposalRow& operator=(const P_PaymentProposalRow&)=default;
    P_PaymentProposalRow(P_PaymentProposalRow&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_PaymentProposalRow& operator=(P_PaymentProposalRow&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_PaymentProposalRow(owner_state *state,PaymentProposalRow *value) : owner(state),ptr(value) {}
    PaymentProposalRow *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_PaymentTerms {
    owner_ref owner;
    PaymentTerms *ptr=nullptr;
    P_PaymentTerms()=default;
    P_PaymentTerms(const P_PaymentTerms&)=default;
    P_PaymentTerms& operator=(const P_PaymentTerms&)=default;
    P_PaymentTerms(P_PaymentTerms&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_PaymentTerms& operator=(P_PaymentTerms&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_PaymentTerms(owner_state *state,PaymentTerms *value) : owner(state),ptr(value) {}
    PaymentTerms *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_PrepaymentSchedule {
    owner_ref owner;
    PrepaymentSchedule *ptr=nullptr;
    P_PrepaymentSchedule()=default;
    P_PrepaymentSchedule(const P_PrepaymentSchedule&)=default;
    P_PrepaymentSchedule& operator=(const P_PrepaymentSchedule&)=default;
    P_PrepaymentSchedule(P_PrepaymentSchedule&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_PrepaymentSchedule& operator=(P_PrepaymentSchedule&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_PrepaymentSchedule(owner_state *state,PrepaymentSchedule *value) : owner(state),ptr(value) {}
    PrepaymentSchedule *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_ProfitAndLoss {
    owner_ref owner;
    ProfitAndLoss *ptr=nullptr;
    P_ProfitAndLoss()=default;
    P_ProfitAndLoss(const P_ProfitAndLoss&)=default;
    P_ProfitAndLoss& operator=(const P_ProfitAndLoss&)=default;
    P_ProfitAndLoss(P_ProfitAndLoss&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_ProfitAndLoss& operator=(P_ProfitAndLoss&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_ProfitAndLoss(owner_state *state,ProfitAndLoss *value) : owner(state),ptr(value) {}
    ProfitAndLoss *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_PurchasingBook {
    owner_ref owner;
    PurchasingBook *ptr=nullptr;
    P_PurchasingBook()=default;
    P_PurchasingBook(const P_PurchasingBook&)=default;
    P_PurchasingBook& operator=(const P_PurchasingBook&)=default;
    P_PurchasingBook(P_PurchasingBook&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_PurchasingBook& operator=(P_PurchasingBook&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_PurchasingBook(owner_state *state,PurchasingBook *value) : owner(state),ptr(value) {}
    PurchasingBook *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_SalesInvoice {
    owner_ref owner;
    SalesInvoice *ptr=nullptr;
    P_SalesInvoice()=default;
    P_SalesInvoice(const P_SalesInvoice&)=default;
    P_SalesInvoice& operator=(const P_SalesInvoice&)=default;
    P_SalesInvoice(P_SalesInvoice&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_SalesInvoice& operator=(P_SalesInvoice&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_SalesInvoice(owner_state *state,SalesInvoice *value) : owner(state),ptr(value) {}
    SalesInvoice *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_SalesService {
    owner_ref owner;
    SalesService *ptr=nullptr;
    P_SalesService()=default;
    P_SalesService(const P_SalesService&)=default;
    P_SalesService& operator=(const P_SalesService&)=default;
    P_SalesService(P_SalesService&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_SalesService& operator=(P_SalesService&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_SalesService(owner_state *state,SalesService *value) : owner(state),ptr(value) {}
    SalesService *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_StockIssueResult {
    owner_ref owner;
    StockIssueResult *ptr=nullptr;
    P_StockIssueResult()=default;
    P_StockIssueResult(const P_StockIssueResult&)=default;
    P_StockIssueResult& operator=(const P_StockIssueResult&)=default;
    P_StockIssueResult(P_StockIssueResult&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_StockIssueResult& operator=(P_StockIssueResult&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_StockIssueResult(owner_state *state,StockIssueResult *value) : owner(state),ptr(value) {}
    StockIssueResult *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_StockItem {
    owner_ref owner;
    StockItem *ptr=nullptr;
    P_StockItem()=default;
    P_StockItem(const P_StockItem&)=default;
    P_StockItem& operator=(const P_StockItem&)=default;
    P_StockItem(P_StockItem&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_StockItem& operator=(P_StockItem&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_StockItem(owner_state *state,StockItem *value) : owner(state),ptr(value) {}
    StockItem *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_StockLot {
    owner_ref owner;
    StockLot *ptr=nullptr;
    P_StockLot()=default;
    P_StockLot(const P_StockLot&)=default;
    P_StockLot& operator=(const P_StockLot&)=default;
    P_StockLot(P_StockLot&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_StockLot& operator=(P_StockLot&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_StockLot(owner_state *state,StockLot *value) : owner(state),ptr(value) {}
    StockLot *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_StockReservation {
    owner_ref owner;
    StockReservation *ptr=nullptr;
    P_StockReservation()=default;
    P_StockReservation(const P_StockReservation&)=default;
    P_StockReservation& operator=(const P_StockReservation&)=default;
    P_StockReservation(P_StockReservation&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_StockReservation& operator=(P_StockReservation&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_StockReservation(owner_state *state,StockReservation *value) : owner(state),ptr(value) {}
    StockReservation *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_StraightLineDepreciation {
    owner_ref owner;
    StraightLineDepreciation *ptr=nullptr;
    P_StraightLineDepreciation()=default;
    P_StraightLineDepreciation(const P_StraightLineDepreciation&)=default;
    P_StraightLineDepreciation& operator=(const P_StraightLineDepreciation&)=default;
    P_StraightLineDepreciation(P_StraightLineDepreciation&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_StraightLineDepreciation& operator=(P_StraightLineDepreciation&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_StraightLineDepreciation(owner_state *state,StraightLineDepreciation *value) : owner(state),ptr(value) {}
    StraightLineDepreciation *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_SupplierBill {
    owner_ref owner;
    SupplierBill *ptr=nullptr;
    P_SupplierBill()=default;
    P_SupplierBill(const P_SupplierBill&)=default;
    P_SupplierBill& operator=(const P_SupplierBill&)=default;
    P_SupplierBill(P_SupplierBill&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_SupplierBill& operator=(P_SupplierBill&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_SupplierBill(owner_state *state,SupplierBill *value) : owner(state),ptr(value) {}
    SupplierBill *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_SupplierRecord {
    owner_ref owner;
    SupplierRecord *ptr=nullptr;
    P_SupplierRecord()=default;
    P_SupplierRecord(const P_SupplierRecord&)=default;
    P_SupplierRecord& operator=(const P_SupplierRecord&)=default;
    P_SupplierRecord(P_SupplierRecord&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_SupplierRecord& operator=(P_SupplierRecord&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_SupplierRecord(owner_state *state,SupplierRecord *value) : owner(state),ptr(value) {}
    SupplierRecord *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_TrialBalance {
    owner_ref owner;
    TrialBalance *ptr=nullptr;
    P_TrialBalance()=default;
    P_TrialBalance(const P_TrialBalance&)=default;
    P_TrialBalance& operator=(const P_TrialBalance&)=default;
    P_TrialBalance(P_TrialBalance&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_TrialBalance& operator=(P_TrialBalance&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_TrialBalance(owner_state *state,TrialBalance *value) : owner(state),ptr(value) {}
    TrialBalance *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_TrialBalanceRow {
    owner_ref owner;
    TrialBalanceRow *ptr=nullptr;
    P_TrialBalanceRow()=default;
    P_TrialBalanceRow(const P_TrialBalanceRow&)=default;
    P_TrialBalanceRow& operator=(const P_TrialBalanceRow&)=default;
    P_TrialBalanceRow(P_TrialBalanceRow&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_TrialBalanceRow& operator=(P_TrialBalanceRow&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_TrialBalanceRow(owner_state *state,TrialBalanceRow *value) : owner(state),ptr(value) {}
    TrialBalanceRow *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_WorkingCapital {
    owner_ref owner;
    WorkingCapital *ptr=nullptr;
    P_WorkingCapital()=default;
    P_WorkingCapital(const P_WorkingCapital&)=default;
    P_WorkingCapital& operator=(const P_WorkingCapital&)=default;
    P_WorkingCapital(P_WorkingCapital&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_WorkingCapital& operator=(P_WorkingCapital&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_WorkingCapital(owner_state *state,WorkingCapital *value) : owner(state),ptr(value) {}
    WorkingCapital *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
struct P_WorkloadChecks {
    owner_ref owner;
    WorkloadChecks *ptr=nullptr;
    P_WorkloadChecks()=default;
    P_WorkloadChecks(const P_WorkloadChecks&)=default;
    P_WorkloadChecks& operator=(const P_WorkloadChecks&)=default;
    P_WorkloadChecks(P_WorkloadChecks&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {x.ptr=nullptr;}
    P_WorkloadChecks& operator=(P_WorkloadChecks&& x) noexcept {if(this!=&x) {owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}return *this;}
    P_WorkloadChecks(owner_state *state,WorkloadChecks *value) : owner(state),ptr(value) {}
    WorkloadChecks *operator->() const { if(!ptr) fail("null_shared_dereference");return ptr; }
};
P_AccountingCheck create_AccountingCheck();
P_AccrualRecord create_AccrualRecord();
P_AdjustmentBook create_AdjustmentBook();
P_AllocationResult create_AllocationResult();
P_AmountResult create_AmountResult();
P_AssetRegister create_AssetRegister();
P_BankMatch create_BankMatch();
P_BankReconciliation create_BankReconciliation();
P_BankReconciliationReport create_BankReconciliationReport();
P_BankStatementLine create_BankStatementLine();
P_BudgetBook create_BudgetBook();
P_BudgetLine create_BudgetLine();
P_BudgetReport create_BudgetReport();
P_BudgetVarianceRow create_BudgetVarianceRow();
P_DecliningBalanceDepreciation create_DecliningBalanceDepreciation();
P_FinancialPosition create_FinancialPosition();
P_FinancialReports create_FinancialReports();
P_FixedAsset create_FixedAsset();
P_GeneralLedger create_GeneralLedger();
P_InventoryBook create_InventoryBook();
P_JournalEntry create_JournalEntry();
P_JournalLine create_JournalLine();
P_LedgerAccount create_LedgerAccount();
P_PayableRecord create_PayableRecord();
P_PaymentProposal create_PaymentProposal();
P_PaymentProposalRow create_PaymentProposalRow();
P_PaymentTerms create_PaymentTerms();
P_PrepaymentSchedule create_PrepaymentSchedule();
P_ProfitAndLoss create_ProfitAndLoss();
P_PurchasingBook create_PurchasingBook();
P_SalesInvoice create_SalesInvoice();
P_SalesService create_SalesService();
P_StockIssueResult create_StockIssueResult();
P_StockItem create_StockItem();
P_StockLot create_StockLot();
P_StockReservation create_StockReservation();
P_StraightLineDepreciation create_StraightLineDepreciation();
P_SupplierBill create_SupplierBill();
P_SupplierRecord create_SupplierRecord();
P_TrialBalance create_TrialBalance();
P_TrialBalanceRow create_TrialBalanceRow();
P_WorkingCapital create_WorkingCapital();
P_WorkloadChecks create_WorkloadChecks();
struct Tag_P_AccountingCheck {};
struct Tag_P_TrialBalance {};
namespace php {
inline bool_t identical(AccountCategory a,AccountCategory b) { return bool_t(a==b); }
inline bool_t not_identical(AccountCategory a,AccountCategory b) { return bool_t(a!=b); }
inline bool_t identical(PaymentTermKind a,PaymentTermKind b) { return bool_t(a==b); }
inline bool_t not_identical(PaymentTermKind a,PaymentTermKind b) { return bool_t(a!=b); }
inline bool_t identical(PurchaseChargeKind a,PurchaseChargeKind b) { return bool_t(a==b); }
inline bool_t not_identical(PurchaseChargeKind a,PurchaseChargeKind b) { return bool_t(a!=b); }
}
struct E_H_I_string_t { string_t key{}; I value{}; bool live=false; };
class H_I_string_t {
    E_H_I_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_I_string_t()=default;
    H_I_string_t(const H_I_string_t&);
    H_I_string_t(H_I_string_t&&) noexcept;
    H_I_string_t& operator=(const H_I_string_t&);
    H_I_string_t& operator=(H_I_string_t&&) noexcept;
    ~H_I_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_I_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    I& operator[](const string_t&);
    I& at(const string_t&);
    const I& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_I_string_t {
    const H_I_string_t *source;size_t index;
    I value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_I_string_t {
    const H_I_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_I_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_I_string_t& x) const { return index!=x.index; }
    Entry_H_I_string_t operator*() const { return {source,index}; }
};
struct Range_H_I_string_t {
    const H_I_string_t *source;
    Iterator_H_I_string_t begin() const { Iterator_H_I_string_t i{source,0};i.skip();return i; }
    Iterator_H_I_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_I_string_t foreach_range(const H_I_string_t& v) { return {&v}; }
namespace php { inline I count(const H_I_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_I_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_AccrualRecord_string_t { string_t key{}; P_AccrualRecord value{}; bool live=false; };
class H_P_AccrualRecord_string_t {
    E_H_P_AccrualRecord_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_AccrualRecord_string_t()=default;
    H_P_AccrualRecord_string_t(const H_P_AccrualRecord_string_t&);
    H_P_AccrualRecord_string_t(H_P_AccrualRecord_string_t&&) noexcept;
    H_P_AccrualRecord_string_t& operator=(const H_P_AccrualRecord_string_t&);
    H_P_AccrualRecord_string_t& operator=(H_P_AccrualRecord_string_t&&) noexcept;
    ~H_P_AccrualRecord_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_AccrualRecord_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_AccrualRecord& operator[](const string_t&);
    P_AccrualRecord& at(const string_t&);
    const P_AccrualRecord& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_AccrualRecord_string_t {
    const H_P_AccrualRecord_string_t *source;size_t index;
    P_AccrualRecord value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_AccrualRecord_string_t {
    const H_P_AccrualRecord_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_AccrualRecord_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_AccrualRecord_string_t& x) const { return index!=x.index; }
    Entry_H_P_AccrualRecord_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_AccrualRecord_string_t {
    const H_P_AccrualRecord_string_t *source;
    Iterator_H_P_AccrualRecord_string_t begin() const { Iterator_H_P_AccrualRecord_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_AccrualRecord_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_AccrualRecord_string_t foreach_range(const H_P_AccrualRecord_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_AccrualRecord_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_AccrualRecord_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_BankMatch_string_t { string_t key{}; P_BankMatch value{}; bool live=false; };
class H_P_BankMatch_string_t {
    E_H_P_BankMatch_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_BankMatch_string_t()=default;
    H_P_BankMatch_string_t(const H_P_BankMatch_string_t&);
    H_P_BankMatch_string_t(H_P_BankMatch_string_t&&) noexcept;
    H_P_BankMatch_string_t& operator=(const H_P_BankMatch_string_t&);
    H_P_BankMatch_string_t& operator=(H_P_BankMatch_string_t&&) noexcept;
    ~H_P_BankMatch_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_BankMatch_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_BankMatch& operator[](const string_t&);
    P_BankMatch& at(const string_t&);
    const P_BankMatch& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_BankMatch_string_t {
    const H_P_BankMatch_string_t *source;size_t index;
    P_BankMatch value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_BankMatch_string_t {
    const H_P_BankMatch_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_BankMatch_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_BankMatch_string_t& x) const { return index!=x.index; }
    Entry_H_P_BankMatch_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_BankMatch_string_t {
    const H_P_BankMatch_string_t *source;
    Iterator_H_P_BankMatch_string_t begin() const { Iterator_H_P_BankMatch_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_BankMatch_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_BankMatch_string_t foreach_range(const H_P_BankMatch_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_BankMatch_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_BankMatch_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_BankStatementLine_string_t { string_t key{}; P_BankStatementLine value{}; bool live=false; };
class H_P_BankStatementLine_string_t {
    E_H_P_BankStatementLine_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_BankStatementLine_string_t()=default;
    H_P_BankStatementLine_string_t(const H_P_BankStatementLine_string_t&);
    H_P_BankStatementLine_string_t(H_P_BankStatementLine_string_t&&) noexcept;
    H_P_BankStatementLine_string_t& operator=(const H_P_BankStatementLine_string_t&);
    H_P_BankStatementLine_string_t& operator=(H_P_BankStatementLine_string_t&&) noexcept;
    ~H_P_BankStatementLine_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_BankStatementLine_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_BankStatementLine& operator[](const string_t&);
    P_BankStatementLine& at(const string_t&);
    const P_BankStatementLine& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_BankStatementLine_string_t {
    const H_P_BankStatementLine_string_t *source;size_t index;
    P_BankStatementLine value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_BankStatementLine_string_t {
    const H_P_BankStatementLine_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_BankStatementLine_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_BankStatementLine_string_t& x) const { return index!=x.index; }
    Entry_H_P_BankStatementLine_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_BankStatementLine_string_t {
    const H_P_BankStatementLine_string_t *source;
    Iterator_H_P_BankStatementLine_string_t begin() const { Iterator_H_P_BankStatementLine_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_BankStatementLine_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_BankStatementLine_string_t foreach_range(const H_P_BankStatementLine_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_BankStatementLine_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_BankStatementLine_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_FixedAsset_string_t { string_t key{}; P_FixedAsset value{}; bool live=false; };
class H_P_FixedAsset_string_t {
    E_H_P_FixedAsset_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_FixedAsset_string_t()=default;
    H_P_FixedAsset_string_t(const H_P_FixedAsset_string_t&);
    H_P_FixedAsset_string_t(H_P_FixedAsset_string_t&&) noexcept;
    H_P_FixedAsset_string_t& operator=(const H_P_FixedAsset_string_t&);
    H_P_FixedAsset_string_t& operator=(H_P_FixedAsset_string_t&&) noexcept;
    ~H_P_FixedAsset_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_FixedAsset_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_FixedAsset& operator[](const string_t&);
    P_FixedAsset& at(const string_t&);
    const P_FixedAsset& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_FixedAsset_string_t {
    const H_P_FixedAsset_string_t *source;size_t index;
    P_FixedAsset value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_FixedAsset_string_t {
    const H_P_FixedAsset_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_FixedAsset_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_FixedAsset_string_t& x) const { return index!=x.index; }
    Entry_H_P_FixedAsset_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_FixedAsset_string_t {
    const H_P_FixedAsset_string_t *source;
    Iterator_H_P_FixedAsset_string_t begin() const { Iterator_H_P_FixedAsset_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_FixedAsset_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_FixedAsset_string_t foreach_range(const H_P_FixedAsset_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_FixedAsset_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_FixedAsset_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_LedgerAccount_string_t { string_t key{}; P_LedgerAccount value{}; bool live=false; };
class H_P_LedgerAccount_string_t {
    E_H_P_LedgerAccount_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_LedgerAccount_string_t()=default;
    H_P_LedgerAccount_string_t(const H_P_LedgerAccount_string_t&);
    H_P_LedgerAccount_string_t(H_P_LedgerAccount_string_t&&) noexcept;
    H_P_LedgerAccount_string_t& operator=(const H_P_LedgerAccount_string_t&);
    H_P_LedgerAccount_string_t& operator=(H_P_LedgerAccount_string_t&&) noexcept;
    ~H_P_LedgerAccount_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_LedgerAccount_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_LedgerAccount& operator[](const string_t&);
    P_LedgerAccount& at(const string_t&);
    const P_LedgerAccount& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_LedgerAccount_string_t {
    const H_P_LedgerAccount_string_t *source;size_t index;
    P_LedgerAccount value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_LedgerAccount_string_t {
    const H_P_LedgerAccount_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_LedgerAccount_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_LedgerAccount_string_t& x) const { return index!=x.index; }
    Entry_H_P_LedgerAccount_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_LedgerAccount_string_t {
    const H_P_LedgerAccount_string_t *source;
    Iterator_H_P_LedgerAccount_string_t begin() const { Iterator_H_P_LedgerAccount_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_LedgerAccount_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_LedgerAccount_string_t foreach_range(const H_P_LedgerAccount_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_LedgerAccount_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_LedgerAccount_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_PayableRecord_string_t { string_t key{}; P_PayableRecord value{}; bool live=false; };
class H_P_PayableRecord_string_t {
    E_H_P_PayableRecord_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_PayableRecord_string_t()=default;
    H_P_PayableRecord_string_t(const H_P_PayableRecord_string_t&);
    H_P_PayableRecord_string_t(H_P_PayableRecord_string_t&&) noexcept;
    H_P_PayableRecord_string_t& operator=(const H_P_PayableRecord_string_t&);
    H_P_PayableRecord_string_t& operator=(H_P_PayableRecord_string_t&&) noexcept;
    ~H_P_PayableRecord_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_PayableRecord_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_PayableRecord& operator[](const string_t&);
    P_PayableRecord& at(const string_t&);
    const P_PayableRecord& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_PayableRecord_string_t {
    const H_P_PayableRecord_string_t *source;size_t index;
    P_PayableRecord value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_PayableRecord_string_t {
    const H_P_PayableRecord_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_PayableRecord_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_PayableRecord_string_t& x) const { return index!=x.index; }
    Entry_H_P_PayableRecord_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_PayableRecord_string_t {
    const H_P_PayableRecord_string_t *source;
    Iterator_H_P_PayableRecord_string_t begin() const { Iterator_H_P_PayableRecord_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_PayableRecord_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_PayableRecord_string_t foreach_range(const H_P_PayableRecord_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_PayableRecord_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_PayableRecord_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_PrepaymentSchedule_string_t { string_t key{}; P_PrepaymentSchedule value{}; bool live=false; };
class H_P_PrepaymentSchedule_string_t {
    E_H_P_PrepaymentSchedule_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_PrepaymentSchedule_string_t()=default;
    H_P_PrepaymentSchedule_string_t(const H_P_PrepaymentSchedule_string_t&);
    H_P_PrepaymentSchedule_string_t(H_P_PrepaymentSchedule_string_t&&) noexcept;
    H_P_PrepaymentSchedule_string_t& operator=(const H_P_PrepaymentSchedule_string_t&);
    H_P_PrepaymentSchedule_string_t& operator=(H_P_PrepaymentSchedule_string_t&&) noexcept;
    ~H_P_PrepaymentSchedule_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_PrepaymentSchedule_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_PrepaymentSchedule& operator[](const string_t&);
    P_PrepaymentSchedule& at(const string_t&);
    const P_PrepaymentSchedule& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_PrepaymentSchedule_string_t {
    const H_P_PrepaymentSchedule_string_t *source;size_t index;
    P_PrepaymentSchedule value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_PrepaymentSchedule_string_t {
    const H_P_PrepaymentSchedule_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_PrepaymentSchedule_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_PrepaymentSchedule_string_t& x) const { return index!=x.index; }
    Entry_H_P_PrepaymentSchedule_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_PrepaymentSchedule_string_t {
    const H_P_PrepaymentSchedule_string_t *source;
    Iterator_H_P_PrepaymentSchedule_string_t begin() const { Iterator_H_P_PrepaymentSchedule_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_PrepaymentSchedule_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_PrepaymentSchedule_string_t foreach_range(const H_P_PrepaymentSchedule_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_PrepaymentSchedule_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_PrepaymentSchedule_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_SalesInvoice_string_t { string_t key{}; P_SalesInvoice value{}; bool live=false; };
class H_P_SalesInvoice_string_t {
    E_H_P_SalesInvoice_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_SalesInvoice_string_t()=default;
    H_P_SalesInvoice_string_t(const H_P_SalesInvoice_string_t&);
    H_P_SalesInvoice_string_t(H_P_SalesInvoice_string_t&&) noexcept;
    H_P_SalesInvoice_string_t& operator=(const H_P_SalesInvoice_string_t&);
    H_P_SalesInvoice_string_t& operator=(H_P_SalesInvoice_string_t&&) noexcept;
    ~H_P_SalesInvoice_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_SalesInvoice_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_SalesInvoice& operator[](const string_t&);
    P_SalesInvoice& at(const string_t&);
    const P_SalesInvoice& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_SalesInvoice_string_t {
    const H_P_SalesInvoice_string_t *source;size_t index;
    P_SalesInvoice value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_SalesInvoice_string_t {
    const H_P_SalesInvoice_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_SalesInvoice_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_SalesInvoice_string_t& x) const { return index!=x.index; }
    Entry_H_P_SalesInvoice_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_SalesInvoice_string_t {
    const H_P_SalesInvoice_string_t *source;
    Iterator_H_P_SalesInvoice_string_t begin() const { Iterator_H_P_SalesInvoice_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_SalesInvoice_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_SalesInvoice_string_t foreach_range(const H_P_SalesInvoice_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_SalesInvoice_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_SalesInvoice_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_StockItem_string_t { string_t key{}; P_StockItem value{}; bool live=false; };
class H_P_StockItem_string_t {
    E_H_P_StockItem_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_StockItem_string_t()=default;
    H_P_StockItem_string_t(const H_P_StockItem_string_t&);
    H_P_StockItem_string_t(H_P_StockItem_string_t&&) noexcept;
    H_P_StockItem_string_t& operator=(const H_P_StockItem_string_t&);
    H_P_StockItem_string_t& operator=(H_P_StockItem_string_t&&) noexcept;
    ~H_P_StockItem_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_StockItem_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_StockItem& operator[](const string_t&);
    P_StockItem& at(const string_t&);
    const P_StockItem& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_StockItem_string_t {
    const H_P_StockItem_string_t *source;size_t index;
    P_StockItem value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_StockItem_string_t {
    const H_P_StockItem_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_StockItem_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_StockItem_string_t& x) const { return index!=x.index; }
    Entry_H_P_StockItem_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_StockItem_string_t {
    const H_P_StockItem_string_t *source;
    Iterator_H_P_StockItem_string_t begin() const { Iterator_H_P_StockItem_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_StockItem_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_StockItem_string_t foreach_range(const H_P_StockItem_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_StockItem_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_StockItem_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_StockReservation_string_t { string_t key{}; P_StockReservation value{}; bool live=false; };
class H_P_StockReservation_string_t {
    E_H_P_StockReservation_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_StockReservation_string_t()=default;
    H_P_StockReservation_string_t(const H_P_StockReservation_string_t&);
    H_P_StockReservation_string_t(H_P_StockReservation_string_t&&) noexcept;
    H_P_StockReservation_string_t& operator=(const H_P_StockReservation_string_t&);
    H_P_StockReservation_string_t& operator=(H_P_StockReservation_string_t&&) noexcept;
    ~H_P_StockReservation_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_StockReservation_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_StockReservation& operator[](const string_t&);
    P_StockReservation& at(const string_t&);
    const P_StockReservation& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_StockReservation_string_t {
    const H_P_StockReservation_string_t *source;size_t index;
    P_StockReservation value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_StockReservation_string_t {
    const H_P_StockReservation_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_StockReservation_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_StockReservation_string_t& x) const { return index!=x.index; }
    Entry_H_P_StockReservation_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_StockReservation_string_t {
    const H_P_StockReservation_string_t *source;
    Iterator_H_P_StockReservation_string_t begin() const { Iterator_H_P_StockReservation_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_StockReservation_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_StockReservation_string_t foreach_range(const H_P_StockReservation_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_StockReservation_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_StockReservation_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_P_SupplierRecord_string_t { string_t key{}; P_SupplierRecord value{}; bool live=false; };
class H_P_SupplierRecord_string_t {
    E_H_P_SupplierRecord_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_P_SupplierRecord_string_t()=default;
    H_P_SupplierRecord_string_t(const H_P_SupplierRecord_string_t&);
    H_P_SupplierRecord_string_t(H_P_SupplierRecord_string_t&&) noexcept;
    H_P_SupplierRecord_string_t& operator=(const H_P_SupplierRecord_string_t&);
    H_P_SupplierRecord_string_t& operator=(H_P_SupplierRecord_string_t&&) noexcept;
    ~H_P_SupplierRecord_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_P_SupplierRecord_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    P_SupplierRecord& operator[](const string_t&);
    P_SupplierRecord& at(const string_t&);
    const P_SupplierRecord& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_P_SupplierRecord_string_t {
    const H_P_SupplierRecord_string_t *source;size_t index;
    P_SupplierRecord value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_P_SupplierRecord_string_t {
    const H_P_SupplierRecord_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_P_SupplierRecord_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_P_SupplierRecord_string_t& x) const { return index!=x.index; }
    Entry_H_P_SupplierRecord_string_t operator*() const { return {source,index}; }
};
struct Range_H_P_SupplierRecord_string_t {
    const H_P_SupplierRecord_string_t *source;
    Iterator_H_P_SupplierRecord_string_t begin() const { Iterator_H_P_SupplierRecord_string_t i{source,0};i.skip();return i; }
    Iterator_H_P_SupplierRecord_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_P_SupplierRecord_string_t foreach_range(const H_P_SupplierRecord_string_t& v) { return {&v}; }
namespace php { inline I count(const H_P_SupplierRecord_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_P_SupplierRecord_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_H_bool_t_I { I key{}; bool_t value{}; bool live=false; };
class H_bool_t_I {
    E_H_bool_t_I *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const I&) const;
    void rehash(size_t);
    void grow();
public:
    H_bool_t_I()=default;
    H_bool_t_I(const H_bool_t_I&);
    H_bool_t_I(H_bool_t_I&&) noexcept;
    H_bool_t_I& operator=(const H_bool_t_I&);
    H_bool_t_I& operator=(H_bool_t_I&&) noexcept;
    ~H_bool_t_I();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_bool_t_I& entry(size_t i) const { return data_[i]; }
    bool contains(const I&) const;
    bool_t& operator[](const I&);
    bool_t& at(const I&);
    const bool_t& at(const I&) const;
    bool_t remove(const I&);
};
struct Entry_H_bool_t_I {
    const H_bool_t_I *source;size_t index;
    bool_t value_copy() const { return source->entry(index).value; }
    const I& key() const { return source->entry(index).key; }
};
struct Iterator_H_bool_t_I {
    const H_bool_t_I *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_bool_t_I& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_bool_t_I& x) const { return index!=x.index; }
    Entry_H_bool_t_I operator*() const { return {source,index}; }
};
struct Range_H_bool_t_I {
    const H_bool_t_I *source;
    Iterator_H_bool_t_I begin() const { Iterator_H_bool_t_I i{source,0};i.skip();return i; }
    Iterator_H_bool_t_I end() const { return {source,source->slots()}; }
};
inline Range_H_bool_t_I foreach_range(const H_bool_t_I& v) { return {&v}; }
namespace php { inline I count(const H_bool_t_I& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_bool_t_I& v,const I& k) { return bool_t(v.contains(k)); }
}
struct E_H_bool_t_string_t { string_t key{}; bool_t value{}; bool live=false; };
class H_bool_t_string_t {
    E_H_bool_t_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;
    size_t find(const string_t&) const;
    void rehash(size_t);
    void grow();
public:
    H_bool_t_string_t()=default;
    H_bool_t_string_t(const H_bool_t_string_t&);
    H_bool_t_string_t(H_bool_t_string_t&&) noexcept;
    H_bool_t_string_t& operator=(const H_bool_t_string_t&);
    H_bool_t_string_t& operator=(H_bool_t_string_t&&) noexcept;
    ~H_bool_t_string_t();
    size_t size() const { return count_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_ && data_[i].live; }
    const E_H_bool_t_string_t& entry(size_t i) const { return data_[i]; }
    bool contains(const string_t&) const;
    bool_t& operator[](const string_t&);
    bool_t& at(const string_t&);
    const bool_t& at(const string_t&) const;
    bool_t remove(const string_t&);
};
struct Entry_H_bool_t_string_t {
    const H_bool_t_string_t *source;size_t index;
    bool_t value_copy() const { return source->entry(index).value; }
    const string_t& key() const { return source->entry(index).key; }
};
struct Iterator_H_bool_t_string_t {
    const H_bool_t_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_H_bool_t_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_H_bool_t_string_t& x) const { return index!=x.index; }
    Entry_H_bool_t_string_t operator*() const { return {source,index}; }
};
struct Range_H_bool_t_string_t {
    const H_bool_t_string_t *source;
    Iterator_H_bool_t_string_t begin() const { Iterator_H_bool_t_string_t i{source,0};i.skip();return i; }
    Iterator_H_bool_t_string_t end() const { return {source,source->slots()}; }
};
inline Range_H_bool_t_string_t foreach_range(const H_bool_t_string_t& v) { return {&v}; }
namespace php { inline I count(const H_bool_t_string_t& v) { return I(static_cast<int64_t>(v.size())); }
inline bool_t isset(const H_bool_t_string_t& v,const string_t& k) { return bool_t(v.contains(k)); }
}
struct E_V_I { I value{}; };
class V_I {
    E_V_I *data_=nullptr;
    size_t used_=0,capacity_=0;
    void grow();
public:
    V_I()=default;
    V_I(const V_I&);
    V_I(V_I&&) noexcept;
    V_I& operator=(const V_I&);
    V_I& operator=(V_I&&) noexcept;
    ~V_I();
    size_t size() const { return used_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_; }
    const E_V_I& entry(size_t i) const { return data_[i]; }
    void push_back(I);
    void append(I v) { push_back(static_cast<I&&>(v)); }
    I& at(I i) { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    const I& at(I i) const { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    I& operator[](I i) { return at(i); }
    const I& operator[](I i) const { return at(i); }
};
struct Entry_V_I {
    const V_I *source;size_t index;
    I value_copy() const { return source->entry(index).value; }
    I key() const { return I(static_cast<int64_t>(index)); }
};
struct Iterator_V_I {
    const V_I *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_V_I& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_V_I& x) const { return index!=x.index; }
    Entry_V_I operator*() const { return {source,index}; }
};
struct Range_V_I {
    const V_I *source;
    Iterator_V_I begin() const { Iterator_V_I i{source,0};i.skip();return i; }
    Iterator_V_I end() const { return {source,source->slots()}; }
};
inline Range_V_I foreach_range(const V_I& v) { return {&v}; }
namespace php { inline I count(const V_I& v) { return I(static_cast<int64_t>(v.size())); }
}
struct E_V_P_BankStatementLine { P_BankStatementLine value{}; };
class V_P_BankStatementLine {
    E_V_P_BankStatementLine *data_=nullptr;
    size_t used_=0,capacity_=0;
    void grow();
public:
    V_P_BankStatementLine()=default;
    V_P_BankStatementLine(const V_P_BankStatementLine&);
    V_P_BankStatementLine(V_P_BankStatementLine&&) noexcept;
    V_P_BankStatementLine& operator=(const V_P_BankStatementLine&);
    V_P_BankStatementLine& operator=(V_P_BankStatementLine&&) noexcept;
    ~V_P_BankStatementLine();
    size_t size() const { return used_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_; }
    const E_V_P_BankStatementLine& entry(size_t i) const { return data_[i]; }
    void push_back(P_BankStatementLine);
    void append(P_BankStatementLine v) { push_back(static_cast<P_BankStatementLine&&>(v)); }
    P_BankStatementLine& at(I i) { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    const P_BankStatementLine& at(I i) const { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    P_BankStatementLine& operator[](I i) { return at(i); }
    const P_BankStatementLine& operator[](I i) const { return at(i); }
};
struct Entry_V_P_BankStatementLine {
    const V_P_BankStatementLine *source;size_t index;
    P_BankStatementLine value_copy() const { return source->entry(index).value; }
    I key() const { return I(static_cast<int64_t>(index)); }
};
struct Iterator_V_P_BankStatementLine {
    const V_P_BankStatementLine *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_V_P_BankStatementLine& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_V_P_BankStatementLine& x) const { return index!=x.index; }
    Entry_V_P_BankStatementLine operator*() const { return {source,index}; }
};
struct Range_V_P_BankStatementLine {
    const V_P_BankStatementLine *source;
    Iterator_V_P_BankStatementLine begin() const { Iterator_V_P_BankStatementLine i{source,0};i.skip();return i; }
    Iterator_V_P_BankStatementLine end() const { return {source,source->slots()}; }
};
inline Range_V_P_BankStatementLine foreach_range(const V_P_BankStatementLine& v) { return {&v}; }
namespace php { inline I count(const V_P_BankStatementLine& v) { return I(static_cast<int64_t>(v.size())); }
}
struct E_V_P_BudgetLine { P_BudgetLine value{}; };
class V_P_BudgetLine {
    E_V_P_BudgetLine *data_=nullptr;
    size_t used_=0,capacity_=0;
    void grow();
public:
    V_P_BudgetLine()=default;
    V_P_BudgetLine(const V_P_BudgetLine&);
    V_P_BudgetLine(V_P_BudgetLine&&) noexcept;
    V_P_BudgetLine& operator=(const V_P_BudgetLine&);
    V_P_BudgetLine& operator=(V_P_BudgetLine&&) noexcept;
    ~V_P_BudgetLine();
    size_t size() const { return used_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_; }
    const E_V_P_BudgetLine& entry(size_t i) const { return data_[i]; }
    void push_back(P_BudgetLine);
    void append(P_BudgetLine v) { push_back(static_cast<P_BudgetLine&&>(v)); }
    P_BudgetLine& at(I i) { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    const P_BudgetLine& at(I i) const { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    P_BudgetLine& operator[](I i) { return at(i); }
    const P_BudgetLine& operator[](I i) const { return at(i); }
};
struct Entry_V_P_BudgetLine {
    const V_P_BudgetLine *source;size_t index;
    P_BudgetLine value_copy() const { return source->entry(index).value; }
    I key() const { return I(static_cast<int64_t>(index)); }
};
struct Iterator_V_P_BudgetLine {
    const V_P_BudgetLine *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_V_P_BudgetLine& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_V_P_BudgetLine& x) const { return index!=x.index; }
    Entry_V_P_BudgetLine operator*() const { return {source,index}; }
};
struct Range_V_P_BudgetLine {
    const V_P_BudgetLine *source;
    Iterator_V_P_BudgetLine begin() const { Iterator_V_P_BudgetLine i{source,0};i.skip();return i; }
    Iterator_V_P_BudgetLine end() const { return {source,source->slots()}; }
};
inline Range_V_P_BudgetLine foreach_range(const V_P_BudgetLine& v) { return {&v}; }
namespace php { inline I count(const V_P_BudgetLine& v) { return I(static_cast<int64_t>(v.size())); }
}
struct E_V_P_BudgetVarianceRow { P_BudgetVarianceRow value{}; };
class V_P_BudgetVarianceRow {
    E_V_P_BudgetVarianceRow *data_=nullptr;
    size_t used_=0,capacity_=0;
    void grow();
public:
    V_P_BudgetVarianceRow()=default;
    V_P_BudgetVarianceRow(const V_P_BudgetVarianceRow&);
    V_P_BudgetVarianceRow(V_P_BudgetVarianceRow&&) noexcept;
    V_P_BudgetVarianceRow& operator=(const V_P_BudgetVarianceRow&);
    V_P_BudgetVarianceRow& operator=(V_P_BudgetVarianceRow&&) noexcept;
    ~V_P_BudgetVarianceRow();
    size_t size() const { return used_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_; }
    const E_V_P_BudgetVarianceRow& entry(size_t i) const { return data_[i]; }
    void push_back(P_BudgetVarianceRow);
    void append(P_BudgetVarianceRow v) { push_back(static_cast<P_BudgetVarianceRow&&>(v)); }
    P_BudgetVarianceRow& at(I i) { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    const P_BudgetVarianceRow& at(I i) const { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    P_BudgetVarianceRow& operator[](I i) { return at(i); }
    const P_BudgetVarianceRow& operator[](I i) const { return at(i); }
};
struct Entry_V_P_BudgetVarianceRow {
    const V_P_BudgetVarianceRow *source;size_t index;
    P_BudgetVarianceRow value_copy() const { return source->entry(index).value; }
    I key() const { return I(static_cast<int64_t>(index)); }
};
struct Iterator_V_P_BudgetVarianceRow {
    const V_P_BudgetVarianceRow *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_V_P_BudgetVarianceRow& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_V_P_BudgetVarianceRow& x) const { return index!=x.index; }
    Entry_V_P_BudgetVarianceRow operator*() const { return {source,index}; }
};
struct Range_V_P_BudgetVarianceRow {
    const V_P_BudgetVarianceRow *source;
    Iterator_V_P_BudgetVarianceRow begin() const { Iterator_V_P_BudgetVarianceRow i{source,0};i.skip();return i; }
    Iterator_V_P_BudgetVarianceRow end() const { return {source,source->slots()}; }
};
inline Range_V_P_BudgetVarianceRow foreach_range(const V_P_BudgetVarianceRow& v) { return {&v}; }
namespace php { inline I count(const V_P_BudgetVarianceRow& v) { return I(static_cast<int64_t>(v.size())); }
}
struct E_V_P_JournalEntry { P_JournalEntry value{}; };
class V_P_JournalEntry {
    E_V_P_JournalEntry *data_=nullptr;
    size_t used_=0,capacity_=0;
    void grow();
public:
    V_P_JournalEntry()=default;
    V_P_JournalEntry(const V_P_JournalEntry&);
    V_P_JournalEntry(V_P_JournalEntry&&) noexcept;
    V_P_JournalEntry& operator=(const V_P_JournalEntry&);
    V_P_JournalEntry& operator=(V_P_JournalEntry&&) noexcept;
    ~V_P_JournalEntry();
    size_t size() const { return used_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_; }
    const E_V_P_JournalEntry& entry(size_t i) const { return data_[i]; }
    void push_back(P_JournalEntry);
    void append(P_JournalEntry v) { push_back(static_cast<P_JournalEntry&&>(v)); }
    P_JournalEntry& at(I i) { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    const P_JournalEntry& at(I i) const { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    P_JournalEntry& operator[](I i) { return at(i); }
    const P_JournalEntry& operator[](I i) const { return at(i); }
};
struct Entry_V_P_JournalEntry {
    const V_P_JournalEntry *source;size_t index;
    P_JournalEntry value_copy() const { return source->entry(index).value; }
    I key() const { return I(static_cast<int64_t>(index)); }
};
struct Iterator_V_P_JournalEntry {
    const V_P_JournalEntry *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_V_P_JournalEntry& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_V_P_JournalEntry& x) const { return index!=x.index; }
    Entry_V_P_JournalEntry operator*() const { return {source,index}; }
};
struct Range_V_P_JournalEntry {
    const V_P_JournalEntry *source;
    Iterator_V_P_JournalEntry begin() const { Iterator_V_P_JournalEntry i{source,0};i.skip();return i; }
    Iterator_V_P_JournalEntry end() const { return {source,source->slots()}; }
};
inline Range_V_P_JournalEntry foreach_range(const V_P_JournalEntry& v) { return {&v}; }
namespace php { inline I count(const V_P_JournalEntry& v) { return I(static_cast<int64_t>(v.size())); }
}
struct E_V_P_JournalLine { P_JournalLine value{}; };
class V_P_JournalLine {
    E_V_P_JournalLine *data_=nullptr;
    size_t used_=0,capacity_=0;
    void grow();
public:
    V_P_JournalLine()=default;
    V_P_JournalLine(const V_P_JournalLine&);
    V_P_JournalLine(V_P_JournalLine&&) noexcept;
    V_P_JournalLine& operator=(const V_P_JournalLine&);
    V_P_JournalLine& operator=(V_P_JournalLine&&) noexcept;
    ~V_P_JournalLine();
    size_t size() const { return used_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_; }
    const E_V_P_JournalLine& entry(size_t i) const { return data_[i]; }
    void push_back(P_JournalLine);
    void append(P_JournalLine v) { push_back(static_cast<P_JournalLine&&>(v)); }
    P_JournalLine& at(I i) { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    const P_JournalLine& at(I i) const { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    P_JournalLine& operator[](I i) { return at(i); }
    const P_JournalLine& operator[](I i) const { return at(i); }
};
struct Entry_V_P_JournalLine {
    const V_P_JournalLine *source;size_t index;
    P_JournalLine value_copy() const { return source->entry(index).value; }
    I key() const { return I(static_cast<int64_t>(index)); }
};
struct Iterator_V_P_JournalLine {
    const V_P_JournalLine *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_V_P_JournalLine& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_V_P_JournalLine& x) const { return index!=x.index; }
    Entry_V_P_JournalLine operator*() const { return {source,index}; }
};
struct Range_V_P_JournalLine {
    const V_P_JournalLine *source;
    Iterator_V_P_JournalLine begin() const { Iterator_V_P_JournalLine i{source,0};i.skip();return i; }
    Iterator_V_P_JournalLine end() const { return {source,source->slots()}; }
};
inline Range_V_P_JournalLine foreach_range(const V_P_JournalLine& v) { return {&v}; }
namespace php { inline I count(const V_P_JournalLine& v) { return I(static_cast<int64_t>(v.size())); }
}
struct E_V_P_PaymentProposalRow { P_PaymentProposalRow value{}; };
class V_P_PaymentProposalRow {
    E_V_P_PaymentProposalRow *data_=nullptr;
    size_t used_=0,capacity_=0;
    void grow();
public:
    V_P_PaymentProposalRow()=default;
    V_P_PaymentProposalRow(const V_P_PaymentProposalRow&);
    V_P_PaymentProposalRow(V_P_PaymentProposalRow&&) noexcept;
    V_P_PaymentProposalRow& operator=(const V_P_PaymentProposalRow&);
    V_P_PaymentProposalRow& operator=(V_P_PaymentProposalRow&&) noexcept;
    ~V_P_PaymentProposalRow();
    size_t size() const { return used_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_; }
    const E_V_P_PaymentProposalRow& entry(size_t i) const { return data_[i]; }
    void push_back(P_PaymentProposalRow);
    void append(P_PaymentProposalRow v) { push_back(static_cast<P_PaymentProposalRow&&>(v)); }
    P_PaymentProposalRow& at(I i) { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    const P_PaymentProposalRow& at(I i) const { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    P_PaymentProposalRow& operator[](I i) { return at(i); }
    const P_PaymentProposalRow& operator[](I i) const { return at(i); }
};
struct Entry_V_P_PaymentProposalRow {
    const V_P_PaymentProposalRow *source;size_t index;
    P_PaymentProposalRow value_copy() const { return source->entry(index).value; }
    I key() const { return I(static_cast<int64_t>(index)); }
};
struct Iterator_V_P_PaymentProposalRow {
    const V_P_PaymentProposalRow *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_V_P_PaymentProposalRow& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_V_P_PaymentProposalRow& x) const { return index!=x.index; }
    Entry_V_P_PaymentProposalRow operator*() const { return {source,index}; }
};
struct Range_V_P_PaymentProposalRow {
    const V_P_PaymentProposalRow *source;
    Iterator_V_P_PaymentProposalRow begin() const { Iterator_V_P_PaymentProposalRow i{source,0};i.skip();return i; }
    Iterator_V_P_PaymentProposalRow end() const { return {source,source->slots()}; }
};
inline Range_V_P_PaymentProposalRow foreach_range(const V_P_PaymentProposalRow& v) { return {&v}; }
namespace php { inline I count(const V_P_PaymentProposalRow& v) { return I(static_cast<int64_t>(v.size())); }
}
struct E_V_P_StockLot { P_StockLot value{}; };
class V_P_StockLot {
    E_V_P_StockLot *data_=nullptr;
    size_t used_=0,capacity_=0;
    void grow();
public:
    V_P_StockLot()=default;
    V_P_StockLot(const V_P_StockLot&);
    V_P_StockLot(V_P_StockLot&&) noexcept;
    V_P_StockLot& operator=(const V_P_StockLot&);
    V_P_StockLot& operator=(V_P_StockLot&&) noexcept;
    ~V_P_StockLot();
    size_t size() const { return used_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_; }
    const E_V_P_StockLot& entry(size_t i) const { return data_[i]; }
    void push_back(P_StockLot);
    void append(P_StockLot v) { push_back(static_cast<P_StockLot&&>(v)); }
    P_StockLot& at(I i) { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    const P_StockLot& at(I i) const { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    P_StockLot& operator[](I i) { return at(i); }
    const P_StockLot& operator[](I i) const { return at(i); }
};
struct Entry_V_P_StockLot {
    const V_P_StockLot *source;size_t index;
    P_StockLot value_copy() const { return source->entry(index).value; }
    I key() const { return I(static_cast<int64_t>(index)); }
};
struct Iterator_V_P_StockLot {
    const V_P_StockLot *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_V_P_StockLot& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_V_P_StockLot& x) const { return index!=x.index; }
    Entry_V_P_StockLot operator*() const { return {source,index}; }
};
struct Range_V_P_StockLot {
    const V_P_StockLot *source;
    Iterator_V_P_StockLot begin() const { Iterator_V_P_StockLot i{source,0};i.skip();return i; }
    Iterator_V_P_StockLot end() const { return {source,source->slots()}; }
};
inline Range_V_P_StockLot foreach_range(const V_P_StockLot& v) { return {&v}; }
namespace php { inline I count(const V_P_StockLot& v) { return I(static_cast<int64_t>(v.size())); }
}
struct E_V_P_TrialBalanceRow { P_TrialBalanceRow value{}; };
class V_P_TrialBalanceRow {
    E_V_P_TrialBalanceRow *data_=nullptr;
    size_t used_=0,capacity_=0;
    void grow();
public:
    V_P_TrialBalanceRow()=default;
    V_P_TrialBalanceRow(const V_P_TrialBalanceRow&);
    V_P_TrialBalanceRow(V_P_TrialBalanceRow&&) noexcept;
    V_P_TrialBalanceRow& operator=(const V_P_TrialBalanceRow&);
    V_P_TrialBalanceRow& operator=(V_P_TrialBalanceRow&&) noexcept;
    ~V_P_TrialBalanceRow();
    size_t size() const { return used_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_; }
    const E_V_P_TrialBalanceRow& entry(size_t i) const { return data_[i]; }
    void push_back(P_TrialBalanceRow);
    void append(P_TrialBalanceRow v) { push_back(static_cast<P_TrialBalanceRow&&>(v)); }
    P_TrialBalanceRow& at(I i) { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    const P_TrialBalanceRow& at(I i) const { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    P_TrialBalanceRow& operator[](I i) { return at(i); }
    const P_TrialBalanceRow& operator[](I i) const { return at(i); }
};
struct Entry_V_P_TrialBalanceRow {
    const V_P_TrialBalanceRow *source;size_t index;
    P_TrialBalanceRow value_copy() const { return source->entry(index).value; }
    I key() const { return I(static_cast<int64_t>(index)); }
};
struct Iterator_V_P_TrialBalanceRow {
    const V_P_TrialBalanceRow *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_V_P_TrialBalanceRow& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_V_P_TrialBalanceRow& x) const { return index!=x.index; }
    Entry_V_P_TrialBalanceRow operator*() const { return {source,index}; }
};
struct Range_V_P_TrialBalanceRow {
    const V_P_TrialBalanceRow *source;
    Iterator_V_P_TrialBalanceRow begin() const { Iterator_V_P_TrialBalanceRow i{source,0};i.skip();return i; }
    Iterator_V_P_TrialBalanceRow end() const { return {source,source->slots()}; }
};
inline Range_V_P_TrialBalanceRow foreach_range(const V_P_TrialBalanceRow& v) { return {&v}; }
namespace php { inline I count(const V_P_TrialBalanceRow& v) { return I(static_cast<int64_t>(v.size())); }
}
struct E_V_string_t { string_t value{}; };
class V_string_t {
    E_V_string_t *data_=nullptr;
    size_t used_=0,capacity_=0;
    void grow();
public:
    V_string_t()=default;
    V_string_t(const V_string_t&);
    V_string_t(V_string_t&&) noexcept;
    V_string_t& operator=(const V_string_t&);
    V_string_t& operator=(V_string_t&&) noexcept;
    ~V_string_t();
    size_t size() const { return used_; }
    size_t slots() const { return used_; }
    bool live(size_t i) const { return i<used_; }
    const E_V_string_t& entry(size_t i) const { return data_[i]; }
    void push_back(string_t);
    void append(string_t v) { push_back(static_cast<string_t&&>(v)); }
    string_t& at(I i) { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    const string_t& at(I i) const { if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }
    string_t& operator[](I i) { return at(i); }
    const string_t& operator[](I i) const { return at(i); }
};
struct Entry_V_string_t {
    const V_string_t *source;size_t index;
    string_t value_copy() const { return source->entry(index).value; }
    I key() const { return I(static_cast<int64_t>(index)); }
};
struct Iterator_V_string_t {
    const V_string_t *source;size_t index;
    void skip() { while(index<source->slots() && !source->live(index)) ++index; }
    Iterator_V_string_t& operator++() { ++index;skip();return *this; }
    bool operator!=(const Iterator_V_string_t& x) const { return index!=x.index; }
    Entry_V_string_t operator*() const { return {source,index}; }
};
struct Range_V_string_t {
    const V_string_t *source;
    Iterator_V_string_t begin() const { Iterator_V_string_t i{source,0};i.skip();return i; }
    Iterator_V_string_t end() const { return {source,source->slots()}; }
};
inline Range_V_string_t foreach_range(const V_string_t& v) { return {&v}; }
namespace php { inline I count(const V_string_t& v) { return I(static_cast<int64_t>(v.size())); }
}
inline V_I convert_V_I(const V_I& x) { return x; }
inline V_P_BankStatementLine convert_V_P_BankStatementLine(const V_P_BankStatementLine& x) { return x; }
}
