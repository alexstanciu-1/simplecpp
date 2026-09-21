#include <scpp/lang/php.hpp>
#include "__types/LlvmApiHandleMapSummaryRow.hpp"
#include "__types/LlvmApiModuleBuildRow.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_module_build_stable_hash.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_handle_summary_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_llvm_api_sink_preflight_module_build_stable_hash(LlvmApiModuleBuildRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::module_build_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[32]);
	string_t identity = required_cast<string_t>(string_t("llvm_api_module_build:v1:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->module_build_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->owner_source_unit_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(row->input_emission_hash) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->function_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->basic_block_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->value_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_llvm_api_sink_preflight_handle_summary_stable_hash(LlvmApiHandleMapSummaryRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::handle_summary_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[33]);
	string_t identity = required_cast<string_t>(string_t("llvm_api_handle_map_summary:v1:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->summary_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->module_build_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->function_handle_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->block_handle_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->value_handle_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->local_slot_handle_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->unresolved_handle_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)));
	return php::stable_hash_string_u64(identity);
}

}
