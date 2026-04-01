#include "test_common.hpp"

// Verifies table_t keeps read/write/remove semantics aligned with the runtime contract.
static void test_table_basic_contract() {
	scpp::table_t t;

	assert(t.empty().native_value() == true);
	assert(t.is_packed().native_value() == true);
	assert(t.append(scpp::value_t(scpp::int_t(10))).native_value() == 0);
	assert(t.append(scpp::value_t(scpp::int_t(20))).native_value() == 1);
	assert(t.size() == 2);

	auto missing = t.find(scpp::string_t("missing"));
	assert(scpp::was_found(missing).native_value() == false);
	assert(t.has(scpp::string_t("missing")).native_value() == false);

	scpp_test::expect_throw<std::out_of_range>([&]() {
		(void)t.at(scpp::string_t("missing"));
	});

	t.set(scpp::string_t("name"), scpp::value_t(scpp::string_t("Alex")));
	assert(t.has(scpp::string_t("name")).native_value() == true);
	assert(t.find(scpp::string_t("name")).has_value().native_value() == true);
}

// Verifies packed-mode remove does not renumber later numeric keys.
static void test_table_remove_preserves_numeric_keys() {
	scpp::table_t t;
	(void) t.append(scpp::value_t(scpp::int_t(100))); // key 0
	(void) t.append(scpp::value_t(scpp::int_t(200))); // key 1
	assert(t.is_packed().native_value() == true);

	assert(t.remove(scpp::int_t(0)) == true);
	assert(t.is_packed().native_value() == false);
	assert(t.has(scpp::int_t(0)).native_value() == false);
	assert(t.has(scpp::int_t(1)).native_value() == true);
	assert(t.size() == 1);

	const auto next = t.append(scpp::value_t(scpp::int_t(300)));
	assert(next.native_value() == 2);
	assert(t.has(scpp::int_t(2)).native_value() == true);
}

// Verifies _find_val() is non-inserting and returns null on miss.
static void test_table_find_val_contract() {
	scpp::table_t t;

	t.set(scpp::string_t("n"), scpp::value_t(scpp::int_t(7)));
	t.set(scpp::string_t("z"), scpp::value_t(scpp::null_t{}));

	const auto before_size = t.size();

	auto found = t._find_val(scpp::string_t("n"));
	assert(found.kind() == scpp::value_t::kind_t::int_v);
	assert(found.int_value().native_value() == 7);
	assert(t.size() == before_size);

	auto missing = t._find_val(scpp::string_t("missing"));
	assert(missing.is_null().native_value() == true);
	assert(t.has(scpp::string_t("missing")).native_value() == false);
	assert(t.size() == before_size);

	auto stored_null = t._find_val(scpp::string_t("z"));
	assert(stored_null.is_null().native_value() == true);
	assert(t.has(scpp::string_t("z")).native_value() == true);

	auto explicit_missing = t.find(scpp::string_t("missing"));
	assert(scpp::was_found(explicit_missing).native_value() == false);
}


// Verifies slot-based dim access keeps read-miss non-materializing while enabling typed ref binding.
static auto add_by_ref(scpp::ref_int_t left, scpp::int_t right) {
	return static_cast<scpp::int_t>(left) + right;
}

static void test_table_slot_contract() {
	scpp::table_t t;
	t.set(scpp::string_t("a"), scpp::value_t(scpp::int_t(0)));
	t.set(scpp::string_t("b"), scpp::value_t(scpp::int_t(1)));
	t.set(scpp::string_t("c"), scpp::value_t(scpp::int_t(2)));
	(void) t.append(scpp::value_t(scpp::int_t(1)));
	(void) t.append(scpp::value_t(scpp::int_t(2)));
	(void) t.append(scpp::value_t(scpp::int_t(3)));

	auto sum = scpp::table_dim_(t, scpp::int_t(0)) + scpp::table_dim_(t, scpp::int_t(1));
	assert(sum.int_value().native_value() == 3);

	auto ref_sum = add_by_ref(scpp::ref_int(scpp::table_dim_(t, scpp::int_t(2))), scpp::table_dim_(t, scpp::int_t(1)));
	assert(ref_sum.native_value() == 5);

	auto echoed = scpp::php::to_string(scpp::table_dim_(t, scpp::int_t(2)));
	assert(echoed.native_value() == "3");

	const auto before_size = t.size();
	auto missing_read = scpp::table_dim_(t, scpp::string_t("missing"));
	assert(static_cast<scpp::value_t>(missing_read).is_null().native_value() == true);
	assert(t.has(scpp::string_t("missing")).native_value() == false);
	assert(t.size() == before_size);

	scpp::int_t &materialized = static_cast<scpp::int_t &>(scpp::table_dim_(t, scpp::string_t("materialized")));
	materialized = scpp::int_t(9);
	assert(t.has(scpp::string_t("materialized")).native_value() == true);
	assert(t._find_val(scpp::string_t("materialized")).int_value().native_value() == 9);
}


// Verifies a nested slot can bind to the stable slot-backed table reference proxy.
static void write_nested_parent_by_ref(scpp::ref_table_t parent) {
	parent[scpp::string_t("x")] = scpp::value_t(scpp::int_t(100));
	parent[scpp::string_t("y")] = scpp::value_t(scpp::int_t(200));
}

static void stress_nested_parent_and_leaf_refs(scpp::ref_int_t leaf1, scpp::ref_table_t parent, scpp::ref_int_t leaf2) {
	parent[scpp::string_t("x")] = scpp::value_t(scpp::int_t(100));
	parent[scpp::string_t("y")] = scpp::value_t(scpp::int_t(200));
	leaf1 = scpp::int_t(7);
	leaf2 += scpp::value_t(scpp::int_t(1));
}



// Verifies slot copies and string casts work when values flow through ref proxies and row aliases.
static void duplicate_row_refs_runtime(scpp::ref_table_t rows, scpp::ref_table_t r1, scpp::ref_table_t r2, scpp::ref_int_t id) {
	r1[scpp::string_t("left")] = scpp::value_t(scpp::int_t(1));
	r2[scpp::string_t("right")] = scpp::value_t(scpp::int_t(2));
	id = static_cast<scpp::int_t>(id) + scpp::int_t(10);
	scpp::table_dim_(rows, scpp::int_t(0))[scpp::string_t("name")] = scpp::table_value_(scpp::string_t("A") + scpp::cast<scpp::string_t>(id));
	auto copied = scpp::table_dim_(rows, scpp::int_t(0))[scpp::string_t("name")];
	r1[scpp::string_t("final")] = scpp::table_value_(copied);
}

static void test_ref_scalar_string_and_slot_copy_helpers() {
	scpp::table_t x;
	scpp::table_t row;
	row.set(scpp::string_t("id"), scpp::value_t(scpp::int_t(1)));
	row.set(scpp::string_t("name"), scpp::value_t(scpp::string_t("Alex0")));
	(void)x.append(scpp::table_value_(std::move(row)));

	duplicate_row_refs_runtime(
		scpp::ref_table(x),
		scpp::ref_table(scpp::table_dim_(x, scpp::int_t(0))),
		scpp::ref_table(scpp::table_dim_(x, scpp::int_t(0))),
		scpp::ref_int(scpp::table_dim_(x, scpp::int_t(0))[scpp::string_t("id")])
	);

	auto row_after = scpp::table_dim_(x, scpp::int_t(0)).as_table_ref();
	assert(row_after._find_val(scpp::string_t("id")).int_value().native_value() == 11);
	assert(row_after._find_val(scpp::string_t("name")).string_value().native_value() == "A11");
	assert(row_after._find_val(scpp::string_t("left")).int_value().native_value() == 1);
	assert(row_after._find_val(scpp::string_t("right")).int_value().native_value() == 2);
	assert(row_after._find_val(scpp::string_t("final")).string_value().native_value() == "A11");
}

static void test_nested_table_slot_binds_to_table_ref() {
	scpp::table_t a;
	scpp::table_t level_s;
	level_s.set(scpp::string_t("t"), scpp::value_t(scpp::int_t(1)));

	scpp::table_t level_r;
	level_r.set(scpp::string_t("s"), scpp::table_value_(std::move(level_s)));

	a.set(scpp::string_t("r"), scpp::table_value_(std::move(level_r)));

	write_nested_parent_by_ref(scpp::ref_table(scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")]));
	stress_nested_parent_and_leaf_refs(
		scpp::ref_int(scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")][scpp::string_t("t")]),
		scpp::ref_table(scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")]),
		scpp::ref_int(scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")][scpp::string_t("t")])
	);

	auto nested = scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")].as_table_ref();
	assert(nested._find_val(scpp::string_t("t")).int_value().native_value() == 8);
	assert(nested._find_val(scpp::string_t("x")).int_value().native_value() == 100);
	assert(nested._find_val(scpp::string_t("y")).int_value().native_value() == 200);
}


// Verifies arithmetic through a ref_table_t root path stays compilable and alias-coherent.
static void scramble_alias_paths(scpp::ref_table_t mid, scpp::ref_int_t leaf_a, scpp::ref_table_t root, scpp::ref_int_t leaf_b) {
	root[scpp::string_t("r")][scpp::string_t("s")][scpp::string_t("x")] = scpp::value_t(scpp::int_t(10));
	mid[scpp::string_t("y")] = scpp::value_t(scpp::int_t(20));
	leaf_a = (leaf_a + scpp::int_t(3));
	root[scpp::string_t("r")][scpp::string_t("s")][scpp::string_t("t")] =
		(root[scpp::string_t("r")][scpp::string_t("s")][scpp::string_t("t")] + scpp::int_t(4));
	leaf_b = (leaf_b + scpp::int_t(5));
}

static void test_ref_table_root_path_arithmetic_contract() {
	scpp::table_t a;
	scpp::table_t level_s;
	level_s.set(scpp::string_t("t"), scpp::value_t(scpp::int_t(1)));

	scpp::table_t level_r;
	level_r.set(scpp::string_t("s"), scpp::table_value_(std::move(level_s)));

	a.set(scpp::string_t("r"), scpp::table_value_(std::move(level_r)));

	scramble_alias_paths(
		scpp::ref_table(scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")]),
		scpp::ref_int(scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")][scpp::string_t("t")]),
		scpp::ref_table(a),
		scpp::ref_int(scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")][scpp::string_t("t")])
	);

	auto nested = scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")].as_table_ref();
	assert(nested._find_val(scpp::string_t("t")).int_value().native_value() == 13);
	assert(nested._find_val(scpp::string_t("x")).int_value().native_value() == 10);
	assert(nested._find_val(scpp::string_t("y")).int_value().native_value() == 20);
}


// Verifies mixed arithmetic between ref_int_t and table_slot_t remains compilable and alias-coherent.
static void fanout_alias_reads(scpp::ref_int_t leaf, scpp::ref_table_t mid, scpp::ref_table_t root, scpp::ref_int_t again) {
	root[scpp::string_t("r")][scpp::string_t("s")][scpp::string_t("a")] = scpp::value_t(scpp::int_t(1));
	mid[scpp::string_t("b")] = scpp::value_t(scpp::int_t(2));
	leaf = (leaf + scpp::int_t(10));
	root[scpp::string_t("r")][scpp::string_t("s")][scpp::string_t("t")] =
		(root[scpp::string_t("r")][scpp::string_t("s")][scpp::string_t("t")] + mid[scpp::string_t("a")]);
	again = (again + mid[scpp::string_t("b")]);
}

static void test_ref_int_plus_table_slot_contract() {
	scpp::table_t a;
	scpp::table_t level_s;
	level_s.set(scpp::string_t("t"), scpp::value_t(scpp::int_t(5)));

	scpp::table_t level_r;
	level_r.set(scpp::string_t("s"), scpp::table_value_(std::move(level_s)));

	a.set(scpp::string_t("r"), scpp::table_value_(std::move(level_r)));

	fanout_alias_reads(
		scpp::ref_int(scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")][scpp::string_t("t")]),
		scpp::ref_table(scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")]),
		scpp::ref_table(a),
		scpp::ref_int(scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")][scpp::string_t("t")])
	);

	auto nested = scpp::table_dim_(a, scpp::string_t("r"))[scpp::string_t("s")].as_table_ref();
	assert(nested._find_val(scpp::string_t("t")).int_value().native_value() == 18);
	assert(nested._find_val(scpp::string_t("a")).int_value().native_value() == 1);
	assert(nested._find_val(scpp::string_t("b")).int_value().native_value() == 2);
}

// Verifies shared-owned table_t remains the object-like carrier form and works with _find_val().
static void test_table_shared_object_like_contract() {
	auto shared = std::make_shared<scpp::table_t<>>();
	shared->set(scpp::string_t("name"), scpp::value_t(scpp::string_t("Alex")));

	scpp::value_t object_value(shared);
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
	test_table_slot_contract();
	test_nested_table_slot_binds_to_table_ref();
	test_ref_scalar_string_and_slot_copy_helpers();
	test_ref_table_root_path_arithmetic_contract();
	test_ref_int_plus_table_slot_contract();
	test_table_shared_object_like_contract();
	return 0;
}
