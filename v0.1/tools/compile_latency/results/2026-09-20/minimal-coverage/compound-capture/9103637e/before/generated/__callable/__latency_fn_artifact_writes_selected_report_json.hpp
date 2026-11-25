#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ArtifactWriteReportRecord;
class PipelineConfig;
string_t __latency_fn_artifact_writes_selected_report_json(shared_p<PipelineConfig> config, shared_p<ArtifactWriteReportRecord> report);
}
