using t1 = unsigned _BitInt(32);
struct alignas(16) t2 { unsigned char bytes[32]; };
struct t3 {
t1 f0;
t2 f1;
};
using t4 = t3[3];
struct t5 {
t4 f0;
t3 f1;
t1 f2;
};
extern "C" const unsigned long long size = sizeof(t5);
extern "C" const unsigned long long alignment = alignof(t5);
extern "C" const unsigned long long field_0 = __builtin_offsetof(t5, f0);
extern "C" const unsigned long long field_1 = __builtin_offsetof(t5, f1);
extern "C" const unsigned long long field_2 = __builtin_offsetof(t5, f2);
extern "C" const unsigned long long primitive_size_1 = sizeof(t1);
extern "C" const unsigned long long primitive_alignment_1 = alignof(t1);
