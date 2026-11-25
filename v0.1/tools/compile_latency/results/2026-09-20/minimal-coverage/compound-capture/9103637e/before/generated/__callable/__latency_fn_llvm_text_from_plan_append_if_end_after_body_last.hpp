#include <scpp/lang/php.hpp>
#include "__callable/__latency_fn_llvm_text_from_plan__norm_append_if_end_after_body_last__activeIfElseLabel.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan__norm_append_if_end_after_body_last__activeIfEndLabel.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_end_after_body_last__exec.hpp"
#pragma once
namespace scpp {
	template <typename T_activeIfEndLabel, typename T_activeIfElseLabel>
	void __latency_fn_llvm_text_from_plan_append_if_end_after_body_last(str::text_builder& lines, T_activeIfEndLabel&& _activeIfEndLabel, int_t<std::uint32_t>& activeIfBodyLastSourceRowId, T_activeIfElseLabel&& _activeIfElseLabel, int_t<std::uint32_t>& activeIfElseBodyLastSourceRowId, int_t<std::uint32_t> currentSourceRowId) {
	string_t& activeIfEndLabel = __latency_fn_llvm_text_from_plan__norm_append_if_end_after_body_last__activeIfEndLabel(std::forward<T_activeIfEndLabel>(_activeIfEndLabel));
	string_t& activeIfElseLabel = __latency_fn_llvm_text_from_plan__norm_append_if_end_after_body_last__activeIfElseLabel(std::forward<T_activeIfElseLabel>(_activeIfElseLabel));
		__latency_fn_llvm_text_from_plan_append_if_end_after_body_last__exec(lines, activeIfEndLabel, activeIfBodyLastSourceRowId, activeIfElseLabel, activeIfElseBodyLastSourceRowId, currentSourceRowId);
	}

}
