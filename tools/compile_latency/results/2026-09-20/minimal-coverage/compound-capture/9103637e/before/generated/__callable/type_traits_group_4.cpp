#include <scpp/lang/php.hpp>
#include "__types/ProviderTraitDescriptorRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__types/TypeTraitTable.hpp"
#include "__callable/__latency_fn_type_traits_descriptor_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_descriptor.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_trait_descriptors.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_type_ref_id_by_abi_shape_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_traits_artifact_kind_type_trait_id.hpp"
#include "__callable/__latency_fn_type_traits_build_lookup_index_if_recommended.hpp"
#include "__callable/__latency_fn_type_traits_lookup_index_threshold_traits.hpp"
#include "__callable/__latency_fn_type_traits_lookup_model_provider_trait_descriptor_id.hpp"
#include "__callable/__latency_fn_type_traits_lookup_policy_for_trait_count.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_table_from_type_refs.hpp"
#include "__callable/__latency_fn_type_traits_lookup_index_threshold_traits.hpp"
#include "__callable/__latency_fn_type_traits_lookup_policy_for_trait_count.hpp"
#include "__callable/__latency_fn_type_traits_lookup_policy_index_recommended_id.hpp"
#include "__callable/__latency_fn_type_traits_lookup_policy_scan_ok_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_traits_max_type_ref_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_traits_build_lookup_index_if_recommended.hpp"
#include "__callable/__latency_fn_type_traits_lookup_policy_index_recommended_id.hpp"
#include "__callable/__latency_fn_type_traits_max_type_ref_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_type_traits_row_by_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_type_traits_lookup_row_id_for_type_ref_id.hpp"
namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
TypeTraitRow __latency_fn_type_traits_row_from_type_ref_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::row_from_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[63]);
	return __latency_fn_type_traits_row_from_descriptor(__latency_fn_type_traits_descriptor_from_type_ref_id(cast<int_t<std::uint32_t>>(typeRefId)));
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_traits_type_ref_id_by_abi_shape_id(int_t<std::uint16_t> abiShapeId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::type_ref_id_by_abi_shape_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[64]);
	auto __latency_local_0 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto descriptor = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(descriptor->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id())) && php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(abiShapeId))))) {
			return descriptor->type_ref_id;
		}
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
TypeTraitTable __latency_fn_type_traits_table_from_type_refs(TypeRefTable typeRefs) {
	SCPP_CALL_DEPTH_GUARD("type_traits::table_from_type_refs", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[65]);
	TypeTraitTable table = TypeTraitTable{};
	table->artifact_kind_id = __latency_fn_type_traits_artifact_kind_type_trait_id();
	table->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
	table->lookup_model_id = __latency_fn_type_traits_lookup_model_provider_trait_descriptor_id();
	table->lookup_index_threshold = __latency_fn_type_traits_lookup_index_threshold_traits();
	php::vector_reserve(table->traits, php::count(typeRefs->types));
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		TypeTraitRow row = __latency_fn_type_traits_row_from_type_ref_id(typeRef->type_ref_id);
		(void) table->traits.append(row);
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->numeric_status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id())))) {
			table->numeric_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(table->numeric_ready_count) + static_cast<int_t<> >(1)));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->numeric_status_id), cast<int_t<>>(__latency_fn_type_traits_status_blocked_id())))) {
				table->numeric_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(table->numeric_blocked_count) + static_cast<int_t<> >(1)));
			}
		}
	}
	table->trait_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(table->traits));
	table->lookup_policy_status_id = __latency_fn_type_traits_lookup_policy_for_trait_count(table->trait_count);
	__latency_fn_type_traits_build_lookup_index_if_recommended(table);
	return table;
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_traits_lookup_policy_for_trait_count(int_t<std::uint32_t> traitCount) {
	SCPP_CALL_DEPTH_GUARD("type_traits::lookup_policy_for_trait_count", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[66]);
	if (static_cast<bool>((cast<int_t<>>(traitCount) > cast<int_t<>>(__latency_fn_type_traits_lookup_index_threshold_traits())))) {
		return __latency_fn_type_traits_lookup_policy_index_recommended_id();
	}
	return __latency_fn_type_traits_lookup_policy_scan_ok_id();
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_traits_max_type_ref_id(TypeTraitTable table) {
	SCPP_CALL_DEPTH_GUARD("type_traits::max_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[67]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = table->traits;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(row->type_ref_id) > maxId))) {
			maxId = cast<int_t<>>(row->type_ref_id);
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(maxId);
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
void __latency_fn_type_traits_build_lookup_index_if_recommended(TypeTraitTable& table) {
	SCPP_CALL_DEPTH_GUARD("type_traits::build_lookup_index_if_recommended", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[68]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(table->lookup_policy_status_id), cast<int_t<>>(__latency_fn_type_traits_lookup_policy_index_recommended_id()))))) {
		return;
	}
	int_t<std::uint32_t> maxTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_traits_max_type_ref_id(table));
	int_t<> slotCount = required_cast<int_t<>>(cast<int_t<>>(maxTypeRefId));
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	php::vector_reserve(table->trait_row_ids_by_type_ref_id, slotCount);
	while (static_cast<bool>((php::count(table->trait_row_ids_by_type_ref_id) < slotCount))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_none_id();
		(void) table->trait_row_ids_by_type_ref_id.append(__latency_local_0);
		}
	}
	int_t<> rowIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_1 = table->traits;
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto row = __latency_local_2.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(row->type_ref_id, slotCount)))) {
			table->trait_row_ids_by_type_ref_id[__latency_fn_structure_row_ids_dense_index(row->type_ref_id)] = __latency_fn_structure_row_ids_uint32_from_int((rowIndex + static_cast<int_t<> >(1)));
		}
		rowIndex = (rowIndex + static_cast<int_t<> >(1));
	}
	table->lookup_index_slot_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(table->trait_row_ids_by_type_ref_id));
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
TypeTraitRow __latency_fn_type_traits_row_by_row_id(TypeTraitTable table, int_t<std::uint32_t> rowId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::row_by_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[69]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(rowId, cast<int_t<>>(table->trait_count))))) {
		return table->traits[__latency_fn_structure_row_ids_dense_index(rowId)];
	}
	TypeTraitRow empty = TypeTraitRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_traits_lookup_row_id_for_type_ref_id(TypeTraitTable table, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::lookup_row_id_for_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[70]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(table->lookup_index_slot_count), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(typeRefId, php::count(table->trait_row_ids_by_type_ref_id))))) {
		return table->trait_row_ids_by_type_ref_id[__latency_fn_structure_row_ids_dense_index(typeRefId)];
	}
	return __latency_fn_structure_row_ids_none_id();
}

}
