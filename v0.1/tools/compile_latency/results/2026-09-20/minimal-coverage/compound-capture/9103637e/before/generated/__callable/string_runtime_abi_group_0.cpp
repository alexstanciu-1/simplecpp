#include <scpp/lang/php.hpp>
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__types/string_runtime_abi.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_row_by_bridge_row_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_bridge_row_id_string_literal_cstr_ctor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_from_cstr_descriptor.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_text_coercion_row_by_source_type_ref_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_text_coercion_descriptor_for_source_type_ref.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_row_by_bridge_row_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_bridge_row_id_php_echo_eval_string.hpp"
#include "__callable/__latency_fn_string_runtime_abi_echo_string_descriptor.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_row_by_bridge_row_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_bridge_row_id_string_release.hpp"
#include "__callable/__latency_fn_string_runtime_abi_release_descriptor.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_row_by_bridge_row_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_bridge_row_id_string_concat_assign_string.hpp"
#include "__callable/__latency_fn_string_runtime_abi_concat_assign_descriptor.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_row_by_bridge_row_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_bridge_row_id_string_compare_lexicographic.hpp"
#include "__callable/__latency_fn_string_runtime_abi_compare_descriptor.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_source_consumption_is_blocked.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_source_consumption_is_ready.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_consumes_for_cleanup.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_consumes_owned_argument.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_has_borrowed_arguments.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_requires_explicit_cleanup.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_ready_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_echo_string_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_from_cstr_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_release_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_source_consumption_is_ready.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_consumes_for_cleanup.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_consumes_owned_argument.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_has_borrowed_arguments.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_requires_explicit_cleanup.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_ready_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_echo_string_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_release_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_source_consumption_is_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_text_coercion_descriptor_for_source_type_ref.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_consumes_for_cleanup.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_consumes_owned_argument.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_has_borrowed_arguments.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_requires_explicit_cleanup.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_ready_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_compare_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_comparison_source_slice_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_echo_string_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_from_cstr_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_release_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_source_consumption_is_ready.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_text_coercion_family_php_text_integer_widths_to_string_i64_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_integer_text_coercion_family_id.hpp"
namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
bool_t string_runtime_abi::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == string_runtime_abi::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_string_runtime_abi_literal_from_cstr_descriptor(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::literal_from_cstr_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[0]);
	return __latency_fn_runtime_abi_bridge_row_by_bridge_row_id(artifact, __latency_fn_semantic_runtime_abi_bridge_bridge_row_id_string_literal_cstr_ctor());
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_string_runtime_abi_text_coercion_descriptor_for_source_type_ref(shared_p<RuntimeAbiBridgeArtifact> artifact, int_t<std::uint32_t> sourceTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::text_coercion_descriptor_for_source_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[1]);
	return __latency_fn_runtime_abi_bridge_text_coercion_row_by_source_type_ref_id(artifact, sourceTypeRefId);
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_string_runtime_abi_echo_string_descriptor(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::echo_string_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[2]);
	return __latency_fn_runtime_abi_bridge_row_by_bridge_row_id(artifact, __latency_fn_semantic_runtime_abi_bridge_bridge_row_id_php_echo_eval_string());
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_string_runtime_abi_release_descriptor(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::release_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[3]);
	return __latency_fn_runtime_abi_bridge_row_by_bridge_row_id(artifact, __latency_fn_semantic_runtime_abi_bridge_bridge_row_id_string_release());
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_string_runtime_abi_concat_assign_descriptor(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::concat_assign_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[4]);
	return __latency_fn_runtime_abi_bridge_row_by_bridge_row_id(artifact, __latency_fn_semantic_runtime_abi_bridge_bridge_row_id_string_concat_assign_string());
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_string_runtime_abi_compare_descriptor(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::compare_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[5]);
	return __latency_fn_runtime_abi_bridge_row_by_bridge_row_id(artifact, __latency_fn_semantic_runtime_abi_bridge_bridge_row_id_string_compare_lexicographic());
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
bool_t __latency_fn_string_runtime_abi_source_consumption_is_blocked(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::source_consumption_is_blocked", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[6]);
	return bool_t(php::identical(cast<int_t<>>(row->source_consumption_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_source_consumption_blocked_id())));
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
bool_t __latency_fn_string_runtime_abi_source_consumption_is_ready(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::source_consumption_is_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[7]);
	return bool_t(php::identical(cast<int_t<>>(row->source_consumption_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_source_consumption_ready_id())));
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
bool_t __latency_fn_string_runtime_abi_literal_echo_source_slice_ready(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::literal_echo_source_slice_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[8]);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> literal = __latency_fn_string_runtime_abi_literal_from_cstr_descriptor(artifact);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> echoString = __latency_fn_string_runtime_abi_echo_string_descriptor(artifact);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> release = __latency_fn_string_runtime_abi_release_descriptor(artifact);
	return (((((((((php::identical(cast<int_t<>>(literal->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id())) && php::identical(cast<int_t<>>(echoString->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && php::identical(cast<int_t<>>(release->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && __latency_fn_string_runtime_abi_source_consumption_is_ready(literal)) && __latency_fn_string_runtime_abi_source_consumption_is_ready(echoString)) && __latency_fn_string_runtime_abi_source_consumption_is_ready(release)) && __latency_fn_runtime_helper_effects_requires_explicit_cleanup(literal)) && __latency_fn_runtime_helper_effects_has_borrowed_arguments(echoString)) && __latency_fn_runtime_helper_effects_consumes_owned_argument(release)) && __latency_fn_runtime_helper_effects_consumes_for_cleanup(release));
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
bool_t __latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready(shared_p<RuntimeAbiBridgeArtifact> artifact, int_t<std::uint32_t> sourceTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::runtime_text_coercion_echo_source_slice_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[9]);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> textCoercion = __latency_fn_string_runtime_abi_text_coercion_descriptor_for_source_type_ref(artifact, cast<int_t<std::uint32_t>>(sourceTypeRefId));
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> echoString = __latency_fn_string_runtime_abi_echo_string_descriptor(artifact);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> release = __latency_fn_string_runtime_abi_release_descriptor(artifact);
	return ((((((((((((cast<int_t<>>(textCoercion->bridge_row_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(textCoercion->source_type_ref_id), cast<int_t<>>(sourceTypeRefId))) && php::identical(cast<int_t<>>(textCoercion->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && php::identical(cast<int_t<>>(echoString->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && php::identical(cast<int_t<>>(release->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && __latency_fn_string_runtime_abi_source_consumption_is_ready(textCoercion)) && __latency_fn_string_runtime_abi_source_consumption_is_ready(echoString)) && __latency_fn_string_runtime_abi_source_consumption_is_ready(release)) && __latency_fn_runtime_helper_effects_requires_explicit_cleanup(textCoercion)) && __latency_fn_runtime_helper_effects_has_borrowed_arguments(echoString)) && __latency_fn_runtime_helper_effects_consumes_owned_argument(release)) && __latency_fn_runtime_helper_effects_consumes_for_cleanup(release));
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
bool_t __latency_fn_string_runtime_abi_comparison_source_slice_ready(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::comparison_source_slice_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[10]);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> literal = __latency_fn_string_runtime_abi_literal_from_cstr_descriptor(artifact);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> compare = __latency_fn_string_runtime_abi_compare_descriptor(artifact);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> echoString = __latency_fn_string_runtime_abi_echo_string_descriptor(artifact);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> release = __latency_fn_string_runtime_abi_release_descriptor(artifact);
	return ((((((((((((((cast<int_t<>>(compare->bridge_row_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(compare->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && php::identical(cast<int_t<>>(literal->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && php::identical(cast<int_t<>>(echoString->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && php::identical(cast<int_t<>>(release->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))) && __latency_fn_string_runtime_abi_source_consumption_is_ready(compare)) && __latency_fn_string_runtime_abi_source_consumption_is_ready(literal)) && __latency_fn_string_runtime_abi_source_consumption_is_ready(echoString)) && __latency_fn_string_runtime_abi_source_consumption_is_ready(release)) && __latency_fn_runtime_helper_effects_has_borrowed_arguments(compare)) && __latency_fn_runtime_helper_effects_requires_explicit_cleanup(literal)) && __latency_fn_runtime_helper_effects_has_borrowed_arguments(echoString)) && __latency_fn_runtime_helper_effects_consumes_owned_argument(release)) && __latency_fn_runtime_helper_effects_consumes_for_cleanup(release));
}

}

namespace scpp { extern const int __latency_lines_string_runtime_abi[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_string_runtime_abi_integer_text_coercion_family_id() {
	SCPP_CALL_DEPTH_GUARD("string_runtime_abi::integer_text_coercion_family_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/string_runtime_abi.phs", __latency_lines_string_runtime_abi[11]);
	return __latency_fn_semantic_runtime_abi_bridge_text_coercion_family_php_text_integer_widths_to_string_i64_id();
}

}
