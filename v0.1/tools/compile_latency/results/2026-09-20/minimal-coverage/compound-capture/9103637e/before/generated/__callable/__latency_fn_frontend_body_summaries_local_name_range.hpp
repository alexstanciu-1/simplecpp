#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendBodySummaryArtifact;
struct FrontendLocalBindingSummaryRow;
struct SourceRangeRow;
SourceRangeRow __latency_fn_frontend_body_summaries_local_name_range(shared_p<FrontendBodySummaryArtifact> artifact, FrontendLocalBindingSummaryRow local);
}
