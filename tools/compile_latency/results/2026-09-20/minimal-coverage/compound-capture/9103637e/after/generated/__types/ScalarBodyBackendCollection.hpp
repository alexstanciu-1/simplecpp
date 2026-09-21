#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class ScalarBodyBackendCollection {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	vector_t<string_t> local_names = vector_t<string_t>{};
	vector_t<int_t<std::uint32_t>> local_source_row_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> local_type_ref_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> assignment_source_row_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> assignment_type_ref_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> assignment_target_local_source_row_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> assignment_value_source_row_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::int32_t>> assignment_values = vector_t<int_t<std::int32_t>>{};
	vector_t<string_t> assignment_value_texts = vector_t<string_t>{};
	vector_t<int_t<std::uint16_t>> assignment_local_operation_ids = vector_t<int_t<std::uint16_t>>{};
	vector_t<int_t<std::uint32_t>> binary_source_row_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint16_t>> binary_feature_ids = vector_t<int_t<std::uint16_t>>{};
	vector_t<int_t<std::uint32_t>> binary_provider_type_ref_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> binary_result_type_ref_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> binary_left_local_source_row_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> binary_right_source_row_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::int32_t>> binary_right_values = vector_t<int_t<std::int32_t>>{};
};
}
