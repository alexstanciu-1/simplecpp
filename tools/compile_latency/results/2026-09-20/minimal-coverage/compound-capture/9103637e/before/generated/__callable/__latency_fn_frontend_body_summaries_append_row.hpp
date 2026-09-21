#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendBodySummaryArtifact;
struct FrontendBodySummaryRow;
void __latency_fn_frontend_body_summaries_append_row(shared_p<FrontendBodySummaryArtifact>& artifact, FrontendBodySummaryRow row);
}
