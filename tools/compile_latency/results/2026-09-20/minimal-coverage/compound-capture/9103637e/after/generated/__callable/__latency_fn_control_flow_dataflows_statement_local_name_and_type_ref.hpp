#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__callable/__latency_fn_control_flow_dataflows__norm_statement_local_name_and_type_ref__name.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_statement_local_name_and_type_ref__exec.hpp"
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
struct FrontendStatementPayloadRow;
	template <typename T_name>
	bool_t __latency_fn_control_flow_dataflows_statement_local_name_and_type_ref(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, T_name&& _name, int_t<std::uint32_t>& typeRefId, shared_p<FrontendModelKernelCounters> counters) {
	string_t& name = __latency_fn_control_flow_dataflows__norm_statement_local_name_and_type_ref__name(std::forward<T_name>(_name));
		return __latency_fn_control_flow_dataflows_statement_local_name_and_type_ref__exec(model, sourceText, statementNode, statement, name, typeRefId, counters);
	}

}
