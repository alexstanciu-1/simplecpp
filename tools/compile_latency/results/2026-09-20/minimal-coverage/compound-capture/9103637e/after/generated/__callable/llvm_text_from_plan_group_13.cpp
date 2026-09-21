#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendStringLiteralOperandRow.hpp"
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_string_local_echo_calls.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_string_runtime_abi_echo_string_descriptor.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_text_coercion_local_echo_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_integer_echo_i64_conversion_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_integer_echo_i64_operand.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_echo_string_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_release_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_text_coercion_descriptor_for_source_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_predicate_from_generated_icmp_opcode.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_string_compare_predicate_for_operator_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_operator_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_predicate_from_generated_icmp_opcode.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls_from_global_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_rhs_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_string_compare_ternary_echo_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_string_compare_predicate_for_operator_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_string_runtime_abi_compare_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_comparison_source_slice_ready.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_all_string_literal_globals_from_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_string_literal_global_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_emission_uses_runtime_string_abi.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_opaque_runtime_string_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_work_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_string_literal_global_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_string_literal_globals_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_runtime_string_local_echo_calls(str::text_builder& lines, int_t<> valueId, int_t<> slotIndex, int_t<> align) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_runtime_string_local_echo_calls", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[115]);
	shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> echoString = __latency_fn_string_runtime_abi_echo_string_descriptor(bridge);
	__latency_fn_llvm_text_from_plan_append_local_load_text(lines, valueId, slotIndex, string_t("ptr"), align);
	__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(lines, echoString, string_t(""), (string_t("ptr %local_load_") + cast<string_t>(valueId)));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_runtime_text_coercion_local_echo_calls(str::text_builder& lines, int_t<> valueId, int_t<> slotIndex, int_t<std::uint32_t> typeRefId, int_t<> align) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_runtime_text_coercion_local_echo_calls", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[116]);
	shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> textCoercion = __latency_fn_string_runtime_abi_text_coercion_descriptor_for_source_type_ref(bridge, typeRefId);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> echoString = __latency_fn_string_runtime_abi_echo_string_descriptor(bridge);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> release = __latency_fn_string_runtime_abi_release_descriptor(bridge);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(textCoercion->argument_carrier_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_primitive_i64_id()))))) {
		return;
	}
	string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(cast<int_t<std::uint32_t>>(typeRefId)));
	__latency_fn_llvm_text_from_plan_append_local_load_text(lines, valueId, slotIndex, llvmType, align);
	string_t sourceOperand = required_cast<string_t>(string_t("%local_load_"));
	sourceOperand = (cast<string_t>(sourceOperand) + cast<string_t>(valueId));
	string_t targetName = required_cast<string_t>(string_t("runtime_i64_"));
	targetName = (cast<string_t>(targetName) + cast<string_t>(valueId));
	string_t conversionText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_integer_echo_i64_conversion_text(cast<int_t<std::uint32_t>>(typeRefId), sourceOperand, targetName));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(conversionText, string_t(""))))) {
		str::text_builder_append_string(lines, conversionText);
	}
	string_t i64Operand = required_cast<string_t>(__latency_fn_llvm_text_from_plan_integer_echo_i64_operand(cast<int_t<std::uint32_t>>(typeRefId), sourceOperand, targetName));
	if (static_cast<bool>(php::identical(i64Operand, string_t("")))) {
		return;
	}
	string_t resultName = required_cast<string_t>(string_t("%runtime_string_"));
	resultName = (cast<string_t>(resultName) + cast<string_t>(valueId));
	__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(lines, textCoercion, resultName, (string_t("i64 ") + cast<string_t>(i64Operand)));
	__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(lines, echoString, string_t(""), (string_t("ptr ") + cast<string_t>(resultName)));
	__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(lines, release, string_t(""), (string_t("ptr ") + cast<string_t>(resultName)));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_string_compare_predicate_for_operator_id(int_t<std::uint16_t> operatorId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::string_compare_predicate_for_operator_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[117]);
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_operator_id(operatorId);
	return __latency_fn_llvm_text_from_plan_predicate_from_generated_icmp_opcode(operatorRow->llvm_opcode);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_predicate_from_generated_icmp_opcode(const string_t& llvmOpcode) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::predicate_from_generated_icmp_opcode", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[118]);
	if (static_cast<bool>((str::length(llvmOpcode) <= static_cast<int_t<> >(5)))) {
		return string_t("");
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(str::substr(llvmOpcode, static_cast<int_t<> >(0), static_cast<int_t<> >(5)), string_t("icmp "))))) {
		return string_t("");
	}
	return str::substr(llvmOpcode, static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_runtime_string_compare_ternary_echo_calls(str::text_builder& lines, int_t<> valueId, int_t<> leftSlotIndex, int_t<> rightSlotIndex, int_t<std::uint16_t> operatorId, int_t<> align, shared_p<BackendStringLiteralOperandRow> thenLiteral, shared_p<BackendStringLiteralOperandRow> elseLiteral) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_runtime_string_compare_ternary_echo_calls", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[119]);
	string_t predicate = required_cast<string_t>(__latency_fn_llvm_text_from_plan_string_compare_predicate_for_operator_id(cast<int_t<std::uint16_t>>(operatorId)));
	if (static_cast<bool>((php::identical(predicate, string_t("")) || (!__latency_fn_string_runtime_abi_comparison_source_slice_ready(__latency_fn_runtime_abi_bridge_build()))))) {
		return;
	}
	shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> compare = __latency_fn_string_runtime_abi_compare_descriptor(bridge);
	__latency_fn_llvm_text_from_plan_append_local_load_text(lines, valueId, leftSlotIndex, string_t("ptr"), align);
	__latency_fn_llvm_text_from_plan_append_local_rhs_load_text(lines, valueId, rightSlotIndex, string_t("ptr"), align);
	string_t compareName = required_cast<string_t>(string_t("%string_compare_"));
	compareName = (cast<string_t>(compareName) + cast<string_t>(valueId));
	__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(lines, compare, compareName, (string_t("ptr %local_load_") + cast<string_t>(valueId) + string_t(", ptr %local_rhs_load_") + cast<string_t>(valueId)));
	string_t predicateName = required_cast<string_t>(string_t("%string_cmp_"));
	predicateName = (cast<string_t>(predicateName) + cast<string_t>(valueId));
	str::text_builder_append_string(lines, (string_t("  ") + cast<string_t>(predicateName) + string_t(" = icmp ") + cast<string_t>(predicate) + string_t(" i64 ") + cast<string_t>(compareName) + string_t(", 0\n")));
	str::text_builder_append_string(lines, (string_t("  br i1 ") + cast<string_t>(predicateName) + string_t(", label %string_ternary_then_") + cast<string_t>(valueId) + string_t(", label %string_ternary_else_") + cast<string_t>(valueId) + string_t("\n")));
	str::text_builder_append_string(lines, (string_t("string_ternary_then_") + cast<string_t>(valueId) + string_t(":\n")));
	__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls_from_global_id(lines, ((valueId * static_cast<int_t<> >(10)) + static_cast<int_t<> >(1)), cast<int_t<>>(thenLiteral->owner_row_id), thenLiteral);
	str::text_builder_append_string(lines, (string_t("  br label %string_ternary_end_") + cast<string_t>(valueId) + string_t("\n")));
	str::text_builder_append_string(lines, (string_t("string_ternary_else_") + cast<string_t>(valueId) + string_t(":\n")));
	__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls_from_global_id(lines, ((valueId * static_cast<int_t<> >(10)) + static_cast<int_t<> >(2)), cast<int_t<>>(elseLiteral->owner_row_id), elseLiteral);
	str::text_builder_append_string(lines, (string_t("  br label %string_ternary_end_") + cast<string_t>(valueId) + string_t("\n")));
	str::text_builder_append_string(lines, (string_t("string_ternary_end_") + cast<string_t>(valueId) + string_t(":\n")));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_all_string_literal_globals_from_backend_requests(str::text_builder& lines, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_all_string_literal_globals_from_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[120]);
	auto __latency_local_0 = requests->string_literal_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto literal = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(literal->owner_row_id) > static_cast<int_t<> >(0)))) {
			__latency_fn_llvm_text_from_plan_append_string_literal_global_text(lines, cast<int_t<>>(literal->owner_row_id), literal);
		}
	}
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
bool_t __latency_fn_llvm_text_from_plan_local_emission_uses_runtime_string_abi(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::local_emission_uses_runtime_string_abi", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[121]);
	if (static_cast<bool>((cast<int_t<>>(requests->string_literal_operand_count) > static_cast<int_t<> >(0)))) {
		return bool_t(static_cast<bool_t>(true));
	}
	auto __latency_local_0 = requests->local_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto localOperand = __latency_local_1.value_copy();
		TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(localOperand->type_ref_id);
		if (static_cast<bool>(php::identical(cast<int_t<>>(trait->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_opaque_runtime_string_id())))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_string_literal_globals_from_emission_and_backend_requests(str::text_builder& lines, BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_string_literal_globals_from_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[122]);
	int_t<> valueIndex = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((valueIndex <= cast<int_t<>>(emission->value_count)))) {
		BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, __latency_fn_structure_row_ids_uint32_from_int(valueIndex));
		valueIndex = (valueIndex + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(value->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_string_id()))))) {
			continue;
		}
		shared_p<BackendStringLiteralOperandRow> literal = __latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id(requests, __latency_fn_backend_emission_decisions_value_work_id(value));
		if (static_cast<bool>((cast<int_t<>>(literal->owner_row_id) > static_cast<int_t<> >(0)))) {
			__latency_fn_llvm_text_from_plan_append_string_literal_global_text(lines, cast<int_t<>>(value->value_id), literal);
		}
	}
}

}
