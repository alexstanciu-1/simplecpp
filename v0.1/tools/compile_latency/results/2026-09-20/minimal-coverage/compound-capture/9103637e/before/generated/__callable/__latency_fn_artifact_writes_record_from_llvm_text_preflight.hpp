#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ArtifactWriteRecord;
struct FunctionBodyTextEmissionPreflightRow;
class PipelineConfig;
ArtifactWriteRecord __latency_fn_artifact_writes_record_from_llvm_text_preflight(shared_p<PipelineConfig> config, FunctionBodyTextEmissionPreflightRow preflight, const string_t& moduleText);
}
