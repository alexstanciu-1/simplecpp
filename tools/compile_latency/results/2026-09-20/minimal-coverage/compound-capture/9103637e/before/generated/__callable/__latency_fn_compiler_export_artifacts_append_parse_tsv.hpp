#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts__norm_append_parse_tsv__text.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_append_parse_tsv__exec.hpp"
#pragma once
namespace scpp {
class FrontendModel;
struct SourceUnitTableRow;
	template <typename T_text>
	void __latency_fn_compiler_export_artifacts_append_parse_tsv(T_text&& _text, SourceUnitTableRow sourceUnit, shared_p<FrontendModel> model) {
	string_t& text = __latency_fn_compiler_export_artifacts__norm_append_parse_tsv__text(std::forward<T_text>(_text));
		__latency_fn_compiler_export_artifacts_append_parse_tsv__exec(text, sourceUnit, model);
	}

}
