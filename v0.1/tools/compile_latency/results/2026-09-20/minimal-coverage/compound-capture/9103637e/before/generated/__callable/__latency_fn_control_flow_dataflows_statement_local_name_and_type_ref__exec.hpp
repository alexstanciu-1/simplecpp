#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
struct FrontendStatementPayloadRow;
bool_t __latency_fn_control_flow_dataflows_statement_local_name_and_type_ref__exec(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, string_t& name, int_t<std::uint32_t>& typeRefId, shared_p<FrontendModelKernelCounters> counters);
}
