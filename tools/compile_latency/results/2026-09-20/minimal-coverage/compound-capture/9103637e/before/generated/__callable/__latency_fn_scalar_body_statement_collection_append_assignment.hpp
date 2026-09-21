#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
struct FrontendStatementPayloadRow;
class ScalarBodyBackendCollection;
bool_t __latency_fn_scalar_body_statement_collection_append_assignment(shared_p<FrontendModel> model, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, shared_p<ScalarBodyBackendCollection>& body);
}
