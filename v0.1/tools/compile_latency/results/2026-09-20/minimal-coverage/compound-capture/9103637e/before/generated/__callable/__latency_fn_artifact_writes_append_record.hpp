#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ArtifactWriteRecord;
class ArtifactWriteReportRecord;
void __latency_fn_artifact_writes_append_record(shared_p<ArtifactWriteReportRecord> report, ArtifactWriteRecord row);
}
