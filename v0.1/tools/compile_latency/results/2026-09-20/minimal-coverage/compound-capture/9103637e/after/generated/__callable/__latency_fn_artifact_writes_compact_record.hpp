#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ArtifactWriteRecord;
ArtifactWriteRecord __latency_fn_artifact_writes_compact_record(int_t<std::uint16_t> statusId, int_t<std::uint16_t> artifactKeyId, int_t<std::uint16_t> pathId, int_t<> byteCount);
}
