#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendBodySummaryArtifact;
struct FrontendLocalBindingSummaryRow;
int_t<std::uint32_t> __latency_fn_frontend_body_summaries_append_local(shared_p<FrontendBodySummaryArtifact>& artifact, FrontendLocalBindingSummaryRow row);
}
