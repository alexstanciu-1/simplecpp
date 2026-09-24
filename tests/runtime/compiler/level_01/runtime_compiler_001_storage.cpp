#ifdef NDEBUG
#undef NDEBUG
#endif

#include "scpp/compiler.hpp"
#include "scpp/memory.hpp"

#include <cassert>
#include <cstdlib>
#include <functional>
#include <new>
#include <string>
#include <vector>

// Single-threaded allocation fault injection, enabled only around the operation
// under test. It exercises real vector growth and shared-handle slots.
static long allocations_until_failure = -1;
void *operator new(std::size_t size) {
	if (allocations_until_failure == 0) throw std::bad_alloc();
	if (allocations_until_failure > 0) --allocations_until_failure;
	if (void *memory = std::malloc(size ? size : 1)) return memory;
	throw std::bad_alloc();
}
// Keep the replacement allocation pair opaque to GCC's inlined mismatch check.
[[gnu::noinline]] void operator delete(void *memory) noexcept { std::free(memory); }
[[gnu::noinline]] void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }

using namespace scpp::compiler;

template<class Error, class Fn> void rejects(Fn &&fn) {
	bool caught = false;
	try { fn(); } catch (const Error &) { caught = true; }
	assert(caught);
}

// Noncopyable compiler record: Storage must only copy its shared handle.
struct token {
	static inline int alive = 0;
	int kind;
	std::string text;
	explicit token(int kind, std::string text = "name") : kind(kind), text(std::move(text)) { ++alive; }
	token(const token &) = delete;
	token(token &&) = delete;
	token &operator=(token &&) = delete;
	~token() noexcept { --alive; }
};

template<class... Args> scpp::shared_p<token> make_token(Args &&...args) {
	return scpp::create<token>(std::forward<Args>(args)...);
}


class indexed_tokens final : public Storage<token> {
public:
	std::function<void(bool)> action;
	std::vector<int> events;
	int indexed_kind = 0;
	int query() const { assert_usable(); return indexed_kind; }
protected:
	void before_add(storage_position p, storage_position key, const scpp::shared_p<token> &value) override {
		assert(p == key && !contains(p));
		if (value->kind < 0) throw std::invalid_argument("Rejected token kind");
		if (action) action(false);
		events.push_back(1); // Test trace only, not a production before-hook index.
	}
	void after_add(storage_position p, storage_position key, const scpp::shared_p<token> &value) override {
		assert(p == key && field(p, &token::kind) == value->kind);
		indexed_kind = value->kind;
		events.push_back(2);
		if (action) action(true);
	}
	void before_replace(storage_position p, storage_position key, const scpp::shared_p<token> &old, const scpp::shared_p<token> &value) override {
		assert(p == key && field(p, &token::kind) == old->kind && old->kind != value->kind);
		events.push_back(3);
	}
	void after_replace(storage_position p, storage_position key, const scpp::shared_p<token> &old, const scpp::shared_p<token> &value) override {
		assert(p == key && field(p, &token::kind) == value->kind && old->kind != value->kind);
		indexed_kind = value->kind;
		events.push_back(4);
	}
	void before_remove(storage_position p, storage_position key, const scpp::shared_p<token> &old) override {
		assert(p == key && field(p, &token::kind) == old->kind);
		events.push_back(5);
	}
	void after_remove(storage_position p, storage_position key, const scpp::shared_p<token> &) override {
		assert(p == key && !contains(p));
		indexed_kind = 0;
		events.push_back(6);
	}
};

class failing_view final : public Storage_View_Abstract<token> {
public:
	using Storage_View_Abstract::Storage_View_Abstract;
	bool fail = false;
	bool reenter = false;
protected:
	storage_position append_member(storage_position target) override {
		if (reenter) internal_remove(0);
		const auto position = Storage_View_Abstract::append_member(target);
		if (fail) throw std::bad_alloc(); // Models a failing subclass index allocation after publication.
		return position;
	}
};

void numeric_model() {
	auto owner = std::make_shared<Storage<token>>(4);
	auto alias = owner;
	assert(owner->is_empty() && !owner->find_position(0));
	rejects<std::invalid_argument>([&] { owner->reserve(-1); });
	rejects<std::out_of_range>([&] { owner->read(0); });
	rejects<std::out_of_range>([&] { owner->replace(0, make_token(1)); });
	assert(owner->append(make_token(10)) == 0);
	assert(owner->append(make_token(20)) == 1);
	assert(owner->append(make_token(30)) == 2);
	Storage_View<token> children(owner);
	assert(children.is_empty());
	assert(children.internal_append(2) == 0);
	assert(children.internal_append(0) == 1);
	assert(children.internal_append(2) == 2);
	assert(children.storage_position_at(0) == 2);
	assert(owner->find_position(0).value() == 0);
	children.set_field(0, &token::text, std::string("edited"));
	assert(alias->field(2, &token::text) == "edited");
	assert(children.field(2, &token::text) == "edited");
	const auto old_handle = children.read(0);
	owner->remove(0); // Moves last physical row, preserving every public position.
	assert(owner->count() == 2 && owner->field(2, &token::kind) == 30);
	assert(children.count() == 3 && children.contains(1));
	assert(children.storage_position_at(1) == 0);
	rejects<std::out_of_range>([&] { children.read(1); });
	children.internal_remove(1);
	owner->replace(2, make_token(31));
	assert(children.field(0, &token::kind) == 31 && old_handle->kind == 30);
	children.internal_replace(2, 1);
	assert(children.field(2, &token::kind) == 20);
	assert(owner->append(make_token(40)) == 3);
	owner->reserve(2000);
	children.reserve(100);
	assert(children.storage_append(make_token(50)) == 4);
	assert(children.storage_position_at(3) == 4);
	std::vector<storage_position> seen;
	owner->for_each([&](auto p, auto) { seen.push_back(p); });
	assert((seen == std::vector<storage_position>{1, 2, 3, 4}));
	seen.clear();
	children.for_each([&](auto p, auto) { seen.push_back(p); });
	assert((seen == std::vector<storage_position>{0, 2, 3}));
	owner->for_each([&](auto, auto) {
		rejects<std::logic_error>([&] { owner->reserve(1); });
	});
	rejects<std::logic_error>([&] { children.append(1); });
	rejects<std::logic_error>([&] { children.replace(0, 1); });
	rejects<std::logic_error>([&] { children.remove(0); });
	rejects<std::logic_error>([&] { children.unset(999); });
	Storage_View<token, false> writable(owner);
	writable.unset(999);
	assert(writable.append(1) == 0);
	writable.replace(0, 2);
	writable.remove(0);
	assert(writable.append(1) == 1);
	for (int i = 0; i < 1000; ++i) {
		const auto p = owner->append(make_token(i));
		owner->remove(p);
	}
	assert(owner->append(make_token(99)) == 1005);
	std::weak_ptr<Storage<token>> weak = owner;
	owner.reset(); alias.reset();
	assert(!weak.expired() && children.field(0, &token::kind) == 31);
	Storage_View<token> missing;
	rejects<std::logic_error>([&] { missing.count(); });
	rejects<std::logic_error>([&] { missing.read(0); });
	rejects<std::logic_error>([&] { missing.internal_append(0); });
	rejects<std::logic_error>([&] { missing.for_each([](auto, auto) {}); });
}

void hooks_and_failures() {
	auto owner = std::make_shared<indexed_tokens>();
	Storage_View<token> view(owner);
	rejects<std::invalid_argument>([&] { view.storage_append(make_token(-1)); });
	assert(owner->is_empty() && view.is_empty());
	assert(view.storage_append(make_token(1)) == 0);
	owner->replace(0, make_token(2));
	owner->remove(0);
	assert((owner->events == std::vector<int>{1, 2, 3, 4, 5, 6}));
	owner->action = [&](bool) {
		rejects<std::logic_error>([&] { owner->append(make_token(1)); });
		rejects<std::logic_error>([&] { owner->replace(0, make_token(1)); });
		rejects<std::logic_error>([&] { owner->remove(0); });
		rejects<std::logic_error>([&] { owner->unset(999); });
		rejects<std::logic_error>([&] { owner->reserve(4); });
		rejects<std::logic_error>([&] { owner->set_field(0, &token::kind, 4); });
		rejects<std::logic_error>([&] { view.internal_append(0); });
		rejects<std::logic_error>([&] { view.internal_replace(0, 0); });
		rejects<std::logic_error>([&] { view.internal_remove(0); });
		rejects<std::logic_error>([&] { view.reserve(4); });
		rejects<std::logic_error>([&] { view.storage_append(make_token(9)); });
		owner->count(); // Reads remain allowed in both hook phases.
	};
	assert(view.storage_append(make_token(3)) == 1);
	owner->action = [](bool after) { if (!after) throw 42; };
	rejects<int>([&] { owner->append(make_token(4)); });
	assert(owner->count() == 1);
	owner->action = {};
	assert(owner->append(make_token(5)) == 2);
	owner->action = [](bool after) { if (after) throw 73; };
	try { owner->append(make_token(6)); assert(false); } catch (int code) { assert(code == 73); }
	rejects<std::logic_error>([&] { owner->count(); });
	rejects<std::logic_error>([&] { owner->query(); });
	rejects<std::logic_error>([&] { owner->reserve(0); });
	rejects<std::logic_error>([&] { owner->read(1); });
	rejects<std::logic_error>([&] { owner->unset(999); });
	rejects<std::logic_error>([&] { view.count(); });
	rejects<std::logic_error>([&] { view.contains(0); });
	rejects<std::logic_error>([&] { view.internal_remove(0); });

	auto healthy = std::make_shared<Storage<token>>();
	Storage_View<token> other(healthy);
	failing_view broken(healthy);
	broken.fail = true;
	rejects<std::bad_alloc>([&] { broken.storage_append(make_token(7)); });
	assert(healthy->count() == 1 && other.is_empty());
	assert(other.internal_append(0) == 0);
	rejects<std::logic_error>([&] { broken.count(); });
	rejects<std::logic_error>([&] { broken.internal_append(0); });
	failing_view recursive(healthy);
	recursive.reenter = true;
	rejects<std::logic_error>([&] { recursive.internal_append(0); });
	rejects<std::logic_error>([&] { recursive.count(); });
	assert(healthy->count() == 1);
}

void allocation_preparation() {
	bool reached_success = false;
	int rejected = 0;
	for (long fail_at = 0; fail_at < 20; ++fail_at) {
		auto owner = std::make_shared<Storage<token>>();
		owner->append(make_token(1));
		owner->append(make_token(2));
		owner->append(make_token(3));
		owner->append(make_token(4));
		auto candidate = make_token(5, std::string(256, 'x'));
		allocations_until_failure = fail_at;
		bool failed = false;
		try { owner->append(candidate); }
		catch (const std::bad_alloc &) { failed = true; }
		allocations_until_failure = -1;
		if (!failed) { reached_success = true; break; }
		++rejected;
		assert(owner->count() == 4 && owner->field(3, &token::kind) == 4);
		assert(owner->append(candidate) == 4);
	}
	assert(reached_success && rejected >= 3);
	for (long fail_at = 0; fail_at < 3; ++fail_at) {
		Storage<token> owner;
		owner.append(make_token(1));
		allocations_until_failure = fail_at;
		bool failed = false;
		try { owner.reserve(100); } catch (const std::bad_alloc &) { failed = true; }
		allocations_until_failure = -1;
		assert(failed && owner.count() == 1);
		assert(owner.append(make_token(2)) == 1);
	}
}

int main() {
	static_assert(!std::is_copy_constructible_v<Storage<token>>);
	static_assert(!std::is_default_constructible_v<token>);
	static_assert(std::is_abstract_v<Storage_View_Abstract<token>>);
	assert(detail::checked_append_position(0) == 0);
	assert(detail::checked_append_position(std::uint64_t{1} << 32) == (std::int64_t{1} << 32));
	assert(detail::checked_append_position(INT64_MAX - 1) == INT64_MAX - 1);
	rejects<std::overflow_error>([] { detail::checked_append_position(INT64_MAX); });
	rejects<std::overflow_error>([] { detail::checked_append_position(UINT64_MAX); });
	numeric_model();
	assert(token::alive == 0);
	hooks_and_failures();
	assert(token::alive == 0);
	allocation_preparation();
	assert(token::alive == 0);
}
