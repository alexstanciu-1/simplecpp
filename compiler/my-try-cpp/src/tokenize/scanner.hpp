#pragma once
#include "scpp_compiler/token_sequence.hpp"

namespace scpp::compiler
{
	token_sequence scan_tokens(const token_buffer &source, int64 previous_count = 0);
	token_kind classify_word(const token_buffer &source, uint64 start, uint64 length);
	token_kind scan_operator(const token_buffer &source, uint64 start, uint64 &length);
}
