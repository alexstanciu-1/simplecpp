#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentIncrementalTransactionRow;
struct ResidentProjectSnapshotRow;
ResidentIncrementalTransactionRow __latency_fn_resident_transactions_make_transaction(ResidentProjectSnapshotRow current, ResidentProjectSnapshotRow previous, int_t<std::uint16_t> scenarioId, int_t<std::uint16_t> statusId, int_t<std::uint16_t> changeStatusId, int_t<std::uint16_t> reuseStatusId, int_t<std::uint32_t> changedFieldCount);
}
