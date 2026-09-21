#include <scpp/lang/php.hpp>
#include "__types/PrimitiveTypeRefDescriptorRow.hpp"
#include "__types/PrimitiveTypeRefSpellingDescriptorRow.hpp"
#include "__types/ProviderDescriptorRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_provider_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_provider_descriptor_by_type_ref_id.hpp"
#include "__callable/__latency_fn_type_refs_provider_descriptors.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_refs_provider_type_ref_row.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_runtime_opaque_id.hpp"
#include "__callable/__latency_fn_type_refs_primitive_descriptor.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_primitive_id.hpp"
#include "__callable/__latency_fn_type_refs_type_status_known_id.hpp"
#include "__callable/__latency_fn_type_refs_bool_id.hpp"
#include "__callable/__latency_fn_type_refs_double_id.hpp"
#include "__callable/__latency_fn_type_refs_float_id.hpp"
#include "__callable/__latency_fn_type_refs_int16_id.hpp"
#include "__callable/__latency_fn_type_refs_int32_id.hpp"
#include "__callable/__latency_fn_type_refs_int64_id.hpp"
#include "__callable/__latency_fn_type_refs_int8_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_primitive_descriptor.hpp"
#include "__callable/__latency_fn_type_refs_primitive_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_uint16_id.hpp"
#include "__callable/__latency_fn_type_refs_uint32_id.hpp"
#include "__callable/__latency_fn_type_refs_uint64_id.hpp"
#include "__callable/__latency_fn_type_refs_uint8_id.hpp"
#include "__callable/__latency_fn_type_refs_primitive_spelling_descriptor.hpp"
#include "__callable/__latency_fn_type_refs_bool_id.hpp"
#include "__callable/__latency_fn_type_refs_double_id.hpp"
#include "__callable/__latency_fn_type_refs_float_id.hpp"
#include "__callable/__latency_fn_type_refs_int16_id.hpp"
#include "__callable/__latency_fn_type_refs_int32_id.hpp"
#include "__callable/__latency_fn_type_refs_int64_id.hpp"
#include "__callable/__latency_fn_type_refs_int8_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_primitive_spelling_descriptor.hpp"
#include "__callable/__latency_fn_type_refs_primitive_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_uint16_id.hpp"
#include "__callable/__latency_fn_type_refs_uint32_id.hpp"
#include "__callable/__latency_fn_type_refs_uint64_id.hpp"
#include "__callable/__latency_fn_type_refs_uint8_id.hpp"
namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
vector_t<ProviderDescriptorRow> __latency_fn_type_refs_provider_descriptors() {
	SCPP_CALL_DEPTH_GUARD("type_refs::provider_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[50]);
	return __latency_fn_semantic_type_ref_providers_provider_descriptors();
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
ProviderDescriptorRow __latency_fn_type_refs_provider_descriptor_by_type_ref_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::provider_descriptor_by_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[51]);
	auto __latency_local_0 = __latency_fn_type_refs_provider_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return row;
		}
	}
	ProviderDescriptorRow empty = ProviderDescriptorRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
TypeRefRow __latency_fn_type_refs_provider_type_ref_row(ProviderDescriptorRow descriptor) {
	SCPP_CALL_DEPTH_GUARD("type_refs::provider_type_ref_row", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[52]);
	TypeRefRow row = TypeRefRow{};
	row->type_ref_id = descriptor->type_ref_id;
	row->type_kind_id = __latency_fn_type_refs_type_kind_runtime_opaque_id();
	row->family_id = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(descriptor->family_id));
	row->status_id = descriptor->status_id;
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
PrimitiveTypeRefDescriptorRow __latency_fn_type_refs_primitive_descriptor(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::primitive_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[53]);
	PrimitiveTypeRefDescriptorRow row = PrimitiveTypeRefDescriptorRow{};
	row->type_ref_id = typeRefId;
	row->type_kind_id = __latency_fn_type_refs_type_kind_primitive_id();
	row->status_id = __latency_fn_type_refs_type_status_known_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
vector_t<PrimitiveTypeRefDescriptorRow> __latency_fn_type_refs_primitive_descriptors() {
	SCPP_CALL_DEPTH_GUARD("type_refs::primitive_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[54]);
	vector_t<PrimitiveTypeRefDescriptorRow> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(12));
	{
	auto __latency_local_0 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_int_id());
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_int64_id());
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_bool_id());
	(void) rows.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_int8_id());
	(void) rows.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_int16_id());
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_int32_id());
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_uint8_id());
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_uint16_id());
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_uint32_id());
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_uint64_id());
	(void) rows.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_float_id());
	(void) rows.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_type_refs_primitive_descriptor(__latency_fn_type_refs_double_id());
	(void) rows.push_back(__latency_local_11);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
shared_p<PrimitiveTypeRefSpellingDescriptorRow> __latency_fn_type_refs_primitive_spelling_descriptor(int_t<std::uint32_t> typeRefId, const string_t& sourceName) {
	SCPP_CALL_DEPTH_GUARD("type_refs::primitive_spelling_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[55]);
	shared_p<PrimitiveTypeRefSpellingDescriptorRow> row = create<PrimitiveTypeRefSpellingDescriptorRow>();
	row->type_ref_id = typeRefId;
	row->source_name = sourceName;
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> __latency_fn_type_refs_primitive_spelling_descriptors() {
	SCPP_CALL_DEPTH_GUARD("type_refs::primitive_spelling_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[56]);
	vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(12));
	{
	auto __latency_local_0 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_int_id(), string_t("int"));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_int64_id(), string_t("int64"));
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_bool_id(), string_t("bool"));
	(void) rows.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_int8_id(), string_t("int8"));
	(void) rows.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_int16_id(), string_t("int16"));
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_int32_id(), string_t("int32"));
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_uint8_id(), string_t("uint8"));
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_uint16_id(), string_t("uint16"));
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_uint32_id(), string_t("uint32"));
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_uint64_id(), string_t("uint64"));
	(void) rows.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_float_id(), string_t("float"));
	(void) rows.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_double_id(), string_t("double"));
	(void) rows.push_back(__latency_local_11);
	}
	return rows;
}

}
