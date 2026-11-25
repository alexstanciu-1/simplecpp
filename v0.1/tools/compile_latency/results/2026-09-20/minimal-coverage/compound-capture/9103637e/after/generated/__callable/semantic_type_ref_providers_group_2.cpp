#include <scpp/lang/php.hpp>
#include "__types/PrimitiveTypeRefSpellingDescriptorRow.hpp"
#include "__types/ProviderDescriptorRow.hpp"
#include "__types/ProviderTraitDescriptorRow.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_trait_descriptor.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_status_known_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_spelling_descriptor.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_byte_span_family_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_byte_span_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_curl_handle_family_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_curl_handle_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_null_family_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_null_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_descriptor.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_descriptors.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_source_buffer_family_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_source_buffer_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_source_line_index_family_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_source_line_index_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_source_location_family_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_source_location_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_string_family_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_string_parts_builder_family_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_string_parts_builder_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_string_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_text_builder_family_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_text_builder_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_token_buffer_family_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_token_buffer_type_ref_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_trait_descriptor.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_trait_descriptors.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_refs_bool_id.hpp"
#include "__callable/__latency_fn_type_refs_double_id.hpp"
#include "__callable/__latency_fn_type_refs_float_id.hpp"
#include "__callable/__latency_fn_type_refs_int16_id.hpp"
#include "__callable/__latency_fn_type_refs_int32_id.hpp"
#include "__callable/__latency_fn_type_refs_int64_id.hpp"
#include "__callable/__latency_fn_type_refs_int8_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_null_id.hpp"
#include "__callable/__latency_fn_type_refs_string_id.hpp"
#include "__callable/__latency_fn_type_refs_uint16_id.hpp"
#include "__callable/__latency_fn_type_refs_uint32_id.hpp"
#include "__callable/__latency_fn_type_refs_uint64_id.hpp"
#include "__callable/__latency_fn_type_refs_uint8_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_null_sentinel_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_opaque_runtime_string_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_f32_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_f64_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i16_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i1_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i32_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i64_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i8_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_u16_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_u32_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_u64_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_u8_id.hpp"
#include "__callable/__latency_fn_type_traits_alias_policy_canonical_self_id.hpp"
#include "__callable/__latency_fn_type_traits_alias_policy_language_int_to_int64_id.hpp"
#include "__callable/__latency_fn_type_traits_cleanup_policy_none_id.hpp"
#include "__callable/__latency_fn_type_traits_cleanup_policy_runtime_release_required_id.hpp"
#include "__callable/__latency_fn_type_traits_comparability_scalar_value_id.hpp"
#include "__callable/__latency_fn_type_traits_copy_policy_runtime_owned_or_borrowed_id.hpp"
#include "__callable/__latency_fn_type_traits_copy_policy_trivial_id.hpp"
#include "__callable/__latency_fn_type_traits_defaultability_runtime_empty_value_id.hpp"
#include "__callable/__latency_fn_type_traits_defaultability_zero_value_id.hpp"
#include "__callable/__latency_fn_type_traits_family_null_sentinel_id.hpp"
#include "__callable/__latency_fn_type_traits_family_primitive_scalar_id.hpp"
#include "__callable/__latency_fn_type_traits_family_runtime_string_id.hpp"
#include "__callable/__latency_fn_type_traits_hashability_scalar_value_id.hpp"
#include "__callable/__latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id.hpp"
#include "__callable/__latency_fn_type_traits_integer_width_policy_language_int_alias_i64_id.hpp"
#include "__callable/__latency_fn_type_traits_integer_width_policy_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_not_numeric_type_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_floating_point_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_signed_integer_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_unsigned_integer_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_floating_basic_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_signed_integer_basic_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_unsigned_integer_basic_id.hpp"
#include "__callable/__latency_fn_type_traits_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_storage_policy_inline_scalar_id.hpp"
#include "__callable/__latency_fn_type_traits_storage_policy_runtime_opaque_id.hpp"
#include "__callable/__latency_fn_type_traits_truthiness_bool_value_id.hpp"
#include "__callable/__latency_fn_type_traits_truthiness_numeric_zero_false_id.hpp"
namespace scpp { extern const int __latency_lines_semantic_type_ref_providers[]; }
namespace scpp {
ProviderTraitDescriptorRow __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(int_t<std::uint32_t> providerId, int_t<std::uint32_t> typeRefId, int_t<std::uint32_t> familyId, int_t<std::uint16_t> abiShapeId, int_t<std::uint16_t> storagePolicyId, int_t<std::uint16_t> copyPolicyId, int_t<std::uint16_t> cleanupPolicyId, int_t<std::uint16_t> truthinessId, int_t<std::uint16_t> comparabilityId, int_t<std::uint16_t> hashabilityId, int_t<std::uint16_t> defaultabilityId, int_t<std::uint16_t> abiAlignBytes, int_t<std::uint16_t> numericKindId, int_t<std::uint16_t> integerWidthBits, int_t<std::uint16_t> integerWidthPolicyId, int_t<std::uint16_t> aliasPolicyId, int_t<std::uint32_t> canonicalNumericTypeRefId, int_t<std::uint16_t> numericOperatorMask, int_t<std::uint16_t> numericStatusId, int_t<std::uint16_t> numericBlockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("semantic_type_ref_providers::provider_trait_descriptor", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_type_ref_providers.phs", __latency_lines_semantic_type_ref_providers[32]);
	ProviderTraitDescriptorRow row = ProviderTraitDescriptorRow{};
	row->provider_id = providerId;
	row->type_ref_id = typeRefId;
	row->family_id = familyId;
	row->abi_shape_id = abiShapeId;
	row->storage_policy_id = storagePolicyId;
	row->copy_policy_id = copyPolicyId;
	row->cleanup_policy_id = cleanupPolicyId;
	row->truthiness_id = truthinessId;
	row->comparability_id = comparabilityId;
	row->hashability_id = hashabilityId;
	row->defaultability_id = defaultabilityId;
	row->abi_align_bytes = abiAlignBytes;
	row->status_id = __latency_fn_semantic_type_ref_providers_status_known_id();
	row->numeric_kind_id = numericKindId;
	row->integer_width_bits = integerWidthBits;
	row->integer_width_policy_id = integerWidthPolicyId;
	row->alias_policy_id = aliasPolicyId;
	row->canonical_numeric_type_ref_id = canonicalNumericTypeRefId;
	row->numeric_operator_mask = numericOperatorMask;
	row->numeric_status_id = numericStatusId;
	row->numeric_blocked_reason_id = numericBlockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_semantic_type_ref_providers[]; }
namespace scpp {
shared_p<PrimitiveTypeRefSpellingDescriptorRow> __latency_fn_semantic_type_ref_providers_spelling_descriptor(int_t<std::uint32_t> typeRefId, const string_t& sourceName) {
	SCPP_CALL_DEPTH_GUARD("semantic_type_ref_providers::spelling_descriptor", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_type_ref_providers.phs", __latency_lines_semantic_type_ref_providers[33]);
	shared_p<PrimitiveTypeRefSpellingDescriptorRow> row = create<PrimitiveTypeRefSpellingDescriptorRow>();
	row->type_ref_id = typeRefId;
	row->source_name = sourceName;
	return row;
}

}

namespace scpp { extern const int __latency_lines_semantic_type_ref_providers[]; }
namespace scpp {
vector_t<ProviderDescriptorRow> __latency_fn_semantic_type_ref_providers_provider_descriptors() {
	SCPP_CALL_DEPTH_GUARD("semantic_type_ref_providers::provider_descriptors", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_type_ref_providers.phs", __latency_lines_semantic_type_ref_providers[34]);
	vector_t<ProviderDescriptorRow> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(10));
	{
	auto __latency_local_0 = __latency_fn_semantic_type_ref_providers_provider_descriptor(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_type_ref_providers_string_type_ref_id(), __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_string_family_id())));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_semantic_type_ref_providers_provider_descriptor(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_type_ref_providers_source_buffer_type_ref_id(), __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_source_buffer_family_id())));
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_semantic_type_ref_providers_provider_descriptor(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3)), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_type_ref_providers_byte_span_type_ref_id(), __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_byte_span_family_id())));
	(void) rows.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_semantic_type_ref_providers_provider_descriptor(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4)), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_type_ref_providers_token_buffer_type_ref_id(), __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_token_buffer_family_id())));
	(void) rows.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_semantic_type_ref_providers_provider_descriptor(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_type_ref_providers_source_line_index_type_ref_id(), __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_source_line_index_family_id())));
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_semantic_type_ref_providers_provider_descriptor(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6)), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_type_ref_providers_source_location_type_ref_id(), __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_source_location_family_id())));
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_semantic_type_ref_providers_provider_descriptor(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7)), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_type_ref_providers_string_parts_builder_type_ref_id(), __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_string_parts_builder_family_id())));
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_semantic_type_ref_providers_provider_descriptor(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_type_ref_providers_text_builder_type_ref_id(), __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_text_builder_family_id())));
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_semantic_type_ref_providers_provider_descriptor(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(9)), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_semantic_type_ref_providers_curl_handle_type_ref_id(), __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_curl_handle_family_id())));
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_semantic_type_ref_providers_provider_descriptor(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10)), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_type_ref_providers_null_type_ref_id(), __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_null_family_id())));
	(void) rows.push_back(__latency_local_9);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_semantic_type_ref_providers[]; }
namespace scpp {
vector_t<ProviderTraitDescriptorRow> __latency_fn_semantic_type_ref_providers_provider_trait_descriptors() {
	SCPP_CALL_DEPTH_GUARD("semantic_type_ref_providers::provider_trait_descriptors", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_type_ref_providers.phs", __latency_lines_semantic_type_ref_providers[35]);
	vector_t<ProviderTraitDescriptorRow> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(14));
	{
	auto __latency_local_0 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), __latency_fn_type_refs_int_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_i64_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_type_traits_numeric_kind_signed_integer_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(64)), __latency_fn_type_traits_integer_width_policy_language_int_alias_i64_id(), __latency_fn_type_traits_alias_policy_language_int_to_int64_id(), __latency_fn_type_refs_int64_id(), __latency_fn_type_traits_numeric_operator_mask_signed_integer_basic_id(), __latency_fn_type_traits_status_ready_id(), __latency_fn_type_traits_numeric_blocked_reason_none_id());
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(2)), __latency_fn_type_refs_int64_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_i64_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_type_traits_numeric_kind_signed_integer_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(64)), __latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_type_refs_int64_id(), __latency_fn_type_traits_numeric_operator_mask_signed_integer_basic_id(), __latency_fn_type_traits_status_ready_id(), __latency_fn_type_traits_numeric_blocked_reason_none_id());
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(3)), __latency_fn_type_refs_bool_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_i1_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_bool_value_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_traits_numeric_kind_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(0)), __latency_fn_type_traits_integer_width_policy_none_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_traits_numeric_operator_mask_none_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_not_numeric_type_id());
	(void) rows.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(4)), __latency_fn_type_refs_int8_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_i8_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_traits_numeric_kind_signed_integer_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_type_refs_int8_id(), __latency_fn_type_traits_numeric_operator_mask_signed_integer_basic_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id());
	(void) rows.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(5)), __latency_fn_type_refs_int16_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_i16_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_type_traits_numeric_kind_signed_integer_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(16)), __latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_type_refs_int16_id(), __latency_fn_type_traits_numeric_operator_mask_signed_integer_basic_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id());
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(6)), __latency_fn_type_refs_int32_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_i32_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4)), __latency_fn_type_traits_numeric_kind_signed_integer_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(32)), __latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_type_refs_int32_id(), __latency_fn_type_traits_numeric_operator_mask_signed_integer_basic_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id());
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(7)), __latency_fn_type_refs_uint8_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_u8_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_traits_numeric_kind_unsigned_integer_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_type_refs_uint8_id(), __latency_fn_type_traits_numeric_operator_mask_unsigned_integer_basic_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id());
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(8)), __latency_fn_type_refs_uint16_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_u16_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_type_traits_numeric_kind_unsigned_integer_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(16)), __latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_type_refs_uint16_id(), __latency_fn_type_traits_numeric_operator_mask_unsigned_integer_basic_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id());
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(9)), __latency_fn_type_refs_uint32_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_u32_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4)), __latency_fn_type_traits_numeric_kind_unsigned_integer_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(32)), __latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_type_refs_uint32_id(), __latency_fn_type_traits_numeric_operator_mask_unsigned_integer_basic_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id());
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(10)), __latency_fn_type_refs_uint64_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_u64_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_type_traits_numeric_kind_unsigned_integer_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(64)), __latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_type_refs_uint64_id(), __latency_fn_type_traits_numeric_operator_mask_unsigned_integer_basic_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id());
	(void) rows.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(11)), __latency_fn_type_refs_float_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_f32_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4)), __latency_fn_type_traits_numeric_kind_floating_point_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(32)), __latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_type_refs_float_id(), __latency_fn_type_traits_numeric_operator_mask_floating_basic_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id());
	(void) rows.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(12)), __latency_fn_type_refs_double_id(), __latency_fn_type_traits_family_primitive_scalar_id(), __latency_fn_type_traits_abi_shape_primitive_f64_id(), __latency_fn_type_traits_storage_policy_inline_scalar_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_type_traits_truthiness_numeric_zero_false_id(), __latency_fn_type_traits_comparability_scalar_value_id(), __latency_fn_type_traits_hashability_scalar_value_id(), __latency_fn_type_traits_defaultability_zero_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_type_traits_numeric_kind_floating_point_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(64)), __latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_type_refs_double_id(), __latency_fn_type_traits_numeric_operator_mask_floating_basic_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id());
	(void) rows.push_back(__latency_local_11);
	}
	{
	auto __latency_local_12 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(13)), __latency_fn_type_refs_string_id(), __latency_fn_type_traits_family_runtime_string_id(), __latency_fn_type_traits_abi_shape_opaque_runtime_string_id(), __latency_fn_type_traits_storage_policy_runtime_opaque_id(), __latency_fn_type_traits_copy_policy_runtime_owned_or_borrowed_id(), __latency_fn_type_traits_cleanup_policy_runtime_release_required_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_type_traits_defaultability_runtime_empty_value_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_type_traits_numeric_kind_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(0)), __latency_fn_type_traits_integer_width_policy_none_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_traits_numeric_operator_mask_none_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_not_numeric_type_id());
	(void) rows.push_back(__latency_local_12);
	}
	{
	auto __latency_local_13 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptor(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(14)), __latency_fn_type_refs_null_id(), __latency_fn_type_traits_family_null_sentinel_id(), __latency_fn_type_traits_abi_shape_null_sentinel_id(), __latency_fn_type_traits_storage_policy_runtime_opaque_id(), __latency_fn_type_traits_copy_policy_trivial_id(), __latency_fn_type_traits_cleanup_policy_none_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_type_traits_numeric_kind_none_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(0)), __latency_fn_type_traits_integer_width_policy_none_id(), __latency_fn_type_traits_alias_policy_canonical_self_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_traits_numeric_operator_mask_none_id(), __latency_fn_type_traits_status_blocked_id(), __latency_fn_type_traits_numeric_blocked_reason_not_numeric_type_id());
	(void) rows.push_back(__latency_local_13);
	}
	return rows;
}

}
