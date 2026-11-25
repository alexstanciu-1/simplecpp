#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class MtStageEvaluationArtifact;
struct MtStageMeasurementRow;
void __latency_fn_mt_stage_evaluation_append_measurement(shared_p<MtStageEvaluationArtifact>& artifact, MtStageMeasurementRow row);
}
