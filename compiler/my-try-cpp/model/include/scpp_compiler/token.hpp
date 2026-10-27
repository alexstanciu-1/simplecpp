#pragma once
#include "scpp_compiler/types.hpp"
#include "scpp_compiler/token_kind.hpp"

namespace scpp::compiler_support
{
	class token final
	{
	public:
		token(uint64 offset, uint64 length, token_kind kind);
		uint32 offset() const;
		uint32 length() const;
		token_kind kind() const;
	private:
		uint32 offset_;
		uint32 packed_;
	};
}

namespace scpp::compiler {
	using compiler_support::token;
}
