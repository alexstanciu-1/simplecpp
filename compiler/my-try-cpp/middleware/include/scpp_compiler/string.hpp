#pragma once
#include "scpp_compiler/types.hpp"

namespace scpp::compiler_support
{

	class vector_string;

	class string final
	{
	  public:
		string();
		explicit string(const char *text);
		// Length-bearing input preserves embedded NUL bytes; borrowed for this call.
		string(const char *bytes, uint64 size);
		string(const string &other);
		string &operator=(const string &other);
		~string() noexcept;
		[[nodiscard]] boolean equals(const string &other) const;
		[[nodiscard]] boolean equals(const char *text) const;
		// Exact token membership in a borrowed, space-separated vocabulary.
		[[nodiscard]] boolean is_one_of(const char *vocabulary) const;
		[[nodiscard]] uint32 byte_at(uint64 index) const;
		void swap(string &other) noexcept;
		[[nodiscard]] uint64 byte_size() const noexcept;

	  private:
		friend class vector_string;
		friend struct runtime_access;
		struct state;
		state *state_;
	};

	boolean empty(const string &value);
	boolean isset(const string &value);
	uint64 strlen(const string &value);
}

namespace scpp::compiler
{
	using compiler_support::string;
	using compiler_support::empty;
	using compiler_support::isset;
	using compiler_support::strlen;
}
