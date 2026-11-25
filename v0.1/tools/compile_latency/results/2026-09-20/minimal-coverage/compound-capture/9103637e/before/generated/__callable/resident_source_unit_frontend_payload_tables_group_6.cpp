#include <scpp/lang/php.hpp>
#include "__types/SourceUnitFrontendWorkerBuildResult.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_build_result_for_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_build_results.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_build_results_are_publishable.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_input_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_tokenizer_elapsed_us_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_parser_elapsed_us_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_symbol_elapsed_us_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us_max.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_parser_elapsed_us_max.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_build_results(const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_build_results", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[74]);
	vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>> results = required_cast<vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>>(tasks::run(inputs, workerCount, [](shared_p<SourceUnitFrontendWorkerPayloadInput> input) -> shared_p<SourceUnitFrontendWorkerBuildResult> {
	return __latency_fn_resident_source_unit_frontend_payload_tables_worker_build_result_for_input(input);
}));
	return results;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_build_results_are_publishable(const shared_p<SourceUnitTable>& sourceUnits, const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_build_results_are_publishable", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[75]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(php::count(results), cast<int_t<>>(sourceUnits->source_unit_count))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		if (static_cast<bool>(((index >= php::count(results)) || php::not_identical(cast<int_t<>>(results.at(index)->source_unit_id), cast<int_t<>>(sourceUnit->source_unit_id))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_payload_copy_byte_total(const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_result_payload_copy_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[76]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(result->result_payload_copy_bytes));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_input_payload_copy_byte_total(const vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>>& inputs) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_input_payload_copy_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[77]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = inputs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto input = __latency_local_1.value_copy();
		total = (total + str::byte_length(input->source_text));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_tokenizer_elapsed_us_total(const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_result_tokenizer_elapsed_us_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[78]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(result->tokenizer_elapsed_us));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_parser_elapsed_us_total(const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_result_parser_elapsed_us_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[79]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(result->parser_elapsed_us));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_symbol_elapsed_us_total(const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_result_symbol_elapsed_us_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[80]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(result->symbol_worker_elapsed_us));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us(const shared_p<SourceUnitFrontendWorkerBuildResult>& result) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_result_elapsed_us", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[81]);
	return ((cast<int_t<>>(result->tokenizer_elapsed_us) + cast<int_t<>>(result->parser_elapsed_us)) + cast<int_t<>>(result->symbol_worker_elapsed_us));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us_total(const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_result_elapsed_us_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[82]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		total = (total + __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us(result));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us_max(const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_result_elapsed_us_max", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[83]);
	int_t<> max = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		int_t<> elapsed = required_cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_elapsed_us(result));
		if (static_cast<bool>((elapsed > max))) {
			max = elapsed;
		}
	}
	return max;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_result_parser_elapsed_us_max(const vector_t<shared_p<SourceUnitFrontendWorkerBuildResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_result_parser_elapsed_us_max", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[84]);
	int_t<> max = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(result->parser_elapsed_us) > max))) {
			max = cast<int_t<>>(result->parser_elapsed_us);
		}
	}
	return max;
}

}
