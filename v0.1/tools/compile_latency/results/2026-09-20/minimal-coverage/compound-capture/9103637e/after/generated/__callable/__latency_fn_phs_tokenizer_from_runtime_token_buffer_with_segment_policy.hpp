#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class TokenStream;
shared_p<TokenStream> __latency_fn_phs_tokenizer_from_runtime_token_buffer_with_segment_policy(int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> sourceBufferId, const string_t& source, int_t<std::uint32_t> segmentThreshold, int_t<std::uint32_t> segmentCapacity);
}
