#pragma once
#include "scpp_compiler/token_buffer.hpp"
#include "scpp_compiler/token.hpp"

namespace scpp::compiler_support
{
	class token_builder;
	class token_sequence final
	{
	public:
		token_sequence() noexcept;
		token_sequence(const token_sequence &other);
		token_sequence(token_sequence &&other) noexcept;
		token_sequence &operator=(const token_sequence &other);
		token_sequence &operator=(token_sequence &&other) noexcept;
		~token_sequence() noexcept;
		boolean present() const noexcept;
		int64 size() const;
		uint64 capacity_bytes() const;
		token at(int64 index) const;
		// Owning snapshot: shares bytes and survives destruction/replacement of this generation.
		token_buffer source() const;
		boolean same_storage(const token_sequence &other) const noexcept;
		void swap(token_sequence &other) noexcept;
	private:
		friend class token_builder;
		struct state;
		state *state_ = nullptr;
	};

	// Private mutable construction; finish transfers the array into an immutable generation.
	class token_builder final
	{
	public:
		token_builder(const token_buffer &source, int64 previous_count);
		~token_builder() noexcept;
		token_builder(const token_builder &) = delete;
		token_builder &operator=(const token_builder &) = delete;
		void append(uint64 offset, uint64 length, token_kind kind);
		token_sequence finish();
	private:
		struct state;
		state *state_;
	};
}

namespace scpp::compiler
{
	using compiler_support::token;
	using compiler_support::token_sequence;
	using compiler_support::token_builder;
}
