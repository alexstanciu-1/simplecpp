#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/LlvmApiHandleMapSummaryRow.hpp"
#include "__types/LlvmApiModuleBuildRow.hpp"
#include "__types/LlvmApiSinkPreflightArtifact.hpp"
#include "__types/LlvmApiSinkPreflightRow.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_append_handle_summary.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_append_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_append_handle_summary.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_append_module_build.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_append_preflight.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_handle_summary_row_from_module_build.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_module_build_row_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_preflight_row_from_emission.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_append_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_from_emission.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_new_artifact.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_preflight_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_module_build_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_handle_summary_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_preflight_debug_string.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_name.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_module_build_debug_string.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_name.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_handle_summary_debug_string.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_name.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_preflight_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
void __latency_fn_llvm_api_sink_preflight_append_handle_summary(LlvmApiSinkPreflightArtifact& artifact, LlvmApiHandleMapSummaryRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::append_handle_summary", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[22]);
	row->summary_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->handle_summaries));
	(void) artifact->handle_summaries.append(row);
	artifact->handle_summary_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->handle_summaries));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_status_ready_id())))) {
		artifact->handle_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->handle_ready_count) + static_cast<int_t<> >(1)));
		return;
	}
	artifact->handle_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->handle_blocked_count) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
void __latency_fn_llvm_api_sink_preflight_append_from_emission(LlvmApiSinkPreflightArtifact& artifact, BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::append_from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[23]);
	int_t<std::uint32_t> preflightId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(artifact->preflights)));
	int_t<std::uint32_t> moduleBuildId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(artifact->module_builds)));
	int_t<std::uint32_t> summaryId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(artifact->handle_summaries)));
	LlvmApiModuleBuildRow moduleBuild = __latency_fn_llvm_api_sink_preflight_module_build_row_from_emission(cast<int_t<std::uint32_t>>(moduleBuildId), emission);
	__latency_fn_llvm_api_sink_preflight_append_preflight(artifact, __latency_fn_llvm_api_sink_preflight_preflight_row_from_emission(cast<int_t<std::uint32_t>>(preflightId), emission));
	__latency_fn_llvm_api_sink_preflight_append_module_build(artifact, moduleBuild);
	__latency_fn_llvm_api_sink_preflight_append_handle_summary(artifact, __latency_fn_llvm_api_sink_preflight_handle_summary_row_from_module_build(cast<int_t<std::uint32_t>>(summaryId), moduleBuild, emission));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
LlvmApiSinkPreflightArtifact __latency_fn_llvm_api_sink_preflight_from_emission(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::from_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[24]);
	LlvmApiSinkPreflightArtifact artifact = __latency_fn_llvm_api_sink_preflight_new_artifact(static_cast<int_t<> >(1));
	__latency_fn_llvm_api_sink_preflight_append_from_emission(artifact, emission);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
LlvmApiSinkPreflightRow __latency_fn_llvm_api_sink_preflight_preflight_by_id(LlvmApiSinkPreflightArtifact artifact, int_t<std::uint32_t> preflightId) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::preflight_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[25]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(preflightId, cast<int_t<>>(artifact->preflight_count))))) {
		LlvmApiSinkPreflightRow row = artifact->preflights[__latency_fn_structure_row_ids_dense_index(preflightId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->preflight_id), cast<int_t<>>(preflightId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->preflights;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->preflight_id), cast<int_t<>>(preflightId)))) {
			return row;
		}
	}
	LlvmApiSinkPreflightRow empty = LlvmApiSinkPreflightRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
LlvmApiModuleBuildRow __latency_fn_llvm_api_sink_preflight_module_build_by_id(LlvmApiSinkPreflightArtifact artifact, int_t<std::uint32_t> moduleBuildId) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::module_build_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[26]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(moduleBuildId, cast<int_t<>>(artifact->module_build_count))))) {
		LlvmApiModuleBuildRow row = artifact->module_builds[__latency_fn_structure_row_ids_dense_index(moduleBuildId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->module_build_id), cast<int_t<>>(moduleBuildId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->module_builds;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->module_build_id), cast<int_t<>>(moduleBuildId)))) {
			return row;
		}
	}
	LlvmApiModuleBuildRow empty = LlvmApiModuleBuildRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
LlvmApiHandleMapSummaryRow __latency_fn_llvm_api_sink_preflight_handle_summary_by_id(LlvmApiSinkPreflightArtifact artifact, int_t<std::uint32_t> summaryId) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::handle_summary_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[27]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(summaryId, cast<int_t<>>(artifact->handle_summary_count))))) {
		LlvmApiHandleMapSummaryRow row = artifact->handle_summaries[__latency_fn_structure_row_ids_dense_index(summaryId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->summary_id), cast<int_t<>>(summaryId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->handle_summaries;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->summary_id), cast<int_t<>>(summaryId)))) {
			return row;
		}
	}
	LlvmApiHandleMapSummaryRow empty = LlvmApiHandleMapSummaryRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
string_t __latency_fn_llvm_api_sink_preflight_preflight_debug_string(LlvmApiSinkPreflightRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::preflight_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[28]);
	string_t text = required_cast<string_t>(string_t("llvm_api_sink_preflight:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->preflight_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_llvm_api_sink_preflight_status_name(row->status_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
string_t __latency_fn_llvm_api_sink_preflight_module_build_debug_string(LlvmApiModuleBuildRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::module_build_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[29]);
	string_t text = required_cast<string_t>(string_t("llvm_api_module_build:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->module_build_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_llvm_api_sink_preflight_status_name(row->status_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
string_t __latency_fn_llvm_api_sink_preflight_handle_summary_debug_string(LlvmApiHandleMapSummaryRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::handle_summary_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[30]);
	string_t text = required_cast<string_t>(string_t("llvm_api_handle_map:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->summary_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_llvm_api_sink_preflight_status_name(row->status_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_llvm_api_sink_preflight_preflight_stable_hash(LlvmApiSinkPreflightRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::preflight_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[31]);
	string_t identity = required_cast<string_t>(string_t("llvm_api_sink_preflight:v1:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->preflight_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->sink_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->owner_source_unit_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->emission_decision_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->value_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->block_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->sidecar_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)));
	return php::stable_hash_string_u64(identity);
}

}
