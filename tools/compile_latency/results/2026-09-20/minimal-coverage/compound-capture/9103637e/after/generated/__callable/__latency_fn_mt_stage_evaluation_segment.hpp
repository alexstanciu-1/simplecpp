#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct MtWorkerSegmentRow;
MtWorkerSegmentRow __latency_fn_mt_stage_evaluation_segment(int_t<std::uint32_t> segmentId, int_t<std::uint16_t> stageKindId, int_t<std::uint16_t> parallelUnitId, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> workerId, int_t<std::uint32_t> partitionId, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> symbolId, int_t<std::uint32_t> inputSnapshotGeneration, int_t<std::uint32_t> completionOrderId, int_t<std::uint32_t> localRowCount, int_t<std::uint32_t> localRowCapacity, int_t<std::uint64_t> mergeOrderKey, int_t<std::uint32_t> publishedRowFirstId);
}
