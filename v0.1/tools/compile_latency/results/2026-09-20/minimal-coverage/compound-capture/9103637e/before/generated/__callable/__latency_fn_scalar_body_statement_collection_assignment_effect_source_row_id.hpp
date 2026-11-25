#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
struct FrontendStatementPayloadRow;
int_t<std::uint32_t> __latency_fn_scalar_body_statement_collection_assignment_effect_source_row_id(shared_p<FrontendModel> model, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, shared_p<FrontendModelKernelCounters> counters);
}
