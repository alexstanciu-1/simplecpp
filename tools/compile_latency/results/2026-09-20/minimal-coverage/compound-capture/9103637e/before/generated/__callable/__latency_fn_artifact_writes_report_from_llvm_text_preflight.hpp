#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ArtifactWriteReportRecord;
struct FunctionBodyTextEmissionPreflightRow;
class PipelineConfig;
shared_p<ArtifactWriteReportRecord> __latency_fn_artifact_writes_report_from_llvm_text_preflight(shared_p<PipelineConfig> config, FunctionBodyTextEmissionPreflightRow preflight, const string_t& moduleText);
}
