// Compiled beside the emitted scanner. Exercise both representations and both scan exits.
#include SCPP_SCANNER_HEADER
#include <string>
#include <cassert>
#include <stdexcept>
#include <string_view>
#include <type_traits>
#include <utility>

using view = decltype(std::declval<const scpp::string&>().checked_bytes());
static_assert(!std::is_copy_constructible_v<view> && !std::is_move_constructible_v<view>);
template<class T> concept can_view = requires(T&& text) { std::forward<T>(text).checked_bytes(); };
static_assert(can_view<const scpp::string&> && !can_view<scpp::string>);

int main() {
    for (const auto& original : {std::string{}, std::string("a\0\xff", 3),
            std::string(22, 'a'), std::string(23, 'a'), std::string(512, 'b')}) {
        scpp::string input(original.data(), static_cast<scpp::int64>(original.size()));
        const auto scan = App::scan_(input, 0);
        assert(std::string_view(input.data(), input.size()) == original);
        input = scpp::string("replaced");
        const auto bytes = scan.source.checked_bytes();
        assert(bytes.size() == static_cast<scpp::int64>(original.size()));
        for (scpp::int64 i = 0; i < bytes.size(); ++i)
            assert(bytes.read(i) == static_cast<unsigned char>(original[i]));
        for (auto index : {scpp::int64{-1}, bytes.size()}) {
            try { (void)bytes.read(index); assert(false); }
            catch (const std::out_of_range& error) {
                assert(std::string_view(error.what()) == "sequence_index_out_of_range");
            }
        }
        if (original.find('\0') != std::string::npos) assert(scan.error == 1 && scan.tokens.size() == 0);
        else assert(scan.error == -1 && scan.tokens.size() == (original.empty() ? 0 : 1));
    }
}
