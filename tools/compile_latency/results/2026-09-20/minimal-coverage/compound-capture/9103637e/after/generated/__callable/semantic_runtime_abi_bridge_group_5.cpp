#include <scpp/lang/php.hpp>
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_row_by_bridge_row_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_rows.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_rows.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_id_for_source_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_row_by_source_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_bridge_row_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_call_lowering_blocked_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_call_lowering_ready_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_debug_string.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_declaration_ready_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_opaque_call_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_owned_return_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_deferred_member_count.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_member_count.hpp"
namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_semantic_runtime_abi_bridge_row_by_bridge_row_id(int_t<std::uint16_t> bridgeRowId) {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::row_by_bridge_row_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[64]);
	auto __latency_local_0 = __latency_fn_semantic_runtime_abi_bridge_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->bridge_row_id), cast<int_t<>>(bridgeRowId)))) {
			return row;
		}
	}
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> empty = create<SemanticRuntimeAbiBridgeDescriptorRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_semantic_runtime_abi_bridge_text_coercion_row_by_source_type_ref_id(int_t<std::uint32_t> sourceTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::text_coercion_row_by_source_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[65]);
	auto __latency_local_0 = __latency_fn_semantic_runtime_abi_bridge_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->source_type_ref_id), cast<int_t<>>(sourceTypeRefId)) && (cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_id_for_source_type_ref_id(cast<int_t<std::uint32_t>>(row->source_type_ref_id))) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(row->return_carrier_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id()))))) {
			return row;
		}
	}
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> empty = create<SemanticRuntimeAbiBridgeDescriptorRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_runtime_abi_bridge[]; }
namespace scpp {
string_t __latency_fn_semantic_runtime_abi_bridge_debug_string() {
	SCPP_CALL_DEPTH_GUARD("semantic_runtime_abi_bridge::debug_string", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_runtime_abi_bridge.phs", __latency_lines_semantic_runtime_abi_bridge[66]);
	return (string_t("semantic_runtime_abi_bridge:rows=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_bridge_row_count())) + string_t(":declaration_ready=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_declaration_ready_count())) + string_t(":call_lowering_ready=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_call_lowering_ready_count())) + string_t(":call_lowering_blocked=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_call_lowering_blocked_count())) + string_t(":source_consumption_ready=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_count())) + string_t(":source_consumption_blocked=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_count())) + string_t(":opaque_calls=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_opaque_call_count())) + string_t(":owned_returns=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_owned_return_count())) + string_t(":text_families=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_count())) + string_t(":text_family_members=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_member_count())) + string_t(":text_family_deferred=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_deferred_member_count())) + string_t(":state=runtime_abi_bridge_metadata_ready_partial_source_consumption_ready"));
}

}
