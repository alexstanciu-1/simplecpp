#pragma once
#include "scpp_compiler/types.hpp"

namespace scpp::compiler_support
{

	class string;

	class vector_string final
	{
	  public:
		vector_string();
		vector_string(const vector_string &other);
		vector_string &operator=(const vector_string &other);
		~vector_string() noexcept;
		void append(const string &value);
		void reserve(int64 capacity);
		[[nodiscard]] boolean contains(const string &value) const;
		void swap(vector_string &other) noexcept;
		[[nodiscard]] int64 size() const;
		// Returns an owned value snapshot; runtime bounds errors propagate.
		[[nodiscard]] string read(int64 index) const;

	  private:
		struct state;
		state *state_;
	};

}
