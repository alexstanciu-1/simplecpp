#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendBodySummaryArtifact;
struct SourceRangeRow;
int_t<std::uint32_t> __latency_fn_frontend_body_summaries_local_id_for_name_range(shared_p<FrontendBodySummaryArtifact>& artifact, const string_t& sourceText, SourceRangeRow nameRange);
}
