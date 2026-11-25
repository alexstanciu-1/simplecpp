#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ArtifactWriteRecord;
class ArtifactWriteReportRecord;
ArtifactWriteRecord __latency_fn_artifact_writes_row_by_id(shared_p<ArtifactWriteReportRecord> report, int_t<std::uint32_t> recordId);
}
