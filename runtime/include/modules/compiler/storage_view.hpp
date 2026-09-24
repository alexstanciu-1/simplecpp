#pragma once

#include "modules/compiler/storage.hpp"
#include <variant>

namespace scpp::compiler {

// One shared backing handle, selected once at construction. The owner keeps its
// compile-time key mode; view membership always stores numeric owner positions.
template<class T, bool ReadOnly = true>
class Storage_View_Abstract {
	std::variant<std::shared_ptr<Storage<T, false>>, std::shared_ptr<Storage<T, true>>> storage_;
	detail::position_store<storage_position> members_;
	detail::mutation_state state_;

	template<class Fn> decltype(auto) with_storage(Fn &&fn) const {
		state_.assert_usable();
		return std::visit([&](const auto &storage) -> decltype(auto) {
			if (!storage) throw std::logic_error("Compiler view has no backing owner");
			storage->assert_usable();
			return fn(*storage);
		}, storage_);
	}
	void assert_access() const { with_storage([](const auto &) {}); }
	void assert_writable() const {
		assert_access();
		if constexpr (ReadOnly) throw std::logic_error("Read-only compiler view membership");
	}

protected:
	// Extension points execute inside the guarded write phase.
	virtual storage_position append_member(storage_position position) { return members_.append(position); }
	virtual void replace_member(storage_position position, storage_position target) { members_.replace(position, target); }
	virtual void remove_member(storage_position position) { members_.remove(position); }

public:
	explicit Storage_View_Abstract(std::shared_ptr<Storage<T, false>> storage = {}) : storage_(std::move(storage)) {}
	explicit Storage_View_Abstract(std::shared_ptr<Storage<T, true>> storage) : storage_(std::move(storage)) {}
	virtual ~Storage_View_Abstract() = 0;
	Storage_View_Abstract(const Storage_View_Abstract &) = delete;
	Storage_View_Abstract &operator=(const Storage_View_Abstract &) = delete;
	std::size_t count() const { assert_access(); return members_.size(); }
	bool is_empty() const { return count() == 0; }
	bool contains(storage_position position) const { assert_access(); return members_.has(position); }
	storage_position storage_position_at(storage_position position) const {
		assert_access(); return members_.snapshot(position);
	}
	T snapshot(storage_position position) const {
		const auto target = storage_position_at(position);
		return with_storage([&](const auto &storage) { return storage.snapshot(target); });
	}
	template<class R, class M>
	M field(storage_position position, M R::*member) const {
		const auto target = storage_position_at(position);
		return with_storage([&](const auto &storage) { return storage.field(target, member); });
	}
	template<class R, class M>
	void set_field(storage_position position, M R::*member, M value) {
		detail::mutation_state::mutation mutation(state_);
		const auto target = storage_position_at(position);
		with_storage([&](auto &storage) { storage.set_field(target, member, std::move(value)); });
	}
	void reserve(storage_position capacity) {
		detail::mutation_state::mutation mutation(state_);
		assert_access(); members_.reserve(capacity);
	}
	storage_position internal_append(storage_position target) {
		detail::mutation_state::mutation mutation(state_);
		assert_access();
		with_storage([&](const auto &storage) { storage.require_position(target); });
		members_.prepare_append();
		return mutation.write([&] { return append_member(target); });
	}
	void internal_replace(storage_position position, storage_position target) {
		detail::mutation_state::mutation mutation(state_);
		storage_position_at(position);
		with_storage([&](const auto &storage) { storage.require_position(target); });
		mutation.write([&] { replace_member(position, target); });
	}
	void internal_remove(storage_position position) {
		detail::mutation_state::mutation mutation(state_);
		storage_position_at(position);
		mutation.write([&] { remove_member(position); });
	}
	storage_position append(storage_position target) { assert_writable(); return internal_append(target); }
	void replace(storage_position position, storage_position target) { assert_writable(); internal_replace(position, target); }
	void remove(storage_position position) { assert_writable(); internal_remove(position); }
	void unset(storage_position position) {
		assert_writable();
		{
			detail::mutation_state::mutation mutation(state_);
			if (!contains(position)) return;
		}
		internal_remove(position);
	}
	storage_position storage_append(T record, std::optional<std::string> key = std::nullopt) {
		detail::mutation_state::mutation mutation(state_);
		assert_access();
		members_.prepare_append();
		// Owner rejection is outside the view write phase. Once the row exists,
		// membership failure poisons this view without rolling back the owner.
		const auto position = with_storage([&](auto &storage) { return storage.append(std::move(record), std::move(key)); });
		mutation.write([&] { append_member(position); });
		return position;
	}
	template<class Fn> void for_each(Fn &&fn) const {
		assert_access();
		detail::mutation_state::traversal traversal(state_);
		for (storage_position position = 0; position < members_.next_position(); ++position)
			if (contains(position)) fn(position, snapshot(position));
		assert_access();
	}
};

template<class T, bool ReadOnly>
Storage_View_Abstract<T, ReadOnly>::~Storage_View_Abstract() = default;

template<class T, bool ReadOnly = true>
class Storage_View final : public Storage_View_Abstract<T, ReadOnly> {
public:
	using Storage_View_Abstract<T, ReadOnly>::Storage_View_Abstract;
};

} // namespace scpp::compiler
