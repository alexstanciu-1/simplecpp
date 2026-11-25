#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class SourceReadWorkerResult {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> result_id = static_cast<int_t<> >(0);
	int_t<> descriptor_id = static_cast<int_t<> >(0);
	int_t<> source_id = static_cast<int_t<> >(0);
	int_t<> manifest_order_id = static_cast<int_t<> >(0);
	int_t<> language_id = static_cast<int_t<> >(0);
	int_t<> status_id = static_cast<int_t<> >(0);
	int_t<> read_status_id = static_cast<int_t<> >(0);
	int_t<> error_status_id = static_cast<int_t<> >(0);
	int_t<> source_unit_key_id = static_cast<int_t<> >(0);
	int_t<> relative_path_id = static_cast<int_t<> >(0);
	int_t<> path_id = static_cast<int_t<> >(0);
	int_t<> source_length = static_cast<int_t<> >(0);
	int_t<> line_count = static_cast<int_t<> >(0);
	int_t<> content_hash = static_cast<int_t<> >(0);
	int_t<> output_order_id = static_cast<int_t<> >(0);
	string_t source_text = string_t("");
	source::source_buffer source_buffer = source::source_buffer_empty();
};
}
