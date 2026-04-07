#include "test_common.hpp"

// Mirrors the authoritative array_semantics.md contract for behaviors that already have
// direct runtime-level observability. This file intentionally focuses on semantic invariants
// rather than generator lowering details.

namespace {

scpp::mixed_t make_table_value() {
	return scpp::mixed_t{scpp::unique<scpp::hash_t<scpp::mixed_t>>()};
}

void test_missing_value_read_returns_null_without_insertion() {
	scpp::mixed_t root = make_table_value();
	root[scpp::string_t("present")] = scpp::mixed_t(scpp::int_t(1));

	const auto before_size = root.size().native_value();
	const auto missing = root.get(scpp::string_t("missing"));

	assert(missing.is_null().native_value() == true);
	assert(root.size().native_value() == before_size);
	assert(root.isset(scpp::string_t("missing")).native_value() == false);
}

void test_nested_value_reads_do_not_autovivify() {
	scpp::mixed_t root = make_table_value();
	root[scpp::string_t("child")] = make_table_value();
	root[scpp::string_t("child")][scpp::string_t("count")] = scpp::mixed_t(scpp::int_t(3));

	const auto before_child_size = root.get("child").size().native_value();
	const auto nested_missing = root.get("child").get("missing");

	assert(nested_missing.is_null().native_value() == true);
	assert(root.get("child").size().native_value() == before_child_size);
}

void test_top_level_write_creates_missing_key() {
	scpp::mixed_t root = make_table_value();

	assert(root.isset("name").native_value() == false);
	root["name"] = scpp::mixed_t(scpp::string_t("Alex"));

	assert(root.isset("name").native_value() == true);
	assert(root.get("name").string_if()->native_value() == "Alex");
}

void test_nested_write_autovivifies_missing_intermediate() {
	scpp::mixed_t root = make_table_value();

	root["x"]["y"] = scpp::mixed_t(scpp::int_t(42));

	assert(root.isset("x").native_value() == true);
	assert(root.get("x").get("y").int_value().native_value() == 42);
}

void test_nested_write_throws_on_wrong_intermediate_kind() {
	scpp::mixed_t root = make_table_value();
	root["x"] = scpp::mixed_t(scpp::int_t(7));

	scpp_test::expect_throw<std::runtime_error>([&]() {
		root["x"]["y"] = scpp::mixed_t(scpp::int_t(1));
	});

	assert(root.get("x").int_value().native_value() == 7);
}

void test_append_on_table_carrier_and_null_bootstrap() {
	scpp::mixed_t table_value = make_table_value();
	table_value.append(scpp::mixed_t(scpp::int_t(10)));
	table_value.append(scpp::mixed_t(scpp::int_t(20)));

	assert(table_value.get(scpp::int_t(0)).int_value().native_value() == 10);
	assert(table_value.get(scpp::int_t(1)).int_value().native_value() == 20);

	scpp::mixed_t null_bootstrap{scpp::null_t{}};
	null_bootstrap.append(scpp::mixed_t(scpp::string_t("boot")));

	assert(null_bootstrap.get(scpp::int_t(0)).string_if()->native_value() == "boot");

	scpp::mixed_t not_a_table{scpp::int_t(5)};
	scpp_test::expect_throw<std::runtime_error>([&]() {
		not_a_table.append(scpp::mixed_t(scpp::int_t(1)));
	});
}

void test_unset_missing_is_noop_and_existing_remove_preserves_other_keys() {
	scpp::mixed_t root = make_table_value();
	root["a"] = scpp::mixed_t(scpp::int_t(1));
	root["b"] = scpp::mixed_t(scpp::int_t(2));

	const auto before_missing_remove = root.size().native_value();
	assert(root.table_if()->remove(scpp::string_t("missing")) == false);
	assert(root.size().native_value() == before_missing_remove);

	assert(root.table_if()->remove(scpp::string_t("a")) == true);
	assert(root.isset("a").native_value() == false);
	assert(root.isset("b").native_value() == true);
	assert(root.get("b").int_value().native_value() == 2);
}

void test_nested_unset_with_missing_parent_is_noop_via_guarded_pattern() {
	scpp::mixed_t root = make_table_value();
	root["keep"] = scpp::mixed_t(scpp::int_t(9));

	const auto before_size = root.size().native_value();
	if (root.isset("x").native_value()) {
		(void)root.get("x").table_if()->remove(scpp::string_t("y"));
	}

	assert(root.size().native_value() == before_size);
	assert(root.get("keep").int_value().native_value() == 9);
}


void test_php_probe_helpers_preserve_null_sensitive_non_mutating_contract() {
	scpp::mixed_t root = make_table_value();
	root["child"] = make_table_value();
	root["child"]["name"] = scpp::mixed_t(scpp::string_t("Alex"));
	root["child"]["maybe"] = scpp::mixed_t(scpp::null_t{});

	const auto before_root_size = root.size().native_value();
	const auto before_child_size = root.get("child").size().native_value();

	assert(scpp::php::isset(root.get("child").get("name")).native_value() == true);
	assert(scpp::php::isset(root.get("child").get("maybe")).native_value() == false);
	assert(scpp::php::isset(root.get("child").get("missing")).native_value() == false);
	assert(scpp::php::empty(root.get("child").get("maybe")).native_value() == true);
	assert(scpp::php::empty(root.get("child").get("missing")).native_value() == true);
	assert(root.size().native_value() == before_root_size);
	assert(root.get("child").size().native_value() == before_child_size);
}

void test_try_ref_shared_pointer_handle_copy_visibility() {
	scpp::hash_t<scpp::shared_p<scpp_test::sample_object>> shared_table;
	auto shared_object = scpp::shared<scpp_test::sample_object>(scpp::int_t(11));
	shared_table.set(scpp::string_t("obj"), shared_object);

	auto copied_handle = shared_table.try_ref<scpp::shared_p<scpp_test::sample_object>>(scpp::string_t("obj"));
	copied_handle->value = scpp::int_t(99);
	assert(shared_table.at(scpp::string_t("obj"))->value.native_value() == 99);
}

} // namespace

int main() {
	test_missing_value_read_returns_null_without_insertion();
	test_nested_value_reads_do_not_autovivify();
	test_top_level_write_creates_missing_key();
	test_nested_write_autovivifies_missing_intermediate();
	test_nested_write_throws_on_wrong_intermediate_kind();
	test_append_on_table_carrier_and_null_bootstrap();
	test_unset_missing_is_noop_and_existing_remove_preserves_other_keys();
	test_nested_unset_with_missing_parent_is_noop_via_guarded_pattern();
	test_php_probe_helpers_preserve_null_sensitive_non_mutating_contract();
	test_try_ref_shared_pointer_handle_copy_visibility();
	return 0;
}
