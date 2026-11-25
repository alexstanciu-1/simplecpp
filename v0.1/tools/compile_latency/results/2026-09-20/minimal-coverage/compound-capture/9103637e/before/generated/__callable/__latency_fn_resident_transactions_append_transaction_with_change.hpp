#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentIncrementalFileChangeRow;
struct ResidentIncrementalTransactionRow;
void __latency_fn_resident_transactions_append_transaction_with_change(shared_p<CompilerProjectRunReport>& report, ResidentIncrementalTransactionRow transaction, ResidentIncrementalFileChangeRow change);
}
