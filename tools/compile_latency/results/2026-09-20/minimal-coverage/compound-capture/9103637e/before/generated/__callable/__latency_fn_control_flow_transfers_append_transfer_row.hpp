#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_control_flow_transfers__norm_append_transfer_row__text.hpp"
#include "__callable/__latency_fn_control_flow_transfers_append_transfer_row__exec.hpp"
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
struct ProjectSymbolIndexRow;
	template <typename T_text>
	void __latency_fn_control_flow_transfers_append_transfer_row(T_text&& _text, int_t<std::uint32_t> rowId, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, FrontendNodeRow statementNode, int_t<std::uint16_t> transferKindId, int_t<std::uint32_t> enclosingLoopNodeId, int_t<std::uint16_t> loopDepth, shared_p<FrontendModelKernelCounters> counters) {
	string_t& text = __latency_fn_control_flow_transfers__norm_append_transfer_row__text(std::forward<T_text>(_text));
		__latency_fn_control_flow_transfers_append_transfer_row__exec(text, rowId, symbol, model, statementNode, transferKindId, enclosingLoopNodeId, loopDepth, counters);
	}

}
