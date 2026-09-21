#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendBodySummaryArtifact;
struct FrontendDeclarationPayloadRow;
class FrontendModel;
shared_p<FrontendBodySummaryArtifact> __latency_fn_frontend_body_summaries_from_declaration(shared_p<FrontendModel> model, const string_t& sourceText, FrontendDeclarationPayloadRow declaration);
}
