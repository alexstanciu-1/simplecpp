#include <scpp/lang/php.hpp>
#include "__callable/__latency_fn_llvm_text_from_plan__norm_append_after_return_branch_boundary__activeIfElseLabel.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan__norm_append_after_return_branch_boundary__activeIfEndLabel.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan__norm_append_after_return_branch_boundary__returned.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_after_return_branch_boundary__exec.hpp"
#pragma once
namespace scpp {
	template <typename T_activeIfEndLabel, typename T_activeIfElseLabel, typename T_returned>
	void __latency_fn_llvm_text_from_plan_append_after_return_branch_boundary(str::text_builder& lines, T_activeIfEndLabel&& _activeIfEndLabel, int_t<std::uint32_t>& activeIfBodyLastSourceRowId, T_activeIfElseLabel&& _activeIfElseLabel, int_t<std::uint32_t>& activeIfElseBodyLastSourceRowId, int_t<std::uint32_t> currentSourceRowId, T_returned&& _returned) {
	string_t& activeIfEndLabel = __latency_fn_llvm_text_from_plan__norm_append_after_return_branch_boundary__activeIfEndLabel(std::forward<T_activeIfEndLabel>(_activeIfEndLabel));
	string_t& activeIfElseLabel = __latency_fn_llvm_text_from_plan__norm_append_after_return_branch_boundary__activeIfElseLabel(std::forward<T_activeIfElseLabel>(_activeIfElseLabel));
	bool_t& returned = __latency_fn_llvm_text_from_plan__norm_append_after_return_branch_boundary__returned(std::forward<T_returned>(_returned));
		__latency_fn_llvm_text_from_plan_append_after_return_branch_boundary__exec(lines, activeIfEndLabel, activeIfBodyLastSourceRowId, activeIfElseLabel, activeIfElseBodyLastSourceRowId, currentSourceRowId, returned);
	}

}
