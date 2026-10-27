#include "scpp_compiler/token.hpp"
#include "scalar_access.hpp"
#include <stdexcept>

namespace scpp::compiler_support
{
	static_assert(sizeof(token) == 8);
	static_assert(alignof(token) == 4);
	static_assert(static_cast<unsigned>(token_kind::kw_xor) <= 255);

	/**
	 * Validate before narrowing; packed length occupies the low 24 bits.
	 */
	token::token(uint64 offset, uint64 length, token_kind kind)
	{
		const auto start = scalar_access::get(offset).native_value();
		const auto bytes = scalar_access::get(length).native_value();
		if (start > 0xffffffffULL || bytes == 0 || bytes > 0xffffff || kind == token_kind::reserved) {
			throw std::out_of_range("Invalid compact token span or reserved tag");
		}
		this->offset_ = static_cast<std::uint32_t>(start);
		this->packed_ = static_cast<std::uint32_t>(bytes) | (static_cast<std::uint32_t>(kind) << 24);
	}

	uint32 token::offset() const
	{
		return this->offset_;
	}

	uint32 token::length() const
	{
		return scalar_access::get(this->packed_).native_value() & 0xffffff;
	}

	token_kind token::kind() const
	{
		return static_cast<token_kind>(scalar_access::get(this->packed_).native_value() >> 24);
	}

}
