#pragma once
#include "scpp_compiler/string.hpp"

namespace scpp::compiler_support
{
	enum class json_kind
	{
		null_value,
		boolean,
		number,
		text,
		array,
		object
	};

	class vector_json;
	// Immutable owning view: every node retains its document's storage.
	class json_value final
	{
	  public:
		json_value();
		json_value(const json_value &);
		json_value &operator=(const json_value &);
		~json_value() noexcept;
		static json_value parse(const string &text);
		json_kind kind() const;
		int64 size() const;
		boolean has(const string &name) const;
		boolean has(const char *name) const;
		json_value member(const string &name) const;
		json_value member(const char *name) const;
		json_value at(int64 index) const;
		string key(int64 index) const;
		string text() const;
		int64 integer() const;
		boolean boolean() const;

	  private:
		friend class vector_json;
		struct state;
		state *state_;
	};

	class vector_json final
	{
	  public:
		vector_json();
		~vector_json() noexcept;
		vector_json(const vector_json &) = delete;
		vector_json &operator=(const vector_json &) = delete;
		void append(const json_value &value);
		json_value read(int64 index) const;
		int64 size() const;
		void swap(vector_json &other) noexcept;

	  private:
		struct state;
		state *state_;
	};
}
