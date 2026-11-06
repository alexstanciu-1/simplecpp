// Length-aware independent byte oracle, including invalid UTF-8 runtime inputs.
#include <initializer_list>
struct bytes { const char* data; __INT64_TYPE__ size; };
int main() {
    for (bytes a : {bytes{"",0}, bytes{"a",1}, bytes{"a\0",2}, bytes{"a\0b",3}, bytes{"a\0c",3}, bytes{"\xff",1}}) {
        for (bytes b : {bytes{"",0}, bytes{"a",1}, bytes{"a\0",2}, bytes{"a\0b",3}, bytes{"a\0c",3}, bytes{"\xff",1}}) {
            int order = 0;
            for (__INT64_TYPE__ i = 0; i < a.size && i < b.size; ++i) {
                const unsigned char x = a.data[i], y = b.data[i];
                if (x != y) { order = x < y ? -1 : 1; break; }
            }
            if (order == 0 && a.size != b.size) order = a.size < b.size ? -1 : 1;
            scpp::string left{a.data, a.size}, right{b.data, b.size};
            if (@compare@(left,right) != order) return 92;
            if (@equal@(left,right) != (order == 0) || @not_equal@(left,right) != (order != 0)
                || @less@(left,right) != (order < 0) || @less_equal@(left,right) != (order <= 0)
                || @greater@(left,right) != (order > 0) || @greater_equal@(left,right) != (order >= 0)) return 90;
        }
    }
    for (int a = 0; a < 256; ++a) for (int b = 0; b < 256; ++b) {
        const char x = static_cast<char>(a), y = static_cast<char>(b);
        scpp::string left{&x, 1}, right{&y, 1};
        if (@less@(left,right) != (a < b) || @greater@(left,right) != (a > b)) return 91;
    }
    return 8;
}
