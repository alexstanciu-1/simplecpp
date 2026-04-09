#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/detail.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/dynamic_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"

#include <cassert>
#include <cstdint>

namespace scpp {

template <typename T_VALUE> class hash_t;

template <typename T> class scalar_ref;

class mixed_t final {
	template <typename T> friend class scalar_ref;
public:
	enum class mixed_type : std::uint8_t {
		null_v = 0,
		bool_v,
		int_v,
		float_v,
		string_v,
		hash_v,
		shared_hash_v,
		dynamic_v,
		weak_hash_v
	};

	enum class kind_t : std::uint8_t {
		null_v = 0,
		bool_v,
		int_v,
		float_v,
		string_v,
		table_v,
		shared_table_v,
		dynamic_v,
		weak_table_v
	};

private:
	kind_t type_;

	union {
		bool_t bool_value_;
		int_t int_value_;
		float_t float_value_;
		unique_p<string_t> string_value_;
		unique_p<hash_t<mixed_t>> table_value_;
		shared_p<hash_t<mixed_t>> shared_table_value_;
		dynamic_t dynamic_value_;
		weak_p<hash_t<mixed_t>> weak_table_value_;
	};

#ifndef NDEBUG
	mutable std::uint16_t borrow_count_ = 0;
#else
	mutable std::uint16_t borrow_count_ = 0;
#endif

	void destroy() noexcept;
	void assert_kind_change_allowed(kind_t target_kind) const noexcept;
	void assert_not_borrowed(const char *operation) const noexcept;
	void acquire_scalar_borrow() const noexcept;
	void release_scalar_borrow() const noexcept;
	void move_construct(mixed_t &&other) noexcept;

public:

	// Transfer ownership of table value (used by runtime internals)
	unique_p<hash_t<mixed_t>> take_table_value();
	// Lifecycle
	mixed_t() noexcept;
	mixed_t(null_t) noexcept;
	mixed_t(nullopt_t) noexcept;
	mixed_t(nullptr_t) noexcept;
	mixed_t(const bool_t &value) noexcept;
	mixed_t(const int_t &value) noexcept;
	mixed_t(const float_t &value) noexcept;
	mixed_t(const string_t &value);
	mixed_t(const char *value);
	mixed_t(bool value) noexcept;
	mixed_t(std::int64_t value) noexcept;
	mixed_t(double value) noexcept;
	mixed_t(unique_p<hash_t<mixed_t>> value) noexcept;
	mixed_t(shared_p<hash_t<mixed_t>> value) noexcept;
	mixed_t(dynamic_init_t value) noexcept;
	mixed_t(weak_p<hash_t<mixed_t>> value) noexcept;
	explicit mixed_t(std::unique_ptr<hash_t<mixed_t>> value) noexcept;
	explicit mixed_t(std::shared_ptr<hash_t<mixed_t>> value) noexcept;
	explicit mixed_t(std::weak_ptr<hash_t<mixed_t>> value) noexcept;
	~mixed_t();

	mixed_t(const mixed_t &other);
	mixed_t(mixed_t &&other) noexcept;
	mixed_t &operator=(const mixed_t &other);
	mixed_t &operator=(mixed_t &&other) noexcept;

	mixed_t &operator=(null_t) noexcept;
	mixed_t &operator=(nullopt_t) noexcept;
	mixed_t &operator=(nullptr_t) noexcept;
	mixed_t &operator=(const bool_t &value) noexcept;
	mixed_t &operator=(const int_t &value) noexcept;
	mixed_t &operator=(const float_t &value) noexcept;
	mixed_t &operator=(const string_t &value);
	mixed_t &operator=(const char *value);
	mixed_t &operator=(bool value) noexcept;
	mixed_t &operator=(std::int64_t value) noexcept;
	mixed_t &operator=(double value) noexcept;
	mixed_t &operator=(unique_p<hash_t<mixed_t>> value) noexcept;
	mixed_t &operator=(shared_p<hash_t<mixed_t>> value) noexcept;
	mixed_t &operator=(dynamic_init_t value) noexcept;
	mixed_t &operator=(weak_p<hash_t<mixed_t>> value) noexcept;
	mixed_t &operator=(std::unique_ptr<hash_t<mixed_t>> value) noexcept;
	mixed_t &operator=(std::shared_ptr<hash_t<mixed_t>> value) noexcept;
	mixed_t &operator=(std::weak_ptr<hash_t<mixed_t>> value) noexcept;

	[[nodiscard]] mixed_t clone() const;

	// Observers
	[[nodiscard]] mixed_type type() const noexcept;
	[[nodiscard]] kind_t kind() const noexcept;
	[[nodiscard]] bool_t is_null() const noexcept;
	[[nodiscard]] bool_t is_bool() const noexcept;
	[[nodiscard]] bool_t is_int() const noexcept;
	[[nodiscard]] bool_t is_float() const noexcept;
	[[nodiscard]] bool_t is_string() const noexcept;
	[[nodiscard]] bool_t is_hash() const noexcept;
	[[nodiscard]] bool_t get_bool() const;
	[[nodiscard]] int_t get_int() const;
	[[nodiscard]] float_t get_float() const;
	[[nodiscard]] const string_t &get_string() const;
	[[nodiscard]] string_t &get_string();
	[[nodiscard]] const hash_t<mixed_t> &get_hash() const;
	[[nodiscard]] hash_t<mixed_t> &get_hash();
	[[nodiscard]] const bool_t *try_get_bool() const noexcept;
	[[nodiscard]] bool_t *try_get_bool() noexcept;
	[[nodiscard]] const int_t *try_get_int() const noexcept;
	[[nodiscard]] int_t *try_get_int() noexcept;
	[[nodiscard]] const float_t *try_get_float() const noexcept;
	[[nodiscard]] float_t *try_get_float() noexcept;
	[[nodiscard]] const string_t *try_get_string() const noexcept;
	[[nodiscard]] string_t *try_get_string() noexcept;
	[[nodiscard]] const hash_t<mixed_t> *try_get_hash() const noexcept;
	[[nodiscard]] hash_t<mixed_t> *try_get_hash() noexcept;
	[[nodiscard]] bool_t bool_value() const;
	[[nodiscard]] int_t int_value() const;
	[[nodiscard]] float_t float_value() const;

	[[nodiscard]] string_t *string_if() noexcept;
	[[nodiscard]] const string_t *string_if() const noexcept;
	[[nodiscard]] hash_t<mixed_t> *table_if() noexcept;
	[[nodiscard]] const hash_t<mixed_t> *table_if() const noexcept;
	[[nodiscard]] shared_p<hash_t<mixed_t>> *shared_table_if() noexcept;
	[[nodiscard]] const shared_p<hash_t<mixed_t>> *shared_table_if() const noexcept;
	[[nodiscard]] dynamic_t *dynamic_if() noexcept;
	[[nodiscard]] const dynamic_t *dynamic_if() const noexcept;
	[[nodiscard]] weak_p<hash_t<mixed_t>> *weak_table_if() noexcept;
	[[nodiscard]] const weak_p<hash_t<mixed_t>> *weak_table_if() const noexcept;

	// Casts / Promotions
	//
	// Contract split:
	// - get_* / try_get_* are exact accessors only
	// - as_*_ref are transitional legacy hooks and are disabled in the current safe subset
	// - conversion operators remain in v1 only to preserve valid Visible Intention sites
	//   from specs/dynamic_types.md sections 1.2 and 1.3 until generator parity exists
	[[nodiscard]] int_t &as_int_ref();
	[[nodiscard]] float_t &as_float_ref();
	[[nodiscard]] bool_t &as_bool_ref();
	[[nodiscard]] string_t &as_string_ref();
	[[nodiscard]] hash_t<mixed_t> &as_table_ref();

	// Temporary v1 Visible-Intention bridge operators.
	// Do not remove until the generator can reliably materialize explicit typed bridges
	// for all valid Visible Intention sites listed in specs/dynamic_types.md sections 1.2 and 1.3.
	operator bool_t() const;
	operator int_t() const;
	operator float_t() const;
	operator string_t() const;

	operator bool() const;
	operator shared_p<hash_t<mixed_t>>() const;
	operator weak_p<hash_t<mixed_t>>() const;

	// Compound Assignments
	mixed_t &operator++();
	mixed_t operator++(int);
	mixed_t &operator--();
	mixed_t operator--(int);
	mixed_t &operator+=(const mixed_t &right);
	mixed_t &operator-=(const mixed_t &right);
	mixed_t &operator*=(const mixed_t &right);
	mixed_t &operator/=(const mixed_t &right);
	mixed_t &operator%=(const mixed_t &right);
	mixed_t &operator&=(const mixed_t &right);
	mixed_t &operator|=(const mixed_t &right);
	mixed_t &operator^=(const mixed_t &right);
	mixed_t &operator<<=(const mixed_t &right);
	mixed_t &operator>>=(const mixed_t &right);

	// Fat Variant Operations
	mixed_t& operator[](const int_t& key);
	mixed_t& operator[](const string_t& key);
	mixed_t& operator[](const mixed_t& key);
	mixed_t& operator[](const char* key);
	mixed_t& operator[](int native_key);

	const mixed_t& operator[](const int_t& key) const;
	const mixed_t& operator[](const string_t& key) const;
	const mixed_t& operator[](const mixed_t& key) const;
	const mixed_t& operator[](const char* key) const;
	const mixed_t& operator[](int native_key) const;

	void append(const mixed_t& val);
	[[nodiscard]] bool remove(const int_t& key);
	[[nodiscard]] bool remove(const string_t& key);
	[[nodiscard]] bool remove(const mixed_t& key);

	[[nodiscard]] int_t size() const;
	[[nodiscard]] bool empty() const;
	mixed_t& at(const int_t& key);
	const mixed_t& at(const int_t& key) const;
	mixed_t& at(const string_t& key);
	const mixed_t& at(const string_t& key) const;

	[[nodiscard]] mixed_t get(const mixed_t& key) const;
	[[nodiscard]] mixed_t get(const int_t& key) const;
	[[nodiscard]] mixed_t get(const string_t& key) const;
	[[nodiscard]] mixed_t get(const char* key) const;
	[[nodiscard]] mixed_t get(int native_key) const;
	
	bool_t isset(const mixed_t& key) const;
	bool_t isset(const int_t& key) const;
	bool_t isset(const string_t& key) const;
	bool_t isset(const char* key) const;
	bool_t isset(int native_key) const;

	// Free operators
	friend mixed_t operator+(const mixed_t &value);
	friend mixed_t operator-(const mixed_t &value);
	friend bool_t operator!(const mixed_t &value);
	friend mixed_t operator~(const mixed_t &value);
	friend mixed_t operator+(const mixed_t &left, const mixed_t &right);
	friend mixed_t operator-(const mixed_t &left, const mixed_t &right);
	friend mixed_t operator*(const mixed_t &left, const mixed_t &right);
	friend mixed_t operator/(const mixed_t &left, const mixed_t &right);
	friend mixed_t operator%(const mixed_t &left, const mixed_t &right);
	friend mixed_t operator&(const mixed_t &left, const mixed_t &right);
	friend mixed_t operator|(const mixed_t &left, const mixed_t &right);
	friend mixed_t operator^(const mixed_t &left, const mixed_t &right);
	friend mixed_t operator<<(const mixed_t &left, const mixed_t &right);
	friend mixed_t operator>>(const mixed_t &left, const mixed_t &right);
	friend bool_t operator==(const mixed_t &left, const mixed_t &right);
	friend bool_t operator!=(const mixed_t &left, const mixed_t &right);
	friend bool_t operator<(const mixed_t &left, const mixed_t &right);
	friend bool_t operator<=(const mixed_t &left, const mixed_t &right);
	friend bool_t operator>(const mixed_t &left, const mixed_t &right);
	friend bool_t operator>=(const mixed_t &left, const mixed_t &right);
	friend bool_t operator&&(const mixed_t &left, const mixed_t &right);
	friend bool_t operator||(const mixed_t &left, const mixed_t &right);
};


template <typename T>
class scalar_ref final {
private:
	T *ptr_ = nullptr;
	mixed_t *owner_ = nullptr;

public:
	scalar_ref(T &raw) noexcept : ptr_(&raw), owner_(nullptr) {}
	scalar_ref(mixed_t &value);
	~scalar_ref() {
		if (owner_ != nullptr) {
			owner_->release_scalar_borrow();
		}
	}

	scalar_ref(const scalar_ref &) = delete;
	scalar_ref &operator=(const scalar_ref &other) {
		get() = other.get();
		return *this;
	}
	scalar_ref(scalar_ref &&other) noexcept : ptr_(other.ptr_), owner_(other.owner_) {
		other.ptr_ = nullptr;
		other.owner_ = nullptr;
	}
	scalar_ref &operator=(scalar_ref &&) = delete;

	operator T&() const {
		assert(ptr_ != nullptr);
		return *ptr_;
	}

	T &get() const {
		assert(ptr_ != nullptr);
		return *ptr_;
	}

	scalar_ref &operator=(const T &value) {
		get() = value;
		return *this;
	}

	scalar_ref &operator++() {
		++get();
		return *this;
	}

	T operator++(int) {
		T snapshot = get();
		++get();
		return snapshot;
	}

	scalar_ref &operator--() {
		--get();
		return *this;
	}

	T operator--(int) {
		T snapshot = get();
		--get();
		return snapshot;
	}

	scalar_ref &operator+=(const T &value) {
		get() = get() + value;
		return *this;
	}

	scalar_ref &operator-=(const T &value) {
		get() = get() - value;
		return *this;
	}

	scalar_ref &operator*=(const T &value) {
		get() = get() * value;
		return *this;
	}

	scalar_ref &operator/=(const T &value) {
		get() = get() / value;
		return *this;
	}
};

using int_ref = scalar_ref<int_t>;
using float_ref = scalar_ref<float_t>;
using bool_ref = scalar_ref<bool_t>;
using string_ref = scalar_ref<string_t>;

} // namespace scpp
