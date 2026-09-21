#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class MtStageEvaluationArtifact;
struct MtStageEvaluationRow;
MtStageEvaluationRow __latency_fn_mt_stage_evaluation_stage_row_by_kind(shared_p<MtStageEvaluationArtifact> artifact, int_t<std::uint16_t> stageKindId);
}
