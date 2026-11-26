#include "packet.cpp"
#include <cassert>
#include <optional>
#include <stdexcept>
#include <string_view>

using App::scpp_records::Packet;

template<class Box>
void decode(const Box& box, std::optional<Packet>& out) {
    box.with_decoder(&out, [](void* slot, scpp::Decoder& reader) {
        static_cast<std::optional<Packet>*>(slot)->emplace(Packet::decode(reader));
    });
}

template<class Box>
void check(Box (*export_packet)(Packet)) {
    using scpp::int64;
    const scpp::string data{"data"}, id{"id"}, version{"version"};
    scpp::vector_int64 values;
    for (int64 n : {2, 4, 6}) values.append(n);
    auto source = Packet::scpp_escape_from_data(std::move(values), 3, 9);
    auto boxed = export_packet(source);
    std::optional<Packet> full;
    decode(boxed, full);
    assert(full->version == 9 && full->id == 3 && full->data.size() == 3 && full->data.at(1) == 4);

    auto envelope = Box::container();
    envelope.set(scpp::string{"packet"}, boxed);
    envelope.with_decoder(nullptr, [](void*, scpp::Decoder& reader) {
        const auto nested = App::scpp_records::Envelope::decode(reader);
        assert(nested.packet.version == 9 && nested.packet.data.at(1) == 4);
    });

    auto reversed = Box::container();
    reversed.set(id, boxed.read(id));
    reversed.set(data, boxed.read(data));
    std::optional<Packet> restored;
    decode(reversed, restored);
    assert(restored->id == 3 && restored->version == 7);
    auto list = reversed.read(data);
    list.set(int64{1}, Box::from_integer(int64{99}));
    reversed.set(data, list);
    source.data.clear();
    assert(reversed.read(data).read(int64{1}).get_integer() == 99);
    assert(restored->data.size() == 3 && restored->data.at(0) == 2 && restored->data.at(1) == 4 && restored->data.at(2) == 6);
    restored->data.set(int64{0}, 8);
    assert(reversed.read(data).read(int64{0}).get_integer() == 2);

    list.set(int64{1}, Box::from_string(scpp::string{"bad"}));
    reversed.set(data, list);
    const auto drops = Packet::drops;
    std::optional<Packet> failed;
    try {
        decode(reversed, failed);
        assert(false);
    } catch (const std::invalid_argument& error) {
        assert(std::string_view(error.what()) == "$.data[1]: expected integer");
    }
    assert(!failed && Packet::drops == drops);
    restored.reset();
    assert(Packet::drops == drops + 1);
}

int main() {
    check(App::exportMixed_);
    check(App::exportDynamic_);
}
