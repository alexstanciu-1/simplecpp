#pragma once

#include "modules/compiler/storage.hpp"
#include "scpp/bool_t.hpp"

namespace scpp::compiler {

// A typed read cursor can widen yielded handles without converting Storage<U> to
// Storage<T>. The collection membership remains invariant and shared, never copied.
template<class T>
class Storage_Cursor {
	using handle = typename detail::storage_record<T>::handle;
	struct cursor_state {
		bool advanced = false;
		virtual ~cursor_state() = default;
		virtual bool valid() const = 0;
		virtual handle current() const = 0;
		virtual std::int64_t key() const = 0;
		virtual void next() = 0;
	};
	template<class U>
	struct source_cursor final : cursor_state {
		Storage<U> source;
		typename Storage<U>::iterator position;
		explicit source_cursor(Storage<U> value) : source(std::move(value)), position(source.begin()) {}
		bool valid() const override { return !(position == source.end()); }
		handle current() const override { return (*position).second; }
		std::int64_t key() const override { return (*position).first; }
		void next() override { if (valid()) ++position; }
	};
	std::shared_ptr<cursor_state> cursor_;
	void require_valid() const {
		if (!cursor_ || !cursor_->valid()) throw std::logic_error("Storage cursor has no current record");
	}
public:
	Storage_Cursor() = default; // Required fields may be populated before publication.
	template<class U> requires std::convertible_to<typename Storage<U>::record_handle, handle>
	explicit Storage_Cursor(Storage<U> source) : cursor_(std::make_shared<source_cursor<U>>(std::move(source))) {}
	[[nodiscard]] scpp::bool_t valid() const { if (!cursor_) throw std::logic_error("Storage cursor is not initialized"); return scpp::bool_t(cursor_->valid()); }
	[[nodiscard]] handle current() const { require_valid(); return cursor_->current(); }
	[[nodiscard]] scpp::int_t<> key() const { require_valid(); return scpp::int_t<>(cursor_->key()); }
	void next() { if (static_cast<bool>(valid())) { cursor_->next(); cursor_->advanced = true; } }
	void rewind() const {
		if (!cursor_) throw std::logic_error("Storage cursor is not initialized");
		if (cursor_->advanced) throw std::logic_error("Create a fresh Storage cursor to restart traversal");
	}
};

} // namespace scpp::compiler
