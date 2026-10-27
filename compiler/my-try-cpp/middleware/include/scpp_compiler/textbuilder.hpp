#pragma once
#include "scpp_compiler/types.hpp"

namespace scpp::compiler_support
{
	class string;

	// Concrete, noncopyable owner. Runtime layout stays in the compiled middleware.
	class textbuilder final
	{
	  public:
		textbuilder();
		~textbuilder() noexcept;
		textbuilder(const textbuilder &) = delete;
		textbuilder &operator=(const textbuilder &) = delete;

		// Literal/zero-terminated input is borrowed only for this call.
		void append(const char *text);
		void append(const string &text);
		void append_number(uint64 value);
		void append_number(int64 value);
		[[nodiscard]] string finish() const;
		[[nodiscard]] boolean equals(const char *text) const;
		// Exercises the compiled runtime's identity pool, not only inline helpers.
		[[nodiscard]] boolean roundtrip_through_runtime() const;

	  private:
		struct state;
		state *state_;
	};

}

namespace scpp::compiler
{
	using compiler_support::textbuilder;
}
