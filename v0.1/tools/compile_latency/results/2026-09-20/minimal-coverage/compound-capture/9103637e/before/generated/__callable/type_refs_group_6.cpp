#include <scpp/lang/php.hpp>
#include "__types/PrimitiveTypeRefDescriptorRow.hpp"
#include "__types/PrimitiveTypeRefSpellingDescriptorRow.hpp"
#include "__types/ProviderDescriptorRow.hpp"
#include "__types/TypeArgRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_matches_type_args.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_type_ref_id_from_table.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_type_refs_arg_kind_type_id.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_source_name_from_table.hpp"
#include "__callable/__latency_fn_type_refs_family_name_by_id.hpp"
#include "__callable/__latency_fn_type_refs_row_by_id.hpp"
#include "__callable/__latency_fn_type_refs_type_arg_count.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_family_instance_id.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_refs_primitive_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_primitive_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_refs_source_type_ref_id_for_name.hpp"
#include "__callable/__latency_fn_type_refs_source_type_ref_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_refs_is_primitive.hpp"
#include "__callable/__latency_fn_type_refs_primitive_descriptor_by_id.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_refs_int64_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_is_i64_alias_type_ref_id.hpp"
#include "__callable/__latency_fn_type_refs_is_i64_alias_type_ref_id.hpp"
#include "__callable/__latency_fn_type_refs_scalar_value_type_compatible_without_conversion.hpp"
#include "__callable/__latency_fn_type_refs_return_value_type_compatible_without_conversion.hpp"
#include "__callable/__latency_fn_type_refs_scalar_value_type_compatible_without_conversion.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_refs_artifact_kind_type_ref_table_id.hpp"
#include "__callable/__latency_fn_type_refs_lookup_model_project_symbols_id.hpp"
#include "__callable/__latency_fn_type_refs_new_table.hpp"
#include "__callable/__latency_fn_type_refs_generic_family_instance_row_by_id.hpp"
#include "__callable/__latency_fn_type_refs_primitive_descriptor_by_id.hpp"
#include "__callable/__latency_fn_type_refs_provider_descriptor_by_type_ref_id.hpp"
#include "__callable/__latency_fn_type_refs_provider_type_ref_row.hpp"
#include "__callable/__latency_fn_type_refs_row_from_id.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_void_id.hpp"
#include "__callable/__latency_fn_type_refs_type_status_known_id.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_refs_void_id.hpp"
namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_refs_family_instance_type_ref_id_from_table(TypeRefTable table, int_t<std::uint32_t> familyId, const vector_t<int_t<std::uint32_t>>& typeArgRefIds) {
	SCPP_CALL_DEPTH_GUARD("type_refs::family_instance_type_ref_id_from_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[74]);
	auto __latency_local_0 = table->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_type_refs_family_instance_matches_type_args(table, row->type_ref_id, cast<int_t<std::uint32_t>>(familyId), typeArgRefIds)))) {
			return row->type_ref_id;
		}
	}
	return __latency_fn_type_refs_unknown_id();
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
string_t __latency_fn_type_refs_family_instance_source_name_from_table(TypeRefTable table, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::family_instance_source_name_from_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[75]);
	TypeRefRow row = __latency_fn_type_refs_row_by_id(table, cast<int_t<std::uint32_t>>(typeRefId));
	if (static_cast<bool>((php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(__latency_fn_type_refs_unknown_id())) || php::not_identical(cast<int_t<>>(row->type_kind_id), cast<int_t<>>(__latency_fn_type_refs_type_kind_family_instance_id()))))) {
		return string_t("");
	}
	string_t name = required_cast<string_t>((cast<string_t>(__latency_fn_type_refs_family_name_by_id(row->family_id)) + string_t("<")));
	int_t<> argCount = required_cast<int_t<>>(__latency_fn_type_refs_type_arg_count(table, cast<int_t<std::uint32_t>>(typeRefId)));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < argCount))) {
		if (static_cast<bool>((index > static_cast<int_t<> >(0)))) {
			name = (cast<string_t>(name) + string_t(","));
		}
		string_t argName = required_cast<string_t>(string_t("unknown"));
		auto __latency_local_0 = table->args;
		for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
			auto argRow = __latency_local_1.value_copy();
			if (static_cast<bool>(((php::identical(cast<int_t<>>(argRow->parent_type_ref_id), cast<int_t<>>(typeRefId)) && php::identical(cast<int_t<>>(argRow->arg_index), index)) && php::identical(cast<int_t<>>(argRow->arg_kind_id), cast<int_t<>>(__latency_fn_type_refs_arg_kind_type_id()))))) {
				string_t nestedName = required_cast<string_t>(__latency_fn_type_refs_family_instance_source_name_from_table(table, argRow->type_arg_ref_id));
				if (static_cast<bool>(php::condition_truthy(php::not_identical(nestedName, string_t(""))))) {
					argName = nestedName;
				}
				else {
					argName = __latency_fn_type_ref_identity_name(argRow->type_arg_ref_id);
				}
			}
		}
		name = (cast<string_t>(name) + cast<string_t>(argName));
		index = (index + static_cast<int_t<> >(1));
	}
	return (cast<string_t>(name) + string_t(">"));
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_refs_primitive_type_ref_id_for_name(const string_t& sourceName) {
	SCPP_CALL_DEPTH_GUARD("type_refs::primitive_type_ref_id_for_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[76]);
	auto __latency_local_0 = __latency_fn_type_refs_primitive_spelling_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(row->source_name, sourceName))) {
			return row->type_ref_id;
		}
	}
	return __latency_fn_type_refs_unknown_id();
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_refs_source_type_ref_id_for_name(const string_t& sourceName) {
	SCPP_CALL_DEPTH_GUARD("type_refs::source_type_ref_id_for_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[77]);
	auto __latency_local_0 = __latency_fn_type_refs_source_type_ref_spelling_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(row->source_name, sourceName))) {
			return row->type_ref_id;
		}
	}
	return __latency_fn_type_refs_unknown_id();
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
bool_t __latency_fn_type_refs_is_primitive(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::is_primitive", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[78]);
	PrimitiveTypeRefDescriptorRow row = __latency_fn_type_refs_primitive_descriptor_by_id(cast<int_t<std::uint32_t>>(typeRefId));
	return bool_t(php::not_identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(__latency_fn_type_refs_unknown_id())));
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
bool_t __latency_fn_type_refs_is_i64_alias_type_ref_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::is_i64_alias_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[79]);
	return (php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_int_id())) || php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_int64_id())));
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
bool_t __latency_fn_type_refs_scalar_value_type_compatible_without_conversion(int_t<std::uint32_t> expectedTypeRefId, int_t<std::uint32_t> valueTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::scalar_value_type_compatible_without_conversion", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[80]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(expectedTypeRefId), cast<int_t<>>(valueTypeRefId)))) {
		return bool_t(static_cast<bool_t>(true));
	}
	return (__latency_fn_type_refs_is_i64_alias_type_ref_id(cast<int_t<std::uint32_t>>(expectedTypeRefId)) && __latency_fn_type_refs_is_i64_alias_type_ref_id(cast<int_t<std::uint32_t>>(valueTypeRefId)));
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
bool_t __latency_fn_type_refs_return_value_type_compatible_without_conversion(int_t<std::uint32_t> expectedTypeRefId, int_t<std::uint32_t> valueTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::return_value_type_compatible_without_conversion", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[81]);
	return __latency_fn_type_refs_scalar_value_type_compatible_without_conversion(cast<int_t<std::uint32_t>>(expectedTypeRefId), cast<int_t<std::uint32_t>>(valueTypeRefId));
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
TypeRefTable __latency_fn_type_refs_new_table(int_t<> typeCapacity, int_t<> argCapacity) {
	SCPP_CALL_DEPTH_GUARD("type_refs::new_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[82]);
	TypeRefTable table = TypeRefTable{};
	table->artifact_kind_id = __latency_fn_type_refs_artifact_kind_type_ref_table_id();
	table->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	table->lookup_model_id = __latency_fn_type_refs_lookup_model_project_symbols_id();
	php::vector_reserve(table->types, typeCapacity);
	php::vector_reserve(table->args, argCapacity);
	return table;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
TypeRefRow __latency_fn_type_refs_row_from_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::row_from_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[83]);
	TypeRefRow row = TypeRefRow{};
	row->type_ref_id = typeRefId;
	PrimitiveTypeRefDescriptorRow primitive = __latency_fn_type_refs_primitive_descriptor_by_id(cast<int_t<std::uint32_t>>(typeRefId));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(primitive->type_ref_id), cast<int_t<>>(__latency_fn_type_refs_unknown_id()))))) {
		row->type_kind_id = primitive->type_kind_id;
		row->status_id = primitive->status_id;
	}
	TypeRefRow genericFamily = __latency_fn_type_refs_generic_family_instance_row_by_id(cast<int_t<std::uint32_t>>(typeRefId));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(genericFamily->type_ref_id), cast<int_t<>>(__latency_fn_type_refs_unknown_id()))))) {
		return genericFamily;
	}
	ProviderDescriptorRow providerDescriptor = __latency_fn_type_refs_provider_descriptor_by_type_ref_id(cast<int_t<std::uint32_t>>(typeRefId));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(providerDescriptor->type_ref_id), cast<int_t<>>(__latency_fn_type_refs_unknown_id()))))) {
		return __latency_fn_type_refs_provider_type_ref_row(providerDescriptor);
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_void_id())))) {
		row->type_kind_id = __latency_fn_type_refs_type_kind_void_id();
		row->status_id = __latency_fn_type_refs_type_status_known_id();
	}
	return row;
}

}
