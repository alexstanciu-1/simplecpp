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

	const auto before_missing_nested = root.get(scpp::string_t("child")).as_table_ref().size();
	assert(root.get(scpp::string_t("child")).get(scpp::string_t("unknown")).is_null().native_value() == true);
	assert(root.get(scpp::string_t("child")).as_table_ref().size() == before_missing_nested);
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

int main() {
	test_table_basic_contract();
	test_table_remove_preserves_numeric_keys();
	test_table_find_val_contract();
	test_table_operator_index_contract();
	test_value_t_table_read_write_contract();
	test_table_shared_object_like_contract();
	return 0;
}
