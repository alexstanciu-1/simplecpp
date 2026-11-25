#include <scpp/lang/php.hpp>
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts__norm_append_tokens_tsv__text.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_append_tokens_tsv__exec.hpp"
#pragma once
namespace scpp {
struct SourceUnitTableRow;
class TokenStream;
	template <typename T_text>
	void __latency_fn_pure_tdd_stage_artifacts_append_tokens_tsv(T_text&& _text, SourceUnitTableRow sourceUnit, shared_p<TokenStream> tokens) {
	string_t& text = __latency_fn_pure_tdd_stage_artifacts__norm_append_tokens_tsv__text(std::forward<T_text>(_text));
		__latency_fn_pure_tdd_stage_artifacts_append_tokens_tsv__exec(text, sourceUnit, tokens);
	}

}
