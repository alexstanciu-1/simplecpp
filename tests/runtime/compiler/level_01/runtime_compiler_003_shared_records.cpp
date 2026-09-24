#ifdef NDEBUG
#undef NDEBUG
#endif

#include "scpp/compiler.hpp"
#include "scpp/memory.hpp"
#include <cassert>

using namespace scpp::compiler;

struct node {
	static inline int alive = 0;
	static inline int destroyed = 0;
	int id;
	explicit node(int id) : id(id) { ++alive; }
	node(const node &) = delete;
	node(node &&) = delete;
	~node() { --alive; ++destroyed; }
};

template<class Error, class Fn> void rejects(Fn &&fn) {
	bool caught = false;
	try { fn(); } catch (const Error &) { caught = true; }
	assert(caught);
}

template<bool Named> class observed_owner final : public Storage<node, Named> {
	using base = Storage<node, Named>;
public:
	using typename base::record_handle;
	using typename base::hook_key_type;
	const node *expected_new = nullptr;
	const node *expected_old = nullptr;
	int hooks = 0;
	bool fail_after_add = false;
protected:
	void before_add(storage_position, hook_key_type, const record_handle &value) override {
		++hooks;
		assert(value.get() == expected_new);
	}
	void after_add(storage_position p, hook_key_type, const record_handle &value) override {
		++hooks;
		assert(value.get() == expected_new && this->read(p).get() == expected_new);
		if (fail_after_add) throw 73;
	}
	void before_replace(storage_position p, hook_key_type, const record_handle &old, const record_handle &value) override {
		++hooks;
		assert(old.get() == expected_old && this->read(p).get() == expected_old);
		assert(value.get() == expected_new);
	}
	void after_replace(storage_position p, hook_key_type, const record_handle &old, const record_handle &value) override {
		++hooks;
		assert(old.get() == expected_old && value.get() == expected_new);
		assert(this->read(p).get() == expected_new);
	}
	void before_remove(storage_position p, hook_key_type, const record_handle &old) override {
		++hooks;
		assert(old.get() == expected_old && this->read(p).get() == expected_old);
	}
	void after_remove(storage_position p, hook_key_type, const record_handle &old) override {
		++hooks;
		assert(old.get() == expected_old && !this->contains(p));
	}
};

template<bool Named, bool ReadOnly> void identities_and_lifetimes() {
	using owner_type = observed_owner<Named>;
	using handle = typename owner_type::record_handle;
	static_assert(std::is_same_v<handle, scpp::shared_p<node>>);
	static_assert(std::is_same_v<decltype(std::declval<Storage_View<node>>().read(0)), handle>);
	const auto first_key = Named ? std::optional<std::string>("first") : std::nullopt;
	const auto second_key = Named ? std::optional<std::string>("second") : std::nullopt;
	auto owner = std::make_shared<owner_type>();
	auto view = std::make_shared<Storage_View<node, ReadOnly>>(owner);
	auto view_alias = view;

	handle empty;
	rejects<std::invalid_argument>([&] { owner->append(empty, first_key); });
	rejects<std::invalid_argument>([&] { view->storage_append(empty, first_key); });
	assert(owner->hooks == 0 && owner->is_empty() && view->is_empty());
	auto original = scpp::create<node>(1);
	owner->expected_new = original.get();
	assert(view->storage_append(original, first_key) == 0);
	assert(node::alive == 1); // No second record allocation during insertion.
	assert(owner->read(0).get() == original.get());
	assert(view->read(0).get() == original.get());
	if constexpr (Named) assert(owner->read("first").get() == original.get());
	assert(view->internal_append(0) == 1);
	auto retained = view_alias->read(1);
	retained->id = 7;
	assert(original->id == 7 && owner->read(0)->id == 7 && view->read(0)->id == 7);
	view->read(0)->id = 8; // Fluent shared-record edit, including read-only membership.
	assert(retained->id == 8);
	owner->for_each([&](const auto &, handle item) { assert(item.get() == original.get()); });
	view->for_each([&](auto, handle item) { assert(item.get() == original.get()); });
	const int hooks_before_null = owner->hooks;
	rejects<std::invalid_argument>([&] { owner->replace(0, empty); });
	if constexpr (Named) {
		rejects<std::invalid_argument>([&] { owner->assign("first", empty); });
		rejects<std::invalid_argument>([&] { owner->assign("absent", empty); });
	}
	assert(owner->hooks == hooks_before_null && owner->read(0).get() == original.get());
	owner->reserve(1024);
	assert(owner->read(0).get() == original.get() && retained->id == 8);

	auto second = scpp::create<node>(2);
	owner->expected_new = second.get();
	assert(owner->append(second, second_key) == 1);
	owner->expected_old = original.get();
	owner->remove(0); // Physically relocates the second handle, not its record.
	assert(owner->read(1).get() == second.get() && original->id == 8);
	assert(view->count() == 2 && view->contains(0));
	rejects<std::out_of_range>([&] { view->read(0); });
	view->internal_replace(0, 1);
	assert(view->read(0).get() == second.get());

	auto replacement = scpp::create<node>(3);
	owner->expected_old = second.get(); owner->expected_new = replacement.get();
	owner->replace(1, replacement);
	assert(second->id == 2 && owner->read(1).get() == replacement.get());
	assert(view->read(0).get() == replacement.get());
	if constexpr (Named) assert(owner->read("second").get() == replacement.get());
	const auto weak_old = std::weak_ptr<node>(original.native_value());
	original.reset();
	assert(!weak_old.expired() && retained->id == 8);
	const int before_release = node::destroyed;
	retained.reset();
	assert(weak_old.expired() && node::destroyed == before_release + 1);
	second.reset(); // The replaced record is independent of its old position.
	assert(node::alive == 1);

	auto surviving = view->read(0);
	const auto weak_current = std::weak_ptr<node>(replacement.native_value());
	replacement.reset();
	owner.reset(); view.reset(); view_alias.reset();
	assert(!weak_current.expired() && surviving->id == 3); // Survives owner destruction too.
	surviving.reset();
	assert(weak_current.expired() && node::alive == 0);
}

void growth_and_failure() {
	auto owner = std::make_shared<Storage<node>>();
	auto original = scpp::create<node>(1);
	owner->append(original);
	const auto retained = owner->read(0);
	for (int i = 0; i < 1000; ++i) owner->append(scpp::create<node>(i));
	assert(owner->read(0).get() == original.get() && retained.get() == original.get());
	auto failing = std::make_shared<observed_owner<false>>();
	Storage_View<node> view(failing);
	failing->expected_new = original.get();
	failing->append(original);
	const auto before_failure = failing->read(0);
	failing->fail_after_add = true;
	try { view.storage_append(original); assert(false); } catch (int code) { assert(code == 73); }
	rejects<std::logic_error>([&] { view.read(0); });
	rejects<std::logic_error>([&] { failing->read(0); });
	original->id = 42;
	assert(retained->id == 42 && before_failure->id == 42); // An owner failure cannot revoke a shared record handle.
}

int main() {
	identities_and_lifetimes<false, false>();
	identities_and_lifetimes<false, true>();
	identities_and_lifetimes<true, false>();
	identities_and_lifetimes<true, true>();
	growth_and_failure();
	assert(node::alive == 0);
}
