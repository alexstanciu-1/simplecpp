#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class MtStageEvaluationArtifact;
struct MtWorkerSegmentRow;
MtWorkerSegmentRow __latency_fn_mt_stage_evaluation_first_published_segment(shared_p<MtStageEvaluationArtifact> artifact, int_t<std::uint16_t> stageKindId);
}
