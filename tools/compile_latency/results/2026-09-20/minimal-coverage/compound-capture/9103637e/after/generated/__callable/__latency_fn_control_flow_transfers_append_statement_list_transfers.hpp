#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_control_flow_transfers__norm_append_statement_list_transfers__text.hpp"
#include "__callable/__latency_fn_control_flow_transfers_append_statement_list_transfers__exec.hpp"
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct ProjectSymbolIndexRow;
	template <typename T_text>
	void __latency_fn_control_flow_transfers_append_statement_list_transfers(T_text&& _text, int_t<std::uint32_t>& rowCount, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, int_t<std::uint32_t> firstStatementNodeId, int_t<std::uint32_t> enclosingLoopNodeId, int_t<std::uint16_t> loopDepth, shared_p<FrontendModelKernelCounters> counters) {
	string_t& text = __latency_fn_control_flow_transfers__norm_append_statement_list_transfers__text(std::forward<T_text>(_text));
		__latency_fn_control_flow_transfers_append_statement_list_transfers__exec(text, rowCount, symbol, model, firstStatementNodeId, enclosingLoopNodeId, loopDepth, counters);
	}

}
