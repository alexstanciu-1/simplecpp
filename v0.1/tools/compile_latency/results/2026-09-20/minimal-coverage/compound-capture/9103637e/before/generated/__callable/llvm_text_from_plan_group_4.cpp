#include <scpp/lang/php.hpp>
#include "__types/PrimitiveAbiAdapterRow.hpp"
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_integer_echo_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_integer_echo_i64_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_any_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_integer_echo_i64_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_runtime_string_echo_abi_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_runtime_text_coercion_echo_abi_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_integer_echo_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_type_policy_supports_type_ref.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_storage_policy_runtime_opaque_id.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_for_step_kind.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_type_policy_supports_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_integer_echo_i64_conversion_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_integer_echo_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_unsigned_integer_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_integer_echo_i64_operand.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_integer_echo_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_estimated_local_body_text_bytes.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_estimated_echo_body_text_bytes.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_reserve_local_slot_lookup_vectors.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_slot_index.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_type_ref_for_slot_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
bool_t __latency_fn_llvm_text_from_plan_integer_echo_supported_for_type_ref(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::integer_echo_supported_for_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[45]);
	return __latency_fn_primitive_abi_adapter_matrix_integer_echo_i64_supported_for_type_ref(typeRefId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
bool_t __latency_fn_llvm_text_from_plan_type_policy_supports_type_ref(int_t<std::uint16_t> typePolicyId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::type_policy_supports_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[46]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(typePolicyId), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_type_policy_any_id())))) {
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typePolicyId), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_type_policy_integer_echo_i64_id())))) {
		return __latency_fn_llvm_text_from_plan_integer_echo_supported_for_type_ref(cast<int_t<std::uint32_t>>(typeRefId));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typePolicyId), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_type_policy_runtime_string_echo_abi_id())))) {
		TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
		return (php::identical(cast<int_t<>>(trait->storage_policy_id), cast<int_t<>>(__latency_fn_type_traits_storage_policy_runtime_opaque_id())) && __latency_fn_string_runtime_abi_literal_echo_source_slice_ready(__latency_fn_runtime_abi_bridge_build()));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typePolicyId), cast<int_t<>>(__latency_fn_backend_emission_adapter_routes_type_policy_runtime_text_coercion_echo_abi_id())))) {
		return __latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready(__latency_fn_runtime_abi_bridge_build(), typeRefId);
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
bool_t __latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref(int_t<std::uint16_t> stepKindId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::step_type_policy_supports_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[47]);
	return __latency_fn_llvm_text_from_plan_type_policy_supports_type_ref(__latency_fn_backend_emission_adapter_routes_type_policy_for_step_kind(stepKindId), cast<int_t<std::uint32_t>>(typeRefId));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_integer_echo_i64_conversion_text(int_t<std::uint32_t> typeRefId, const string_t& sourceOperand, const string_t& targetName) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::integer_echo_i64_conversion_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[48]);
	if (static_cast<bool>((!__latency_fn_llvm_text_from_plan_integer_echo_supported_for_type_ref(cast<int_t<std::uint32_t>>(typeRefId))))) {
		return string_t("");
	}
	shared_p<PrimitiveAbiAdapterRow> row = __latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id(typeRefId);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->width_bits), static_cast<int_t<> >(64)))) {
		return string_t("");
	}
	string_t extensionOpcode = required_cast<string_t>(string_t("sext"));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_unsigned_integer_id())))) {
		extensionOpcode = string_t("zext");
	}
	return (string_t("  %") + cast<string_t>(targetName) + string_t(" = ") + cast<string_t>(extensionOpcode) + string_t(" ") + cast<string_t>(row->llvm_type) + string_t(" ") + cast<string_t>(sourceOperand) + string_t(" to i64\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_integer_echo_i64_operand(int_t<std::uint32_t> typeRefId, const string_t& sourceOperand, const string_t& targetName) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::integer_echo_i64_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[49]);
	if (static_cast<bool>((!__latency_fn_llvm_text_from_plan_integer_echo_supported_for_type_ref(cast<int_t<std::uint32_t>>(typeRefId))))) {
		return string_t("");
	}
	shared_p<PrimitiveAbiAdapterRow> row = __latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id(typeRefId);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->width_bits), static_cast<int_t<> >(64)))) {
		return sourceOperand;
	}
	return (string_t("%") + cast<string_t>(targetName));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_estimated_local_body_text_bytes(int_t<> valueCount) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::estimated_local_body_text_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[50]);
	return ((valueCount * static_cast<int_t<> >(56)) + static_cast<int_t<> >(2048));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_estimated_echo_body_text_bytes(int_t<> valueCount) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::estimated_echo_body_text_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[51]);
	return ((valueCount * static_cast<int_t<> >(64)) + static_cast<int_t<> >(512));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_reserve_local_slot_lookup_vectors(vector_t<int_t<std::uint32_t>>& localSourceRows, vector_t<int_t<std::uint32_t>>& localTypeRefs, int_t<> localOperandCount) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::reserve_local_slot_lookup_vectors", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[52]);
	int_t<> slotCapacity = required_cast<int_t<>>((cast<int_t<>>((localOperandCount / static_cast<int_t<> >(2))) + static_cast<int_t<> >(2)));
	php::vector_reserve(localSourceRows, slotCapacity);
	php::vector_reserve(localTypeRefs, slotCapacity);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_local_slot_index(vector_t<int_t<std::uint32_t>>& localSourceRows, int_t<std::uint32_t> localSourceRowId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::local_slot_index", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[53]);
	int_t<> count = required_cast<int_t<>>(php::count(localSourceRows));
	if (static_cast<bool>((count > static_cast<int_t<> >(0)))) {
		int_t<std::uint32_t> lastSourceRowId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(localSourceRows.at((count - static_cast<int_t<> >(1)))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(lastSourceRowId), cast<int_t<>>(localSourceRowId)))) {
			return count;
		}
	}
	int_t<> low = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> high = required_cast<int_t<>>((count - static_cast<int_t<> >(1)));
	while (static_cast<bool>((low <= high))) {
		int_t<> middle = required_cast<int_t<>>(cast<int_t<>>(((low + high) / static_cast<int_t<> >(2))));
		int_t<std::uint32_t> sourceRowId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(localSourceRows.at(middle)));
		if (static_cast<bool>(php::identical(cast<int_t<>>(sourceRowId), cast<int_t<>>(localSourceRowId)))) {
			return (middle + static_cast<int_t<> >(1));
		}
		if (static_cast<bool>((cast<int_t<>>(sourceRowId) < cast<int_t<>>(localSourceRowId)))) {
			low = (middle + static_cast<int_t<> >(1));
		}
		else {
			high = (middle - static_cast<int_t<> >(1));
		}
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = localSourceRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceRowId = __latency_local_1.value_copy();
		index = (index + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::identical(cast<int_t<>>(sourceRowId), cast<int_t<>>(localSourceRowId)))) {
			return index;
		}
	}
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_llvm_text_from_plan_local_type_ref_for_slot_index(vector_t<int_t<std::uint32_t>>& localTypeRefs, int_t<> slotIndex) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::local_type_ref_for_slot_index", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[54]);
	if (static_cast<bool>(((slotIndex <= static_cast<int_t<> >(0)) || (slotIndex > php::count(localTypeRefs))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	return cast<int_t<std::uint32_t>>(localTypeRefs.at((slotIndex - static_cast<int_t<> >(1))));
}

}
