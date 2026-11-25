#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentIncrementalFileChangeRow;
struct ResidentIncrementalTransactionRow;
struct ResidentProjectSnapshotRow;
ResidentIncrementalFileChangeRow __latency_fn_resident_transactions_make_file_change(ResidentIncrementalTransactionRow transaction, ResidentProjectSnapshotRow current, ResidentProjectSnapshotRow previous, int_t<std::uint16_t> changeKindId, int_t<std::uint16_t> dirtyStatusId, int_t<std::uint16_t> reuseStatusId);
}
