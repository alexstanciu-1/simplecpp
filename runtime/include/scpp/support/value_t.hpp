#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/detail.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"

#include <cstdint>

namespace scpp {

template <typename T_VALUE> class table_t;

class value_t final {
public:
	enum class kind_t : std::uint8_t {
		null_v = 0,
		bool_v,
		int_v,
		float_v,
		string_v,
		table_v,
		shared_table_v,
		weak_table_v
	};

private:
	kind_t type_;

	union {
		bool_t bool_value_;
		int_t int_value_;
		float_t float_value_;
		unique_p<string_t> string_value_;
		unique_p<table_t<value_t>> table_value_;
		shared_p<table_t<value_t>> shared_table_value_;
		weak_p<table_t<value_t>> weak_table_value_;
	};

	void destroy() noexcept;
	void move_construct(value_t &&other) noexcept;

public:
	// Lifecycle
	value_t() noexcept;
	value_t(null_t) noexcept;
	value_t(nullopt_t) noexcept;
	value_t(nullptr_t) noexcept;
	value_t(const bool_t &value) noexcept;
	value_t(const int_t &value) noexcept;
	value_t(const float_t &value) noexcept;
	value_t(const string_t &value);
	value_t(const char *value);
	value_t(bool value) noexcept;
	value_t(std::int64_t value) noexcept;
	value_t(double value) noexcept;
	value_t(unique_p<table_t<value_t>> value) noexcept;
	value_t(shared_p<table_t<value_t>> value) noexcept;
	value_t(weak_p<table_t<value_t>> value) noexcept;
	explicit value_t(std::unique_ptr<table_t<value_t>> value) noexcept;
	explicit value_t(std::shared_ptr<table_t<value_t>> value) noexcept;
	explicit value_t(std::weak_ptr<table_t<value_t>> value) noexcept;
	~value_t();

	value_t(const value_t &other);
	value_t(value_t &&other) noexcept;
	value_t &operator=(const value_t &other);
	value_t &operator=(value_t &&other) noexcept;

	value_t &operator=(null_t) noexcept;
	value_t &operator=(nullopt_t) noexcept;
	value_t &operator=(nullptr_t) noexcept;
	value_t &operator=(const bool_t &value) noexcept;
	value_t &operator=(const int_t &value) noexcept;
	value_t &operator=(const float_t &value) noexcept;
	value_t &operator=(const string_t &value);
	value_t &operator=(const char *value);
	value_t &operator=(bool value) noexcept;
	value_t &operator=(std::int64_t value) noexcept;
	value_t &operator=(double value) noexcept;
	value_t &operator=(unique_p<table_t<value_t>> value) noexcept;
	value_t &operator=(shared_p<table_t<value_t>> value) noexcept;
	value_t &operator=(weak_p<table_t<value_t>> value) noexcept;
	value_t &operator=(std::unique_ptr<table_t<value_t>> value) noexcept;
	value_t &operator=(std::shared_ptr<table_t<value_t>> value) noexcept;
	value_t &operator=(std::weak_ptr<table_t<value_t>> value) noexcept;

	[[nodiscard]] value_t clone() const;

	// Observers
	[[nodiscard]] kind_t kind() const noexcept;
	[[nodiscard]] bool_t is_null() const noexcept;
	[[nodiscard]] bool_t bool_value() const;
	[[nodiscard]] int_t int_value() const;
	[[nodiscard]] float_t float_value() const;

	[[nodiscard]] string_t *string_if() noexcept;
	[[nodiscard]] const string_t *string_if() const noexcept;
	[[nodiscard]] table_t<value_t> *table_if() noexcept;
	[[nodiscard]] const table_t<value_t> *table_if() const noexcept;
	[[nodiscard]] shared_p<table_t<value_t>> *shared_table_if() noexcept;
	[[nodiscard]] const shared_p<table_t<value_t>> *shared_table_if() const noexcept;
	[[nodiscard]] weak_p<table_t<value_t>> *weak_table_if() noexcept;
	[[nodiscard]] const weak_p<table_t<value_t>> *weak_table_if() const noexcept;

	// Casts / Promotions
	[[nodiscard]] int_t &as_int_ref();
	[[nodiscard]] float_t &as_float_ref();
	[[nodiscard]] bool_t &as_bool_ref();
	[[nodiscard]] string_t &as_string_ref();
	[[nodiscard]] table_t<value_t> &as_table_ref();

	operator bool_t() const;
	operator int_t() const;
	operator float_t() const;
	operator string_t() const;
	operator string_t&();

	operator bool() const;
	operator shared_p<table_t<value_t>>() const;
	operator weak_p<table_t<value_t>>() const;

	// Compound Assignments
	value_t &operator++();
	value_t operator++(int);
	value_t &operator--();
	value_t operator--(int);
	value_t &operator+=(const value_t &right);
	value_t &operator-=(const value_t &right);
	value_t &operator*=(const value_t &right);
	value_t &operator/=(const value_t &right);
	value_t &operator%=(const value_t &right);
	value_t &operator&=(const value_t &right);
	value_t &operator|=(const value_t &right);
	value_t &operator^=(const value_t &right);
	value_t &operator<<=(const value_t &right);
	value_t &operator>>=(const value_t &right);

	// Fat Variant Operations
	value_t& operator[](const int_t& key);
	value_t& operator[](const string_t& key);
	value_t& operator[](const value_t& key);
	value_t& operator[](const char* key);
	value_t& operator[](int native_key);

	const value_t& operator[](const int_t& key) const;
	const value_t& operator[](const string_t& key) const;
	const value_t& operator[](const value_t& key) const;
	const value_t& operator[](const char* key) const;
	const value_t& operator[](int native_key) const;

	void append(const value_t& val);
	
	bool_t isset(const value_t& key) const;
	bool_t isset(const int_t& key) const;
	bool_t isset(const string_t& key) const;
	bool_t isset(const char* key) const;
	bool_t isset(int native_key) const;

	// Free operators
	friend value_t operator+(const value_t &value);
	friend value_t operator-(const value_t &value);
	friend bool_t operator!(const value_t &value);
	friend value_t operator~(const value_t &value);
	friend value_t operator+(const value_t &left, const value_t &right);
	friend value_t operator-(const value_t &left, const value_t &right);
	friend value_t operator*(const value_t &left, const value_t &right);
	friend value_t operator/(const value_t &left, const value_t &right);
	friend value_t operator%(const value_t &left, const value_t &right);
	friend value_t operator&(const value_t &left, const value_t &right);
	friend value_t operator|(const value_t &left, const value_t &right);
	friend value_t operator^(const value_t &left, const value_t &right);
	friend value_t operator<<(const value_t &left, const value_t &right);
	friend value_t operator>>(const value_t &left, const value_t &right);
	friend bool_t operator==(const value_t &left, const value_t &right);
	friend bool_t operator!=(const value_t &left, const value_t &right);
	friend bool_t operator<(const value_t &left, const value_t &right);
	friend bool_t operator<=(const value_t &left, const value_t &right);
	friend bool_t operator>(const value_t &left, const value_t &right);
	friend bool_t operator>=(const value_t &left, const value_t &right);
	friend bool_t operator&&(const value_t &left, const value_t &right);
	friend bool_t operator||(const value_t &left, const value_t &right);
};

} // namespace scpp
