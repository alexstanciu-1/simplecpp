#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class MtStageEvaluationArtifact;
struct MtStageEvaluationRow;
void __latency_fn_mt_stage_evaluation_append_stage(shared_p<MtStageEvaluationArtifact>& artifact, MtStageEvaluationRow row);
}
