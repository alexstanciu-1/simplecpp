#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/PhsParserCursor.hpp"
namespace scpp {
class ParserDiagnosticTable;
class TokenStream;
class PhsParserState {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint32_t> source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_buffer_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_length = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> diagnostic_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> parsed_statement_tail_node_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> current_namespace_name_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> current_namespace_source_range_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	shared_p<TokenStream> tokens;
	PhsParserCursor cursor;
	shared_p<ParserDiagnosticTable> diagnostics;
	vector_t<string_t> local_names = vector_t<string_t>{};
	vector_t<int_t<>> local_type_ref_ids = vector_t<int_t<>>{};
};
}
