#pragma once

#include "scpp/shared_p.hpp"
#include <concepts>
#include <utility>

namespace scpp {

// Nominal marker for the PHP Iterator protocol. Source interfaces declare their
// exact current()/key() types; the runtime never erases them to mixed values.
struct iterator_protocol {
	virtual ~iterator_protocol() = default;
};

template<class T>
concept typed_iterator = std::derived_from<T, iterator_protocol> && requires(T &cursor) {
	cursor.rewind();
	cursor.next();
	static_cast<bool>(cursor.valid());
	cursor.current();
	cursor.key();
};

// One range retains the cursor, not a snapshot of its yielded records. Each begin
// follows the source rewind contract, including forward-only cursor rejection.
template<typed_iterator T>
class foreach_cursor_range {
	shared_p<T> cursor_;
public:
	explicit foreach_cursor_range(shared_p<T> cursor) : cursor_(std::move(cursor)) {}
	struct sentinel {};
	class iterator {
		shared_p<T> cursor_;
	public:
		explicit iterator(shared_p<T> cursor) : cursor_(std::move(cursor)) {}
		struct entry {
			shared_p<T> cursor;
			auto key() const { return cursor->key(); }
			auto value_copy() const { return cursor->current(); }
		};
		entry operator*() const { return {cursor_}; }
		iterator &operator++() { cursor_->next(); return *this; }
		bool operator!=(sentinel) const { return static_cast<bool>(cursor_->valid()); }
	};
	iterator begin() { cursor_->rewind(); return iterator(cursor_); }
	sentinel end() const { return {}; }
};

template<typed_iterator T>
foreach_cursor_range<T> foreach_range(const shared_p<T> &cursor) {
	return foreach_cursor_range<T>(cursor);
}

} // namespace scpp
