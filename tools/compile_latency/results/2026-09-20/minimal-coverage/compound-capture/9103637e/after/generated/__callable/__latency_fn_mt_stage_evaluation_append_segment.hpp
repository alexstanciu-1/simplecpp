#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class MtStageEvaluationArtifact;
struct MtWorkerSegmentRow;
void __latency_fn_mt_stage_evaluation_append_segment(shared_p<MtStageEvaluationArtifact>& artifact, MtWorkerSegmentRow row);
}
