#include "test_common.hpp"

// Verifies hash_t keeps basic set/find/at/remove semantics aligned with the runtime contract.
static void test_table_basic_contract() {
	scpp::hash_t t;

	assert(t.empty().native_value() == true);
	assert(t.is_packed().native_value() == true);
	assert(t.append(scpp::mixed_t(scpp::int_t(10))).native_value() == 0);
	assert(t.append(scpp::mixed_t(scpp::int_t(20))).native_value() == 1);
	assert(t.size() == 2);

	auto missing = t.find(scpp::string_t("missing"));
	assert(scpp::was_found(missing).native_value() == false);
	assert(t.has(scpp::string_t("missing")).native_value() == false);

	scpp_test::expect_throw<std::out_of_range>([&]() {
		(void)t.at(scpp::string_t("missing"));
	});

	t.set(scpp::string_t("name"), scpp::mixed_t(scpp::string_t("Alex")));
	assert(t.has(scpp::string_t("name")).native_value() == true);
	assert(t.find(scpp::string_t("name")).has_value().native_value() == true);
	assert(t._find_val(scpp::string_t("name")).string_if()->native_value() == "Alex");
}

// Verifies packed-mode remove does not renumber later numeric keys.
static void test_table_remove_preserves_numeric_keys() {
	scpp::hash_t t;
	(void)t.append(scpp::mixed_t(scpp::int_t(100))); // key 0
	(void)t.append(scpp::mixed_t(scpp::int_t(200))); // key 1
	assert(t.is_packed().native_value() == true);

	assert(t.remove(scpp::int_t(0)) == true);
	assert(t.is_packed().native_value() == false);
	assert(t.has(scpp::int_t(0)).native_value() == false);
	assert(t.has(scpp::int_t(1)).native_value() == true);
	assert(t.size() == 1);

	const auto next = t.append(scpp::mixed_t(scpp::int_t(300)));
	assert(next.native_value() == 2);
	assert(t.has(scpp::int_t(2)).native_value() == true);
}

// Verifies _find_val() is non-inserting and returns null on miss.
static void test_table_find_val_contract() {
	scpp::hash_t t;

	t.set(scpp::string_t("n"), scpp::mixed_t(scpp::int_t(7)));
	t.set(scpp::string_t("z"), scpp::mixed_t(scpp::null_t{}));

	const auto before_size = t.size();

	auto found = t._find_val(scpp::string_t("n"));
	assert(found.kind() == scpp::mixed_t::kind_t::int_v);
	assert(found.int_value().native_value() == 7);
	assert(t.size() == before_size);

	auto missing = t._find_val(scpp::string_t("missing"));
	assert(missing.is_null().native_value() == true);
	assert(t.has(scpp::string_t("missing")).native_value() == false);
	assert(t.size() == before_size);

	auto stored_null = t._find_val(scpp::string_t("z"));
	assert(stored_null.is_null().native_value() == true);
	assert(t.has(scpp::string_t("z")).native_value() == true);
}

// Verifies mutable operator[] materializes missing slots while const reads remain non-inserting.
static void test_table_operator_index_contract() {
	scpp::hash_t t;
	t.set(scpp::string_t("a"), scpp::mixed_t(scpp::int_t(1)));

	const auto before_const_read = t.size();
	const auto &const_ref = static_cast<const scpp::hash_t<> &>(t)[scpp::string_t("missing")];
	assert(const_ref.is_null().native_value() == true);
	assert(t.size() == before_const_read);
	assert(t.has(scpp::string_t("missing")).native_value() == false);

	t[scpp::string_t("missing")] = scpp::mixed_t(scpp::int_t(9));
	assert(t.has(scpp::string_t("missing")).native_value() == true);
	assert(t._find_val(scpp::string_t("missing")).int_value().native_value() == 9);
}

// Verifies mixed_t table wrappers support nested writes, append, and non-autovivifying get().
static void test_value_t_table_read_write_contract() {
	scpp::mixed_t root{scpp::unique<scpp::hash_t<scpp::mixed_t>>()};
	root[scpp::string_t("name")] = scpp::mixed_t(scpp::string_t("Alex"));
	root[scpp::string_t("child")] = scpp::mixed_t(scpp::unique<scpp::hash_t<scpp::mixed_t>>());
	root[scpp::string_t("child")][scpp::string_t("count")] = scpp::mixed_t(scpp::int_t(3));
	root[scpp::string_t("child")].append(scpp::mixed_t(scpp::int_t(10)));
	root[scpp::string_t("child")].append(scpp::mixed_t(scpp::int_t(20)));

	assert(root.get(scpp::string_t("name")).string_if()->native_value() == "Alex");
	assert(root.get(scpp::string_t("child")).get(scpp::string_t("count")).int_value().native_value() == 3);
	assert(root.get(scpp::string_t("child")).get(scpp::int_t(0)).int_value().native_value() == 10);
	assert(root.get(scpp::string_t("child")).get(scpp::int_t(1)).int_value().native_value() == 20);
	assert(root.get(scpp::string_t("missing")).is_null().native_value() == true);

	const auto before_missing_nested = root.get(scpp::string_t("child")).size().native_value();
	assert(root.get(scpp::string_t("child")).get(scpp::string_t("unknown")).is_null().native_value() == true);
	assert(root.get(scpp::string_t("child")).size().native_value() == before_missing_nested);
}


// Verifies entry iteration preserves associative keys, skips tombstones, and exposes mutable value refs.
static void test_table_entry_iteration_contract() {
	scpp::hash_t t;
	t.set(scpp::string_t("a"), scpp::mixed_t(scpp::int_t(1)));
	t.set(scpp::string_t("b"), scpp::mixed_t(scpp::int_t(2)));
	t.set(scpp::int_t(7), scpp::mixed_t(scpp::int_t(3)));
	assert(t.remove(scpp::string_t("b")) == true);

	int seen = 0;
	bool saw_a = false;
	bool saw_7 = false;
	for (auto it = t.begin_entries(); it != t.end_entries(); ++it) {
		auto entry = *it;
		auto key = entry.key();
		auto &value = entry.value_ref();
		++seen;
		if (key.string_if() != nullptr && key.string_if()->native_value() == "a") {
			saw_a = true;
			assert(value.int_value().native_value() == 1);
			value = scpp::mixed_t(scpp::int_t(11));
			continue;
		}
		if (key.kind() == scpp::mixed_t::kind_t::int_v && key.int_value().native_value() == 7) {
			saw_7 = true;
			assert(value.int_value().native_value() == 3);
			value = scpp::mixed_t(scpp::int_t(13));
			continue;
		}
		assert(false && "unexpected entry key during hash_t entry iteration");
	}

	assert(seen == 2);
	assert(saw_a == true);
	assert(saw_7 == true);
	assert(t._find_val(scpp::string_t("a")).int_value().native_value() == 11);
	assert(t._find_val(scpp::int_t(7)).int_value().native_value() == 13);
	assert(t.has(scpp::string_t("b")).native_value() == false);
}

// Verifies shared-owned hash_t remains the object-like carrier form and works with _find_val().
static void test_table_shared_object_like_contract() {
	auto shared = std::make_shared<scpp::hash_t<>>();
	shared->set(scpp::string_t("name"), scpp::mixed_t(scpp::string_t("Alex")));

	scpp::mixed_t object_value(shared);
	const auto *as_table = object_value.table_if();
	assert(as_table != nullptr);

	auto name = as_table->_find_val(scpp::string_t("name"));
	assert(name.string_if() != nullptr);
	assert(name.string_if()->native_value() == "Alex");

	auto missing = as_table->_find_val(scpp::string_t("missing"));
	assert(missing.is_null().native_value() == true);
}


// Verifies typed hash_t specializations, clear(), and builder helpers remain usable.
static void test_typed_table_and_builder_contract() {
	scpp::hash_t<scpp::int_t, scpp::int_t> ints;
	(void)ints.append(scpp::int_t(10));
	(void)ints.append(scpp::int_t(20));
	ints.set(scpp::int_t(7), scpp::int_t(99));
	assert(ints.size() == 3);
	assert(ints.at(scpp::int_t(0)).native_value() == 10);
	assert(ints.at(scpp::int_t(1)).native_value() == 20);
	assert(ints.at(scpp::int_t(7)).native_value() == 99);
	assert(scpp::was_found(ints.find(scpp::int_t(1))).native_value() == true);
	assert(scpp::is_nullopt(ints.find(scpp::int_t(99))).native_value() == true);

	scpp::hash_t<scpp::string_t, scpp::int_t> strings;
	(void)strings.append(scpp::string_t("alpha"));
	(void)strings.append(scpp::string_t("beta"));
	strings.set(scpp::int_t(7), scpp::string_t("gamma"));
	assert(strings.at(scpp::int_t(0)).native_value() == "alpha");
	assert(strings.at(scpp::int_t(1)).native_value() == "beta");
	assert(strings.at(scpp::int_t(7)).native_value() == "gamma");

	auto built = scpp::table_(
		scpp::table_item_(scpp::int_t(1)),
		scpp::table_item_(scpp::string_t("hello")),
		scpp::table_kv_(scpp::string_t("key"), scpp::int_t(99)),
		scpp::table_kv_(2, scpp::bool_t(true))
	);
	assert(built->has(scpp::int_t(0)).native_value() == true);
	assert(built->has(scpp::int_t(1)).native_value() == true);
	assert(built->has(scpp::string_t("key")).native_value() == true);
	assert(built->has(scpp::int_t(2)).native_value() == true);
	assert(built->at(scpp::string_t("key")).int_value().native_value() == 99);

	scpp::hash_t<> clearable;
	(void)clearable.append(scpp::mixed_t(scpp::int_t(1)));
	(void)clearable.append(scpp::mixed_t(scpp::int_t(2)));
	clearable.set(scpp::string_t("k"), scpp::mixed_t(scpp::bool_t(true)));
	assert(clearable.size() == 3);
	clearable.clear();
	assert(clearable.empty().native_value() == true);
	assert(clearable.size() == 0);
	assert(clearable.is_packed().native_value() == true);
	(void)clearable.append(scpp::mixed_t(scpp::int_t(42)));
	assert(clearable.at(scpp::int_t(0)).int_value().native_value() == 42);
}

// Verifies string-key identity stays stable across table construction, lookup, and JSON encoding.
static void test_string_key_identity_contract() {
	scpp::mixed_t probe = scpp::mixed_t{
		scpp::shared_table_(
			scpp::table_kv_(scpp::string_t("name"), scpp::string_t("N")),
			scpp::table_kv_(scpp::string_t("class"), scpp::string_t("C"))
		)
	};

	assert(probe.get(scpp::string_t("name")).get_string().native_value() == "N");
	assert(probe.get(scpp::string_t("class")).get_string().native_value() == "C");
	assert(scpp::php::json_encode(probe).native_value() == "{\"name\":\"N\",\"class\":\"C\"}");
}

// Verifies nested table lookups stay non-inserting and null-safe through the current hash_t/mixed_t API.
static void test_table_find_chain_contract() {
	auto inner = scpp::unique<scpp::hash_t<scpp::mixed_t>>();
	inner->set(scpp::string_t("leaf"), scpp::mixed_t(scpp::int_t(123)));

	scpp::hash_t<> outer;
	outer.set(scpp::string_t("child"), scpp::mixed_t(std::move(inner)));

	auto child = outer.find(scpp::string_t("child"));
	assert(child.has_value().native_value() == true);
	assert(child.value().is_hash().native_value() == true);

	auto leaf = child.value().get_hash().find(scpp::string_t("leaf"));
	assert(leaf.has_value().native_value() == true);
	assert(leaf.value().int_value().native_value() == 123);

	auto missing = child.value().get_hash().find(scpp::string_t("nope"));
	assert(scpp::is_nullopt(missing).native_value() == true);

	scpp::mixed_t missing_child(scpp::null_t{});
	assert(missing_child.is_null().native_value() == true);
}

int main() {
	test_table_basic_contract();
	test_table_remove_preserves_numeric_keys();
	test_table_find_val_contract();
	test_table_operator_index_contract();
	test_value_t_table_read_write_contract();
	test_table_entry_iteration_contract();
	test_table_shared_object_like_contract();
	test_typed_table_and_builder_contract();
	test_string_key_identity_contract();
	test_table_find_chain_contract();
	return 0;
}
