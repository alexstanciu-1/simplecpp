#ifdef NDEBUG
#undef NDEBUG
#endif

#include "scpp/compiler.hpp"
#include <cassert>
#include <cstdlib>
#include <functional>
#include <new>
#include <string>
#include <vector>

// Real hash/slot allocation failures, enabled only around the tested operation.
static long allocations_until_failure = -1;
void *operator new(std::size_t size) {
	if (allocations_until_failure == 0) throw std::bad_alloc();
	if (allocations_until_failure > 0) --allocations_until_failure;
	if (void *memory = std::malloc(size ? size : 1)) return memory;
	throw std::bad_alloc();
}
[[gnu::noinline]] void operator delete(void *memory) noexcept { std::free(memory); }
[[gnu::noinline]] void operator delete(void *memory, std::size_t) noexcept { std::free(memory); }

using namespace scpp::compiler;
struct record {
	static inline int alive = 0;
	int id;
	std::string text;
	explicit record(int id, std::string text = "value") : id(id), text(std::move(text)) { ++alive; }
	record(const record &other) : id(other.id), text(other.text) { ++alive; }
	record(record &&other) noexcept : id(other.id), text(std::move(other.text)) { ++alive; }
	record &operator=(record &&) noexcept = default;
	~record() noexcept { --alive; }
};
template<class Error, class Fn> void rejects(Fn &&fn) {
	bool caught = false;
	try { fn(); } catch (const Error &) { caught = true; }
	assert(caught);
}

class observed_storage final : public Storage<record, true> {
public:
	std::function<void(bool)> action;
	std::vector<std::string> events;
	int indexed_id = -1;
	int query() const { assert_usable(); return indexed_id; }
protected:
	void before_add(storage_position p, const std::string &key, const record &value) override {
		assert(!contains(key) && !contains(p));
		if (value.id < 0) throw std::invalid_argument("Invalid indexed id");
		if (action) action(false);
	}
	void after_add(storage_position p, const std::string &key, const record &value) override {
		assert(position_of(key) == p && snapshot(key).id == value.id);
		indexed_id = value.id;
		events.push_back("add:" + key);
		if (action) action(true);
	}
	void before_replace(storage_position p, const std::string &key, const record &old, const record &) override {
		assert(position_of(key) == p && snapshot(key).id == old.id);
	}
	void after_replace(storage_position p, const std::string &key, const record &old, const record &value) override {
		assert(position_of(key) == p && snapshot(key).id == value.id && old.id != value.id);
		indexed_id = value.id;
		events.push_back("replace:" + key);
	}
	void before_remove(storage_position p, const std::string &key, const record &old) override {
		assert(position_of(key) == p && snapshot(p).id == old.id);
	}
	void after_remove(storage_position p, const std::string &key, const record &) override {
		assert(!contains(key) && !contains(p));
		indexed_id = -1;
		events.push_back("remove:" + key);
	}
};

class failing_membership final : public Storage_View_Abstract<record> {
public:
	using Storage_View_Abstract::Storage_View_Abstract;
protected:
	storage_position append_member(storage_position target) override {
		Storage_View_Abstract::append_member(target);
		throw std::bad_alloc();
	}
};

void keys_and_positions() {
	Storage<record, true> store(8);
	assert(store.is_empty());
	rejects<std::invalid_argument>([&] { store.append(record(1)); });
	rejects<std::invalid_argument>([&] { store.position_of(0); });
	rejects<std::invalid_argument>([&] { store.find_position(0); });
	rejects<std::invalid_argument>([&] { store.reserve(-1); });
	rejects<std::out_of_range>([&] { store.snapshot("missing"); });
	store.unset("missing");
	store.unset(-1);
	const std::vector<std::string> keys{"", "0", "00", "-1", "01", "9223372036854775807", std::string("x\0y", 3)};
	for (std::size_t i = 0; i < keys.size(); ++i) assert(store.append(record(static_cast<int>(i)), keys[i]) == static_cast<storage_position>(i));
	assert(store.position_of("") == 0 && store.find_position("").value() == 0);
	assert(!store.find_position("absent"));
	for (std::size_t i = 0; i < keys.size(); ++i) {
		assert(store.snapshot(keys[i]).id == static_cast<int>(i));
		assert(store.snapshot(static_cast<storage_position>(i)).id == static_cast<int>(i));
	}
	rejects<std::invalid_argument>([&] { store.append(record(9), "0"); });
	store.assign("0", record(10));
	assert(store.position_of("0") == 1 && store.count() == keys.size());
	store.replace(1, record(11));
	assert(store.snapshot("0").id == 11);
	store.set_field("0", &record::text, std::string("changed"));
	assert(store.field(1, &record::text) == "changed");
	store.unset("00"); // Moves physical last row in both record/key buffers.
	assert(!store.contains(2) && store.snapshot(std::string("x\0y", 3)).id == 6);
	store.assign("00", record(12));
	assert(store.position_of("00") == 7);
	store.assign("new", record(13));
	assert(store.position_of("new") == 8);
	store.reserve(4096);
	store.reserve(0);
	std::vector<std::string> order;
	store.for_each([&](const auto &key, auto) { order.push_back(key); });
	assert((order == std::vector<std::string>{"", "0", "-1", "01", "9223372036854775807", std::string("x\0y", 3), "00", "new"}));
	for (int i = 0; i < 1000; ++i) {
		store.assign("churn", record(i));
		assert(store.position_of("churn") == 9 + i);
		store.remove(9 + i);
	}
	assert(store.append(record(14), "churn") == 1009);
	rejects<std::out_of_range>([&] { store.replace(99999, record(1)); });
	rejects<std::out_of_range>([&] { store.remove(99999); });
	Storage<record> numeric;
	rejects<std::invalid_argument>([&] { numeric.append(record(1), "0"); });
	rejects<std::invalid_argument>([&] { numeric.assign("0", record(1)); });
	rejects<std::invalid_argument>([&] { numeric.snapshot("0"); });
	rejects<std::invalid_argument>([&] { numeric.unset("0"); });
	assert(numeric.append(record(1)) == 0);
}

template<bool StringKey, bool ReadOnly> void view_matrix() {
	auto owner = std::make_shared<Storage<record, StringKey>>();
	Storage_View<record, ReadOnly> view(owner);
	Storage_View<record> other(owner);
	const auto key = StringKey ? std::optional<std::string>("primary") : std::nullopt;
	assert(view.storage_append(record(1), key) == 0);
	assert(view.internal_append(0) == 1);
	assert(other.is_empty() && view.count() == 2);
	assert(view.storage_position_at(1) == 0);
	view.set_field(1, &record::text, std::string("edited"));
	assert(view.field(0, &record::text) == "edited");
	owner->replace(0, record(2));
	assert(view.snapshot(0).id == 2 && view.snapshot(1).id == 2);
	view.reserve(100);
	if constexpr (ReadOnly) {
		rejects<std::logic_error>([&] { view.append(0); });
		rejects<std::logic_error>([&] { view.replace(0, 0); });
		rejects<std::logic_error>([&] { view.remove(0); });
		rejects<std::logic_error>([&] { view.unset(999); });
	} else {
		assert(view.append(0) == 2);
		view.replace(2, 0);
		view.unset(2);
		view.unset(999);
	}
	if constexpr (StringKey) {
		rejects<std::invalid_argument>([&] { view.storage_append(record(3)); });
		rejects<std::invalid_argument>([&] { view.storage_append(record(3), "primary"); });
	} else rejects<std::invalid_argument>([&] { view.storage_append(record(3), "primary"); });
	assert(view.count() == 2 && owner->count() == 1);
	owner->remove(0);
	assert(view.contains(0) && view.count() == 2 && view.storage_position_at(0) == 0);
	rejects<std::out_of_range>([&] { view.snapshot(0); });
	rejects<std::out_of_range>([&] { view.for_each([](auto, auto) {}); });
	view.internal_remove(0);
	assert(view.storage_append(record(4), key) == 1);
	// Reusing a primary key must not reconnect an old positional membership.
	rejects<std::out_of_range>([&] { view.snapshot(1); });
	view.internal_replace(1, 1);
	std::vector<storage_position> seen;
	view.for_each([&](auto p, auto row) { seen.push_back(p); assert(row.id == 4); });
	assert(seen.size() == 2 && seen[0] == 1);
	std::weak_ptr<Storage<record, StringKey>> weak = owner;
	owner.reset();
	assert(!weak.expired() && view.snapshot(1).id == 4);
}

void hook_protocol() {
	auto owner = std::make_shared<observed_storage>();
	Storage_View<record> view(owner);
	rejects<std::invalid_argument>([&] { view.storage_append(record(-1), "bad"); });
	assert(view.is_empty() && owner->is_empty());
	owner->action = [&](bool) {
		rejects<std::logic_error>([&] { owner->assign("nested", record(1)); });
		rejects<std::logic_error>([&] { owner->append(record(1), "nested"); });
		rejects<std::logic_error>([&] { owner->unset("absent"); });
		rejects<std::logic_error>([&] { owner->unset(999); });
		rejects<std::logic_error>([&] { owner->replace(0, record(1)); });
		rejects<std::logic_error>([&] { owner->remove(0); });
		rejects<std::logic_error>([&] { owner->reserve(1); });
		rejects<std::logic_error>([&] { view.internal_append(0); });
		rejects<std::logic_error>([&] { view.storage_append(record(1), "nested"); });
		owner->for_each([](const auto &, auto) {});
	};
	assert(view.storage_append(record(1), "a") == 0);
	owner->action = {};
	owner->assign("a", record(2));
	owner->unset("a");
	assert((owner->events == std::vector<std::string>{"add:a", "replace:a", "remove:a"}));
	assert(owner->append(record(3), "a") == 1);
	owner->action = [](bool after) { if (!after) throw 12; };
	rejects<int>([&] { owner->assign("rejected", record(4)); });
	assert(!owner->contains("rejected") && owner->count() == 1);
	owner->action = [](bool after) { if (after) throw 42; };
	try { owner->append(record(5), "fail"); assert(false); } catch (int error) { assert(error == 42); }
	rejects<std::logic_error>([&] { owner->find_position("a"); });
	rejects<std::logic_error>([&] { owner->contains(1); });
	rejects<std::logic_error>([&] { owner->query(); });
	rejects<std::logic_error>([&] { owner->unset("absent"); });
	rejects<std::logic_error>([&] { owner->reserve(0); });
	rejects<std::logic_error>([&] { view.count(); });
	rejects<std::logic_error>([&] { view.internal_remove(0); });
}

void allocation_failures() {
	int usable_failures = 0, closed_failures = 0;
	bool success = false;
	for (long fail_at = 0; fail_at < 64; ++fail_at) {
		auto owner = std::make_shared<Storage<record, true>>();
		Storage_View<record> view(owner);
		const std::string key(256, 'k');
		record value(1, std::string(256, 'v'));
		allocations_until_failure = fail_at;
		bool failed = false;
		try { view.storage_append(value, key); } catch (const std::bad_alloc &) { failed = true; }
		allocations_until_failure = -1;
		if (!failed) { assert(view.count() == 1 && owner->position_of(key) == 0); success = true; break; }
		bool closed = false;
		try { owner->assert_usable(); } catch (const std::logic_error &) { closed = true; }
		if (closed) {
			++closed_failures;
			rejects<std::logic_error>([&] { view.count(); });
			rejects<std::logic_error>([&] { owner->snapshot(0); });
		} else {
			++usable_failures;
			assert(owner->is_empty() && view.is_empty() && !owner->find_position(key));
			assert(view.storage_append(value, key) == 0);
		}
	}
	assert(success && usable_failures > 0 && closed_failures > 0);
	// Rehash/reserve failures must leave an existing index and its rows usable.
	success = false;
	int failed_reserves = 0;
	for (long fail_at = 0; fail_at < 16; ++fail_at) {
		Storage<record, true> owner;
		owner.append(record(7), "kept");
		allocations_until_failure = fail_at;
		bool failed = false;
		try { owner.reserve(1000); } catch (const std::bad_alloc &) { failed = true; }
		allocations_until_failure = -1;
		assert(owner.count() == 1 && owner.position_of("kept") == 0 && owner.snapshot("kept").id == 7);
		if (!failed) { success = true; break; }
		++failed_reserves;
		assert(owner.append(record(8), "next") == 1);
	}
	assert(success && failed_reserves >= 7);
	auto owner = std::make_shared<Storage<record, true>>();
	Storage_View<record> healthy_view(owner);
	failing_membership failed_view(owner);
	rejects<std::bad_alloc>([&] { failed_view.storage_append(record(9), "published"); });
	assert(owner->position_of("published") == 0 && owner->snapshot(0).id == 9);
	assert(healthy_view.is_empty() && healthy_view.internal_append(0) == 0);
	rejects<std::logic_error>([&] { failed_view.count(); });
	rejects<std::logic_error>([&] { failed_view.storage_append(record(10), "later"); });
	assert(!owner->contains("later"));
}

int main() {
	static_assert(std::is_empty_v<detail::primary_index<false>>);
	static_assert(sizeof(Storage<record, false>) < sizeof(Storage<record, true>));
	keys_and_positions();
	view_matrix<false, false>(); view_matrix<false, true>();
	view_matrix<true, false>(); view_matrix<true, true>();
	hook_protocol();
	allocation_failures();
	assert(record::alive == 0);
}
