#include "modules/json/pull_reader.hpp"
#include <cassert>
#include <limits>
#include <type_traits>
using namespace scpp;
using scpp::json::pull_reader;
using scpp::json::read_error;

template<class F> void fails(F work, const char *category, const char *path) {
    bool caught = false;
    try { work(); } catch (const read_error &error) {
        caught = true;
        assert(error.category == category);
        assert(error.path == path);
        assert(std::string(error.what()).find("probe.json: byte ") == 0);
    }
    assert(caught);
}
int main() {
    static_assert(!std::is_copy_constructible_v<pull_reader>);
    static_assert(!std::is_move_constructible_v<pull_reader>);
    string_t source("{\"values\":[-9223372036854775808,9223372036854775807],\"raw\":1e9999}");
    pull_reader reader(source, string_t("probe.json"));
    source = string_t("replaced"); // Reader owns its input, not a borrowed view.
    reader.begin_object(); assert(reader.next_property().native_value());
    assert(reader.property_name().native_value() == "values");
    reader.begin_array(); assert(reader.next_element().native_value());
    assert(reader.read_integer().native_value() == std::numeric_limits<std::int64_t>::min());
    assert(reader.next_element().native_value());
    assert(reader.read_integer().native_value() == std::numeric_limits<std::int64_t>::max());
    assert(!reader.next_element().native_value()); assert(reader.next_property().native_value());
    assert(reader.read_number().native_value() == "1e9999");
    assert(!reader.next_property().native_value()); reader.finish_document();
    fails([&] { reader.kind(); }, "protocol", "$");

    pull_reader wrong(string_t("{\"codes\":[false]}"), string_t("probe.json"));
    wrong.begin_object(); wrong.next_property(); wrong.begin_array(); wrong.next_element();
    fails([&] { wrong.read_integer(); }, "type", "$[\"codes\"][0]");
    fails([&] { wrong.read_boolean(); }, "protocol", "$[\"codes\"][0]");
    pull_reader schema(string_t("{}"), string_t("probe.json"));
    schema.begin_object(); schema.next_property();
    fails([&] { schema.reject(string_t("missing field")); }, "schema", "$");
    pull_reader unfinished(string_t("[]"), string_t("probe.json"));
    unfinished.begin_array();
    fails([&] { unfinished.finish_document(); }, "protocol", "$");

    json_pull_reader checked;
    assert(checked.kind().has_error().native_value());
    assert(checked.start(string_t("true"), string_t("probe.json"), int_t<>(128)).has_value().native_value());
    assert(checked.read_string().has_error().native_value());
    assert(checked.read_boolean().has_error().native_value());
    assert(checked.start(string_t("false"), string_t("probe.json"), int_t<>(128)).has_error().native_value());
}
