#pragma once
#include "boxed_inspection.hpp"
#include "scpp/encoder.hpp"

namespace scpp::detail {
// Synchronous visit for sinks whose callbacks leave the source stable.
template<boxed_family Family, class Sink>
void encode_boxed(const boxed_value<Family>& value, Sink& out) {
    using element = boxed_value<Family>;
    using kind = typename element::kind;
    switch (value.type()) {
        case kind::null: out.write_null(); return;
        case kind::boolean: out.write(value.get_boolean()); return;
        case kind::integer: out.write(value.get_integer()); return;
        case kind::floating: out.write(value.get_floating()); return;
        case kind::string: out.string_bytes(value.get_string()); return;
        case kind::tuple: {
            const auto count = value.iteration_limit();
            out.tuple(count);
            std::int64_t pos = 0;
            element slot;
            while (value.iteration_next(pos, count, slot)) encode_boxed(slot, out);
            out.end_array();
            return;
        }
        case kind::container: break;
    }
    const auto& table = boxed_inspection::table(value);
    out.owned(&table, [](const void* raw, Encoder& encoder) {
        auto& sink = static_cast<Sink&>(encoder);
        const auto& entries = *static_cast<const packed_table<element>*>(raw);
        if constexpr (Family == boxed_family::mixed) sink.mapping(entries.size());
        else sink.record("dynamic", entries.size());
        entries.visit_entries([&](std::uint32_t key, const element& child) {
            if (packed_key::is_string(key)) sink.field(packed_string_pool::instance().resolve(key));
            else sink.integer_key(key);
            encode_boxed(child, sink);
        });
        sink.end_object();
    });
}
}
