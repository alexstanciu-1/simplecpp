#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/result.hpp"
#include "scpp/result_or_bool.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/support/hash_t.hpp"
#include "scpp/vector_t.hpp"

namespace scpp {

template <typename T>
class foreach_vector_entry_view final {
public:
	foreach_vector_entry_view(vector_t<T> *owner, std::size_t index) noexcept
		: owner_(owner), index_(index) {}

	[[nodiscard]] int_t<> key() const {
		return int_t<>{static_cast<std::int64_t>(index_)};
	}

	[[nodiscard]] T value_copy() const requires std::copyable<T> {
		return owner_->at(index_);
	}

	[[nodiscard]] T &value_ref() const {
		return owner_->at(index_);
	}

private:
	vector_t<T> *owner_;
	std::size_t index_;
};

template <typename T>
class foreach_const_vector_entry_view final {
public:
	foreach_const_vector_entry_view(const vector_t<T> *owner, std::size_t index) noexcept
		: owner_(owner), index_(index) {}

	[[nodiscard]] int_t<> key() const {
		return int_t<>{static_cast<std::int64_t>(index_)};
	}

	[[nodiscard]] T value_copy() const requires std::copyable<T> {
		return owner_->at(index_);
	}

private:
	const vector_t<T> *owner_;
	std::size_t index_;
};

template <typename T>
class foreach_vector_iterator final {
public:
	foreach_vector_iterator(vector_t<T> *owner, std::size_t index) noexcept
		: owner_(owner), index_(index) {}

	[[nodiscard]] foreach_vector_entry_view<T> operator*() const noexcept {
		return foreach_vector_entry_view<T>(owner_, index_);
	}

	foreach_vector_iterator &operator++() noexcept {
		++index_;
		return *this;
	}

	[[nodiscard]] bool operator==(const foreach_vector_iterator &other) const noexcept {
		return owner_ == other.owner_ && index_ == other.index_;
	}

	[[nodiscard]] bool operator!=(const foreach_vector_iterator &other) const noexcept {
		return !(*this == other);
	}

private:
	vector_t<T> *owner_;
	std::size_t index_;
};

template <typename T>
class foreach_const_vector_iterator final {
public:
	foreach_const_vector_iterator(const vector_t<T> *owner, std::size_t index) noexcept
		: owner_(owner), index_(index) {}

	[[nodiscard]] foreach_const_vector_entry_view<T> operator*() const noexcept {
		return foreach_const_vector_entry_view<T>(owner_, index_);
	}

	foreach_const_vector_iterator &operator++() noexcept {
		++index_;
		return *this;
	}

	[[nodiscard]] bool operator==(const foreach_const_vector_iterator &other) const noexcept {
		return owner_ == other.owner_ && index_ == other.index_;
	}

	[[nodiscard]] bool operator!=(const foreach_const_vector_iterator &other) const noexcept {
		return !(*this == other);
	}

private:
	const vector_t<T> *owner_;
	std::size_t index_;
};

template <typename T>
class foreach_vector_range final {
public:
	foreach_vector_range() noexcept
		: owner_(&empty_owner()) {}

	foreach_vector_range(vector_t<T> &owner) noexcept
		: owner_(&owner) {}

	[[nodiscard]] foreach_vector_iterator<T> begin() const noexcept {
		return foreach_vector_iterator<T>(owner_, 0);
	}

	[[nodiscard]] foreach_vector_iterator<T> end() const noexcept {
		return foreach_vector_iterator<T>(owner_, owner_->size());
	}

private:
	static vector_t<T> &empty_owner() {
		static vector_t<T> empty;
		return empty;
	}

	vector_t<T> *owner_;
};

template <typename T>
class foreach_const_vector_range final {
public:
	foreach_const_vector_range() noexcept
		: owner_(&empty_owner()) {}

	foreach_const_vector_range(const vector_t<T> &owner) noexcept
		: owner_(&owner) {}

	[[nodiscard]] foreach_const_vector_iterator<T> begin() const noexcept {
		return foreach_const_vector_iterator<T>(owner_, 0);
	}

	[[nodiscard]] foreach_const_vector_iterator<T> end() const noexcept {
		return foreach_const_vector_iterator<T>(owner_, owner_->size());
	}

private:
	static const vector_t<T> &empty_owner() {
		static const vector_t<T> empty;
		return empty;
	}

	const vector_t<T> *owner_;
};

template <typename T, typename K = typename default_hash_key<T>::type>
class foreach_hash_range final {
public:
	foreach_hash_range() noexcept
		: owner_(&empty_owner()) {}

	foreach_hash_range(hash_t<T, K> &owner) noexcept
		: owner_(&owner) {}

	[[nodiscard]] auto begin() const noexcept {
		return owner_->begin_entries();
	}

	[[nodiscard]] auto end() const noexcept {
		return owner_->end_entries();
	}

private:
	static hash_t<T, K> &empty_owner() {
		static hash_t<T, K> empty;
		return empty;
	}

	hash_t<T, K> *owner_;
};

template <typename T>
[[nodiscard]] inline foreach_vector_range<T> foreach_range(vector_t<T> &value) noexcept {
	return foreach_vector_range<T>(value);
}

template <typename T>
[[nodiscard]] inline foreach_const_vector_range<T> foreach_range(const vector_t<T> &value) noexcept {
	return foreach_const_vector_range<T>(value);
}

template <typename T, typename K>
[[nodiscard]] inline foreach_hash_range<T, K> foreach_range(hash_t<T, K> &value) noexcept {
	return foreach_hash_range<T, K>(value);
}

[[nodiscard]] inline foreach_hash_range<mixed_t> foreach_range(mixed_t &value) noexcept {
	if (auto table = value.try_get_hash()) {
		return foreach_hash_range<mixed_t>(*table);
	}
	return foreach_hash_range<mixed_t>();
}

template <typename T>
[[nodiscard]] inline foreach_vector_range<T> foreach_range(result<vector_t<T>> &value) noexcept {
	if (value.has_value().native_value()) {
		return foreach_vector_range<T>(value.value());
	}
	return foreach_vector_range<T>();
}

template <typename T>
[[nodiscard]] inline foreach_vector_range<T> foreach_range(result_or_false<vector_t<T>> &value) noexcept {
	if (value.has_value().native_value()) {
		return foreach_vector_range<T>(value.value());
	}
	return foreach_vector_range<T>();
}

template <typename T>
[[nodiscard]] inline foreach_vector_range<T> foreach_range(result_or_bool<vector_t<T>> &value) noexcept {
	if (value.has_value().native_value()) {
		return foreach_vector_range<T>(value.value());
	}
	return foreach_vector_range<T>();
}

template <typename T, typename K>
[[nodiscard]] inline foreach_hash_range<T, K> foreach_range(result<hash_t<T, K>> &value) noexcept {
	if (value.has_value().native_value()) {
		return foreach_hash_range<T, K>(value.value());
	}
	return foreach_hash_range<T, K>();
}

template <typename T, typename K>
[[nodiscard]] inline foreach_hash_range<T, K> foreach_range(result_or_false<hash_t<T, K>> &value) noexcept {
	if (value.has_value().native_value()) {
		return foreach_hash_range<T, K>(value.value());
	}
	return foreach_hash_range<T, K>();
}

template <typename T, typename K>
[[nodiscard]] inline foreach_hash_range<T, K> foreach_range(result_or_bool<hash_t<T, K>> &value) noexcept {
	if (value.has_value().native_value()) {
		return foreach_hash_range<T, K>(value.value());
	}
	return foreach_hash_range<T, K>();
}

[[nodiscard]] inline foreach_hash_range<mixed_t> foreach_range(result<mixed_t> &value) noexcept {
	if (value.has_value().native_value()) {
		return foreach_range(value.value());
	}
	return foreach_hash_range<mixed_t>();
}

[[nodiscard]] inline foreach_hash_range<mixed_t> foreach_range(result_or_false<mixed_t> &value) noexcept {
	if (value.has_value().native_value()) {
		return foreach_range(value.value());
	}
	return foreach_hash_range<mixed_t>();
}

[[nodiscard]] inline foreach_hash_range<mixed_t> foreach_range(result_or_bool<mixed_t> &value) noexcept {
	if (value.has_value().native_value()) {
		return foreach_range(value.value());
	}
	return foreach_hash_range<mixed_t>();
}

} // namespace scpp
