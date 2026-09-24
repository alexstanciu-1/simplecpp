#pragma once

// Contract: specs/compiler_storage.md. Native helper; no source binding yet.
#include "modules/compiler/detail/position_store.hpp"
#include "modules/compiler/detail/primary_index.hpp"
#include <memory>
#include <optional>

namespace scpp::compiler {

template<class T, bool UseStringKey = false>
class Storage {
	static_assert(std::is_copy_constructible_v<T>, "Compiler Storage requires snapshot-copyable records");
	detail::position_store<T> records_;
	[[no_unique_address]] detail::primary_index<UseStringKey> keys_;
	detail::mutation_state state_;

public:
	using key_type = std::conditional_t<UseStringKey, std::string, storage_position>;
	using hook_key_type = std::conditional_t<UseStringKey, const std::string &, storage_position>;

private:
	storage_position append_impl(T value, const std::optional<std::string> &key,
		detail::mutation_state::mutation &mutation) {
		keys_.validate_append(key);
		records_.prepare_append();
		const auto position = records_.next_position();
		keys_.prepare_append();
		auto candidate = value;
		auto primary = keys_.candidate(position, key);
		before_add(position, primary, value);
		return mutation.write([&] {
			keys_.append(position, primary);
			records_.append(std::move(candidate));
			after_add(position, primary, value);
			return position;
		});
	}
	void replace_impl(storage_position position, T value, detail::mutation_state::mutation &mutation) {
		const auto old = snapshot(position);
		const auto key = keys_.key_at(position);
		auto candidate = value;
		before_replace(position, key, old, value);
		mutation.write([&] {
			records_.replace(position, std::move(candidate));
			after_replace(position, key, old, value);
		});
	}
	void remove_impl(storage_position position, detail::mutation_state::mutation &mutation) {
		const auto old = snapshot(position);
		const auto key = keys_.key_at(position);
		before_remove(position, key, old);
		mutation.write([&] {
			keys_.remove(position, key);
			records_.remove(position);
			after_remove(position, key, old);
		});
	}

protected:
	virtual void before_add(storage_position, hook_key_type, const T &) {}
	virtual void after_add(storage_position, hook_key_type, const T &) {}
	virtual void before_replace(storage_position, hook_key_type, const T &, const T &) {}
	virtual void after_replace(storage_position, hook_key_type, const T &, const T &) {}
	virtual void before_remove(storage_position, hook_key_type, const T &) {}
	virtual void after_remove(storage_position, hook_key_type, const T &) {}

public:
	explicit Storage(storage_position capacity = 0) { records_.reserve(capacity); keys_.reserve(capacity); }
	virtual ~Storage() = default;
	Storage(const Storage &) = delete;
	Storage &operator=(const Storage &) = delete;
	void assert_usable() const { state_.assert_usable(); }
	std::size_t count() const { assert_usable(); return records_.size(); }
	bool is_empty() const { return count() == 0; }
	bool contains(storage_position position) const { assert_usable(); return records_.has(position); }
	bool contains(const std::string &key) const { return find_position(key).has_value(); }
	storage_position require_position(storage_position position) const {
		if (!contains(position)) throw std::out_of_range("Unknown compiler storage position");
		return position;
	}
	std::optional<storage_position> find_position(storage_position key) const {
		assert_usable();
		if constexpr (UseStringKey) throw std::invalid_argument("String Storage requires a string primary key");
		if (contains(key)) return key;
		return std::nullopt;
	}
	std::optional<storage_position> find_position(const std::string &key) const {
		assert_usable(); return keys_.find(key);
	}
	template<class Key> storage_position position_of(const Key &key) const {
		const auto position = find_position(key);
		if (!position) throw std::out_of_range("Unknown compiler storage key");
		return *position;
	}
	T snapshot(storage_position position) const { assert_usable(); return records_.snapshot(position); }
	T snapshot(const std::string &key) const { return snapshot(position_of(key)); }
	template<class R, class M> M field(storage_position position, M R::*member) const {
		assert_usable(); return records_.field(position, member);
	}
	template<class R, class M> M field(const std::string &key, M R::*member) const {
		return field(position_of(key), member);
	}
	// Only unindexed fields. Indexed changes require replace with a new value.
	template<class Offset, class R, class M> void set_field(const Offset &offset, M R::*member, M value) {
		detail::mutation_state::mutation mutation(state_);
		storage_position position;
		if constexpr (std::is_convertible_v<Offset, std::string>) position = position_of(offset);
		else position = require_position(offset);
		mutation.write([&] { records_.set_field(position, member, std::move(value)); });
	}
	void reserve(storage_position capacity) {
		detail::mutation_state::mutation mutation(state_);
		records_.reserve(capacity); keys_.reserve(capacity);
	}
	storage_position append(T value, std::optional<std::string> key = std::nullopt) {
		detail::mutation_state::mutation mutation(state_);
		return append_impl(std::move(value), key, mutation);
	}
	void replace(storage_position position, T value) {
		detail::mutation_state::mutation mutation(state_);
		replace_impl(position, std::move(value), mutation);
	}
	// Native adapter for keyed assignment: insert a missing string key, otherwise
	// replace its existing row without changing the primary key or position.
	void assign(const std::string &key, T value) {
		detail::mutation_state::mutation mutation(state_);
		const auto position = find_position(key);
		if (position) replace_impl(*position, std::move(value), mutation);
		else append_impl(std::move(value), key, mutation);
	}
	void remove(storage_position position) {
		detail::mutation_state::mutation mutation(state_);
		remove_impl(position, mutation);
	}
	void unset(storage_position position) {
		detail::mutation_state::mutation mutation(state_);
		if (contains(position)) remove_impl(position, mutation);
	}
	void unset(const std::string &key) {
		detail::mutation_state::mutation mutation(state_);
		if (const auto position = find_position(key)) remove_impl(*position, mutation);
	}
	template<class Fn> void for_each(Fn &&fn) const {
		detail::mutation_state::traversal traversal(state_);
		for (storage_position position = 0; position < records_.next_position(); ++position)
			if (contains(position)) fn(keys_.key_at(position), snapshot(position));
	}
};

} // namespace scpp::compiler
