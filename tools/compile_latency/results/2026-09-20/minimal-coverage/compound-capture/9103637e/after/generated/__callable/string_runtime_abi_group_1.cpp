#include <scpp/lang/php.hpp>
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticRuntimeAbiBridgeFamilyRow.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_by_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_family_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_family_row.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_member_type_ref_ids.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_family_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_source_type_refs.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_deferred_type_ref_ids.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_deferred_source_type_refs.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_family_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_ready_source_type_ref_count.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_source_type_refs.hpp"
#include "__callable/__latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_deferred_source_slice_blocked.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_deferred_source_type_refs.hpp"
#include "__callable/__latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_deferred_source_slice_blocked.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_deferred_source_type_refs.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_family_row.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_family_source_slice_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_ready_source_type_ref_count.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_family_source_slice_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_text_coercion_storage_slice_ready.hpp"
namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeFamilyRow> __latency_fn_string_runtime_abi_integer_text_coercion_family_row() {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::integer_text_coercion_family_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[12]);
	return __latency_fn_semantic_runtime_abi_bridge_text_coercion_family_by_id(__latency_fn_string_runtime_abi_integer_text_coercion_family_id());
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
vector_t<int_t<std::uint32_t>> __latency_fn_string_runtime_abi_integer_text_coercion_source_type_refs() {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::integer_text_coercion_source_type_refs", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[13]);
	return __latency_fn_semantic_runtime_abi_bridge_text_coercion_family_member_type_ref_ids(__latency_fn_string_runtime_abi_integer_text_coercion_family_id());
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
vector_t<int_t<std::uint32_t>> __latency_fn_string_runtime_abi_integer_text_coercion_deferred_source_type_refs() {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::integer_text_coercion_deferred_source_type_refs", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[14]);
	return __latency_fn_semantic_runtime_abi_bridge_text_coercion_family_deferred_type_ref_ids(__latency_fn_string_runtime_abi_integer_text_coercion_family_id());
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_string_runtime_abi_integer_text_coercion_ready_source_type_ref_count(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::integer_text_coercion_ready_source_type_ref_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[15]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = __latency_fn_string_runtime_abi_integer_text_coercion_source_type_refs();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRefId = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready(artifact, cast<int_t<std::uint32_t>>(typeRefId))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
bool_t __latency_fn_string_runtime_abi_integer_text_coercion_deferred_source_slice_blocked(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::integer_text_coercion_deferred_source_slice_blocked", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[16]);
	auto __latency_local_0 = __latency_fn_string_runtime_abi_integer_text_coercion_deferred_source_type_refs();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRefId = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready(artifact, cast<int_t<std::uint32_t>>(typeRefId))))) {
			return bool_t(static_cast<bool_t>(false));
		}
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
bool_t __latency_fn_string_runtime_abi_integer_text_coercion_family_source_slice_ready(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::integer_text_coercion_family_source_slice_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[17]);
	shared_p<SemanticRuntimeAbiBridgeFamilyRow> family = __latency_fn_string_runtime_abi_integer_text_coercion_family_row();
	return ((((cast<int_t<>>(family->family_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(__latency_fn_string_runtime_abi_integer_text_coercion_ready_source_type_ref_count(artifact)), cast<int_t<>>(family->member_count))) && php::identical(cast<int_t<>>(php::count(__latency_fn_string_runtime_abi_integer_text_coercion_deferred_source_type_refs())), cast<int_t<>>(family->deferred_member_count))) && __latency_fn_string_runtime_abi_integer_text_coercion_deferred_source_slice_blocked(artifact));
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
bool_t __latency_fn_string_runtime_abi_text_coercion_storage_slice_ready(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::text_coercion_storage_slice_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[18]);
	return (__latency_fn_string_runtime_abi_literal_echo_source_slice_ready(artifact) && __latency_fn_string_runtime_abi_integer_text_coercion_family_source_slice_ready(artifact));
}

}
