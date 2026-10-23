#include "scpp/source_self.hpp"
#include <cassert>
#include <iostream>

struct left : virtual scpp::source_self_state { virtual ~left() = default; };
struct right : virtual scpp::source_self_state { virtual ~right() = default; };
struct record : left, right { int value = 7; };

template<class T> bool unbound(T* value) {
	try { auto owner = scpp::retain_source_self(value); }
	catch (const scpp::runtime_error&) { return true; }
	return false;
}

// Indirect calls during lifetime transitions must not acquire an owner.
struct transitions : virtual scpp::source_self_state {
	static inline int constructed = 0;
	static inline int destroyed = 0;
	transitions() { assert(unbound(this)); ++constructed; }
	~transitions() { assert(unbound(this)); ++destroyed; }
};

struct failed_construction : virtual scpp::source_self_state {
	std::shared_ptr<int> member;
	explicit failed_construction(std::shared_ptr<int> value) : member(std::move(value)) {
		assert(unbound(this));
		throw std::logic_error("construction failed");
	}
};

int main() {
	auto original = scpp::source_shared<record>();
	auto other = scpp::source_shared<record>();
	auto retained = scpp::retain_source_self(static_cast<right*>(original.get()));
	assert(!original.native_value().owner_before(retained.native_value()));
	assert(!retained.native_value().owner_before(original.native_value()));
	record copied(*original), moved(std::move(copied));
	assert(unbound(&copied) && unbound(&moved));
	*other = *original;
	auto assigned = scpp::retain_source_self(other.get());
	assert(!other.native_value().owner_before(assigned.native_value()));
	assert(!assigned.native_value().owner_before(other.native_value()));
	std::weak_ptr<record> weak = original.native_value();
	original.reset();
	assert(!weak.expired());
	retained.reset();
	assert(weak.expired());
	auto fresh_copy = scpp::source_shared<record>(moved);
	assert(!unbound(fresh_copy.get()));
	assert(unbound(&moved));
	*other = std::move(*fresh_copy);
	auto after_move = scpp::retain_source_self(other.get());
	assert(!other.native_value().owner_before(after_move.native_value()));
	assert(!after_move.native_value().owner_before(other.native_value()));
	auto moved_from_owner = scpp::retain_source_self(fresh_copy.get());
	assert(!fresh_copy.native_value().owner_before(moved_from_owner.native_value()));
	assert(!moved_from_owner.native_value().owner_before(fresh_copy.native_value()));
	{
		auto value = scpp::source_shared<transitions>();
		assert(transitions::constructed == 1 && transitions::destroyed == 0);
	}
	assert(transitions::destroyed == 1);
	auto member = std::make_shared<int>(1);
	try { auto failed = scpp::source_shared<failed_construction>(member); assert(false); }
	catch (const std::logic_error&) {}
	assert(member.use_count() == 1);
	// An arbitrary native shared owner is not implicitly a v0.2 source binding.
	auto native_only = scpp::shared<record>();
	assert(unbound(native_only.get()));
	std::cout << "source self identity, lifecycle and unbound guards passed; state bytes="
		<< sizeof(scpp::source_self_state) << '\n';
}
