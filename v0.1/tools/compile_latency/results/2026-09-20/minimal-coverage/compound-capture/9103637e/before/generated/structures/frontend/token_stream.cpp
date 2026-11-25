#include <scpp/lang/php.hpp>
#include "__types/TokenRowList.hpp"
#include "__types/TokenStream.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t TokenStream::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == TokenStream::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

TokenStream::TokenStream() {
	SCPP_CALL_DEPTH_GUARD("TokenStream::__construct", "/tmp/scpp-edit-latency-20260919/app/structures/frontend/token_stream.phs", 12);
	this->token_rows = create<TokenRowList>();
}

}

