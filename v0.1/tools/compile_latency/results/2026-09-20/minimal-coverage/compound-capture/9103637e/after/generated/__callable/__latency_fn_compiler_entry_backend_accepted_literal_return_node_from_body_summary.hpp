#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct FrontendLiteralPayloadRow;
class FrontendModel;
struct FrontendNodeRow;
struct ProjectSymbolIndexRow;
FrontendNodeRow __latency_fn_compiler_entry_backend_accepted_literal_return_node_from_body_summary(ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText, FrontendLiteralPayloadRow& literal);
}
