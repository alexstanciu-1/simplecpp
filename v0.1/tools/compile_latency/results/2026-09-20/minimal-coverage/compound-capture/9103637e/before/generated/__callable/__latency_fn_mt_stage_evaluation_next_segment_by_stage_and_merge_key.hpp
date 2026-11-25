#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class MtStageEvaluationArtifact;
struct MtWorkerSegmentRow;
MtWorkerSegmentRow __latency_fn_mt_stage_evaluation_next_segment_by_stage_and_merge_key(shared_p<MtStageEvaluationArtifact> artifact, vector_t<int_t<std::uint32_t>>& selectedSegments, int_t<std::uint16_t> stageKindId);
}
