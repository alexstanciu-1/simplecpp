#include <scpp/lang/php.hpp>
#include "__types/SourceReadResultRow.hpp"
#include "__types/SourceReadWorkerResult.hpp"
#include "__callable/__latency_fn_source_units__norm_source_read_worker_result_from_row__sourceText.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_result_from_row__exec.hpp"
#pragma once
namespace scpp {
struct SourceReadResultRow;
class SourceReadWorkerResult;
	template <typename T_sourceText>
	shared_p<SourceReadWorkerResult> __latency_fn_source_units_source_read_worker_result_from_row(SourceReadResultRow row, T_sourceText&& _sourceText) {
	string_t& sourceText = __latency_fn_source_units__norm_source_read_worker_result_from_row__sourceText(std::forward<T_sourceText>(_sourceText));
		return __latency_fn_source_units_source_read_worker_result_from_row__exec(row, sourceText);
	}

}
