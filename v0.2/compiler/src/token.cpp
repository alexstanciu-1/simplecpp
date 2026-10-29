#include "token.hpp"
#include <stdexcept>

namespace scpp::compiler_support
{
	static_assert(sizeof(token) == 8);
	static_assert(alignof(token) == 4);
	static_assert(static_cast<unsigned>(token_kind::kw_xor) <= 255);

	/**
	 * Validate the representation before narrowing. The builder will separately
	 * enforce source containment and ordering; zero is the reserved tag.
	 */
	token::token(uint64 offset, uint64 length, token_kind kind)
	{
		if (offset > uint64{0xffffffffULL} || length == uint64{0} ||
			length > uint64{0xffffff} || kind == token_kind::reserved) {
			throw std::out_of_range("Invalid compact token span or reserved tag");
		}
		this->offset_ = static_cast<uint32>(offset);
		const uint32 tag{static_cast<unsigned int>(kind)};
		this->packed_ = static_cast<uint32>(length) | (tag << uint32{24});
	}

	uint32 token::offset() const
	{
		return this->offset_;
	}

	uint32 token::length() const
	{
		return this->packed_ & uint32{0xffffff};
	}

	token_kind token::kind() const
	{
		return static_cast<token_kind>((this->packed_ >> uint32{24}).native_value());
	}
}
