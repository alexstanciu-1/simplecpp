#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/string_t.hpp"

#include <cstdint>
#include <memory>
#include <stdexcept>

// Ordered key/value runtime container value used by table_t.
//
// Notes:
// - public semantics stay in scpp terms
// - storage keeps the donor mem_container tagged-union layout style
// - copy support is explicit because lookup returns value objects, not references
class value_t final {
public:
	// Runtime tag for the active stored payload.
	enum class kind_t : std::uint8_t {
		null_v = 0,
		bool_v,
		int_v,
		float_v,
		string_v,
		table_v,
		shared_table_v,
		weak_table_v,
		any_v
	};

private:
	// Internal tagged-union storage reused from the donor design.
	kind_t type_;

	union {
		bool bool_value_;
		std::int64_t int_value_;
		double float_value_;
		std::unique_ptr<string_t> string_value_;
		std::unique_ptr<table_t> table_value_;
		std::shared_ptr<table_t> shared_table_value_;
		std::weak_ptr<table_t> weak_table_value_;
		std::any any_value_;
	};

	// Destroys the currently active payload branch.
	void destroy() noexcept;

	// Moves one active payload from another value into this object.
	void move_construct(value_t &&other) noexcept;

public:
	// Stable zero-state constructor: stores runtime null.
	value_t() noexcept;
	value_t(null_t) noexcept;

	// Value constructors keep public I/O in scpp terms.
	value_t(const bool_t &value) noexcept;
	value_t(const int_t &value) noexcept;
	value_t(const float_t &value) noexcept;
	value_t(const string_t &value);
	value_t(const char *value);

	// Native convenience constructors used by assignment-capable access such as table["x"] = 12.
	value_t(bool value) noexcept;
	value_t(std::int64_t value) noexcept;
	value_t(double value) noexcept;

	// Table/object-like constructors remain available for future runtime growth.
	value_t(std::unique_ptr<table_t> value) noexcept;
	value_t(std::shared_ptr<table_t> value) noexcept;
	value_t(std::weak_ptr<table_t> value) noexcept;
	value_t(std::any value);

	// Rule-of-five support is explicit because the type owns a manual tagged union.
	~value_t();

	value_t(const value_t &other);
	value_t(value_t &&other) noexcept;
	value_t &operator=(const value_t &other);
	value_t &operator=(value_t &&other) noexcept;

	// Convenience assignments keep generated/runtime code short at the call site.
	value_t &operator=(null_t) noexcept;
	value_t &operator=(const bool_t &value) noexcept;
	value_t &operator=(const int_t &value) noexcept;
	value_t &operator=(const float_t &value) noexcept;
	value_t &operator=(const string_t &value);
	value_t &operator=(const char *value);
	value_t &operator=(bool value) noexcept;
	value_t &operator=(std::int64_t value) noexcept;
	value_t &operator=(double value) noexcept;

	// Deep-copy helper used by table_t copy and non-reference lookup results.
	[[nodiscard]] value_t clone() const;

	// Minimal public inspection helpers.
	[[nodiscard]] kind_t kind() const noexcept;
	[[nodiscard]] bool_t is_null() const noexcept;

	// Scalar access helpers used by runtime services such as PHP echo/string coercion.
	[[nodiscard]] bool_t bool_value() const;
	[[nodiscard]] int_t int_value() const;
	[[nodiscard]] float_t float_value() const;
	[[nodiscard]] const string_t *string_if() const noexcept;

	[[nodiscard]] table_t *table_if() noexcept;
	[[nodiscard]] const table_t *table_if() const noexcept;
};

