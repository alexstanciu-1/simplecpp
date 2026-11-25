#include <scpp/lang/php.hpp>
#include "__types/PrimitiveTypeRefDescriptorRow.hpp"
#include "__types/PrimitiveTypeRefSpellingDescriptorRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__callable/__latency_fn_type_refs_primitive_spelling_descriptor.hpp"
#include "__callable/__latency_fn_type_refs_primitive_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_provider_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_source_type_ref_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_void_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_provider_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_provider_source_name_by_id.hpp"
#include "__callable/__latency_fn_type_refs_provider_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_primitive_descriptor_by_id.hpp"
#include "__callable/__latency_fn_type_refs_primitive_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_type_ref_id.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_type_ref_id_for_type_args.hpp"
#include "__callable/__latency_fn_type_refs_family_nullable_id.hpp"
#include "__callable/__latency_fn_type_refs_family_result_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_nullable_int_id.hpp"
#include "__callable/__latency_fn_type_refs_result_int_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_type_ref_id_for_type_args.hpp"
#include "__callable/__latency_fn_type_refs_family_nullable_id.hpp"
#include "__callable/__latency_fn_type_refs_family_result_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_nullable_int_id.hpp"
#include "__callable/__latency_fn_type_refs_result_int_id.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_row.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_family_instance_id.hpp"
#include "__callable/__latency_fn_type_refs_type_status_known_id.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_row.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_type_ref_id.hpp"
#include "__callable/__latency_fn_type_refs_family_nullable_id.hpp"
#include "__callable/__latency_fn_type_refs_family_result_id.hpp"
#include "__callable/__latency_fn_type_refs_generic_family_instance_rows.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> __latency_fn_type_refs_source_type_ref_spelling_descriptors() {
	SCPP_CALL_DEPTH_GUARD("type_refs::source_type_ref_spelling_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[57]);
	vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> rows = required_cast<vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>>>(__latency_fn_type_refs_primitive_spelling_descriptors());
	{
	auto __latency_local_0 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_void_id(), string_t("void"));
	(void) rows.push_back(__latency_local_0);
	}
	auto __latency_local_1 = __latency_fn_type_refs_provider_spelling_descriptors();
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto row = __latency_local_2.value_copy();
		(void) rows.push_back(row);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> __latency_fn_type_refs_provider_spelling_descriptors() {
	SCPP_CALL_DEPTH_GUARD("type_refs::provider_spelling_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[58]);
	return __latency_fn_semantic_type_ref_providers_provider_spelling_descriptors();
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
string_t __latency_fn_type_refs_provider_source_name_by_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::provider_source_name_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[59]);
	auto __latency_local_0 = __latency_fn_type_refs_provider_spelling_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return row->source_name;
		}
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
PrimitiveTypeRefDescriptorRow __latency_fn_type_refs_primitive_descriptor_by_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::primitive_descriptor_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[60]);
	auto __latency_local_0 = __latency_fn_type_refs_primitive_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return row;
		}
	}
	PrimitiveTypeRefDescriptorRow empty = PrimitiveTypeRefDescriptorRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_refs_family_instance_type_ref_id(int_t<std::uint32_t> familyId, int_t<std::uint32_t> payloadTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::family_instance_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[61]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(familyId), cast<int_t<>>(__latency_fn_type_refs_family_result_id())) && php::identical(cast<int_t<>>(payloadTypeRefId), cast<int_t<>>(__latency_fn_type_refs_int_id()))))) {
		return __latency_fn_type_refs_result_int_id();
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(familyId), cast<int_t<>>(__latency_fn_type_refs_family_nullable_id())) && php::identical(cast<int_t<>>(payloadTypeRefId), cast<int_t<>>(__latency_fn_type_refs_int_id()))))) {
		return __latency_fn_type_refs_nullable_int_id();
	}
	vector_t<int_t<std::uint32_t>> typeArgRefIds = {};
	(void) typeArgRefIds.push_back(payloadTypeRefId);
	return __latency_fn_type_refs_family_instance_type_ref_id_for_type_args(cast<int_t<std::uint32_t>>(familyId), typeArgRefIds);
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_refs_family_instance_type_ref_id_for_type_args(int_t<std::uint32_t> familyId, const vector_t<int_t<std::uint32_t>>& typeArgRefIds) {
	SCPP_CALL_DEPTH_GUARD("type_refs::family_instance_type_ref_id_for_type_args", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[62]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(familyId), cast<int_t<>>(__latency_fn_type_refs_unknown_id())))) {
		return __latency_fn_type_refs_unknown_id();
	}
	if (static_cast<bool>(php::identical(php::count(typeArgRefIds), static_cast<int_t<> >(0)))) {
		return __latency_fn_type_refs_unknown_id();
	}
	if (static_cast<bool>(php::identical(php::count(typeArgRefIds), static_cast<int_t<> >(1)))) {
		int_t<std::uint32_t> firstTypeArgId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(typeArgRefIds.at(static_cast<int_t<> >(0))));
		if (static_cast<bool>((php::identical(cast<int_t<>>(familyId), cast<int_t<>>(__latency_fn_type_refs_family_result_id())) && php::identical(cast<int_t<>>(firstTypeArgId), cast<int_t<>>(__latency_fn_type_refs_int_id()))))) {
			return __latency_fn_type_refs_result_int_id();
		}
		if (static_cast<bool>((php::identical(cast<int_t<>>(familyId), cast<int_t<>>(__latency_fn_type_refs_family_nullable_id())) && php::identical(cast<int_t<>>(firstTypeArgId), cast<int_t<>>(__latency_fn_type_refs_int_id()))))) {
			return __latency_fn_type_refs_nullable_int_id();
		}
	}
	int_t<std::uint32_t> identity = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(((static_cast<int_t<> >(1000000) + (cast<int_t<>>(familyId) * static_cast<int_t<> >(4096))) + php::count(typeArgRefIds))));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = typeArgRefIds;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeArgRefId = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(typeArgRefId), cast<int_t<>>(__latency_fn_type_refs_unknown_id())))) {
			return __latency_fn_type_refs_unknown_id();
		}
		int_t<> next = required_cast<int_t<>>(((((cast<int_t<>>(identity) * static_cast<int_t<> >(131)) + (cast<int_t<>>(typeArgRefId) * static_cast<int_t<> >(17))) + index) % static_cast<int_t<> >(2000000000)));
		identity = __latency_fn_structure_row_ids_uint32_from_int((static_cast<int_t<> >(1000000) + next));
		index = (index + static_cast<int_t<> >(1));
	}
	return cast<int_t<std::uint32_t>>(identity);
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
TypeRefRow __latency_fn_type_refs_family_instance_row(int_t<std::uint32_t> typeRefId, int_t<std::uint32_t> familyId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::family_instance_row", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[63]);
	TypeRefRow row = TypeRefRow{};
	row->type_ref_id = typeRefId;
	row->type_kind_id = __latency_fn_type_refs_type_kind_family_instance_id();
	row->family_id = familyId;
	row->status_id = __latency_fn_type_refs_type_status_known_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
vector_t<TypeRefRow> __latency_fn_type_refs_generic_family_instance_rows() {
	SCPP_CALL_DEPTH_GUARD("type_refs::generic_family_instance_rows", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[64]);
	vector_t<TypeRefRow> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(2));
	{
	auto __latency_local_0 = __latency_fn_type_refs_family_instance_row(__latency_fn_type_refs_family_instance_type_ref_id(__latency_fn_type_refs_family_result_id(), __latency_fn_type_refs_int_id()), __latency_fn_type_refs_family_result_id());
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_type_refs_family_instance_row(__latency_fn_type_refs_family_instance_type_ref_id(__latency_fn_type_refs_family_nullable_id(), __latency_fn_type_refs_int_id()), __latency_fn_type_refs_family_nullable_id());
	(void) rows.push_back(__latency_local_1);
	}
	return rows;
}

}
