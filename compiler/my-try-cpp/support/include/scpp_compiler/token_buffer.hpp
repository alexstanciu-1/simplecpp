#pragma once
#include "scpp_compiler/string.hpp"

namespace scpp::compiler_support
{
	// Immutable byte owner shared by loaded files and their token generations.
	class token_buffer final
	{
	public:
		token_buffer() noexcept;
		token_buffer(const token_buffer &other);
		token_buffer(token_buffer &&other) noexcept;
		token_buffer &operator=(const token_buffer &other);
		token_buffer &operator=(token_buffer &&other) noexcept;
		~token_buffer() noexcept;
		static token_buffer from_text(const string &text);
		static token_buffer read(const string &path, int64 mtime, int64 bytes);
		boolean present() const noexcept;
		uint64 byte_size() const noexcept;
		// Safe lookahead: -1 outside the source; embedded NUL remains byte zero.
		int32 peek(uint64 offset) const;
		boolean matches(uint64 offset, uint64 length, const char *literal) const;
		uint32 byte_at(uint64 offset) const;
		boolean equals(const string &text) const;
		boolean same_storage(const token_buffer &other) const noexcept;
		void swap(token_buffer &other) noexcept;
	private:
		struct state;
		state *state_ = nullptr;
	};
}

namespace scpp::compiler
{
	using compiler_support::token_buffer;
}
