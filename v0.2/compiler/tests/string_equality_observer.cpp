// Length-aware independent byte oracle, including invalid UTF-8 runtime inputs.
#include <initializer_list>
struct bytes { const char* data; __INT64_TYPE__ size; };
int main() {
    for (bytes a : {bytes{"",0}, bytes{"a",1}, bytes{"a\0",2}, bytes{"a\0b",3}, bytes{"a\0c",3}, bytes{"\xff",1}}) {
        for (bytes b : {bytes{"",0}, bytes{"a",1}, bytes{"a\0",2}, bytes{"a\0b",3}, bytes{"a\0c",3}, bytes{"\xff",1}}) {
            bool expected = a.size == b.size;
            if (expected) for (__INT64_TYPE__ i = 0; i < a.size; ++i) if (a.data[i] != b.data[i]) expected = false;
            scpp::string left{a.data, a.size}, right{b.data, b.size};
            if (@equal@(left,right) != expected || @not_equal@(left,right) == expected) return 90;
        }
    }
    return 8;
}
