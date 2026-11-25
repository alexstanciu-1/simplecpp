#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct DeterministicWorkOrderRow;
DeterministicWorkOrderRow __latency_fn_deterministic_work_ordering_row(int_t<std::uint32_t> workId, int_t<std::uint16_t> executionModelId, int_t<std::uint16_t> workKindId, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> symbolId, int_t<std::uint32_t> partitionId, int_t<std::uint32_t> partitionLocalOrderId, int_t<std::uint32_t> completionOrderId, int_t<std::uint32_t> outputOrderId);
}
