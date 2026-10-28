#pragma once
#include "scpp/integers.hpp"
#include "scpp/values.hpp"

namespace scpp::compiler_support
{
	// Immutable byte snapshot. Copies share storage; no interior reference escapes.
	class token_buffer final
	{
	public:
		token_buffer() noexcept;
		token_buffer(const token_buffer &other);
		token_buffer(token_buffer &&other) noexcept;
		token_buffer &operator=(const token_buffer &other);
		token_buffer &operator=(token_buffer &&other) noexcept;
		~token_buffer() noexcept;
		// Host ingress: copies exactly length bytes. Null is invalid, including at zero length.
		static token_buffer from_bytes(const char *bytes, uint64 length);
		bool_t present() const noexcept;
		uint64 byte_size() const noexcept;
		// -1 is outside the snapshot; NUL and high bytes remain 0..255.
		int32 peek(uint64 offset) const;
		uint32 byte_at(uint64 offset) const;
		// Literal is a non-null, zero-terminated spelling borrowed for this call.
		bool_t matches(uint64 offset, uint64 length, const char *literal) const;
		bool_t same_storage(const token_buffer &other) const noexcept;
		void swap(token_buffer &other) noexcept;
	private:
		struct state;
		state *state_ = nullptr;
	};
}

namespace scpp::compiler {
	using compiler_support::token_buffer;
}
