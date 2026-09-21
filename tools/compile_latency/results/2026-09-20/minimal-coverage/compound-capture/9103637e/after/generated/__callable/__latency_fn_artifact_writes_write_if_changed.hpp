#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ArtifactWriteRecord;
class PipelineConfig;
ArtifactWriteRecord __latency_fn_artifact_writes_write_if_changed(shared_p<PipelineConfig> config, int_t<std::uint16_t> artifactKeyId, int_t<std::uint16_t> pathId, const string_t& text);
}
