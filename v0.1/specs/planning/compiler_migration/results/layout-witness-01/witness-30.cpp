using t1 = unsigned _BitInt(32);
struct t7 {
t1 f0;
};
struct alignas(8) t2 { unsigned char bytes[16]; };
struct t4 {
t2 f0;
t1 f1;
t1 f2;
};
struct t10 {
t7 f0;
t4 f1;
};
struct t9 {
t1 f0;
};
struct t11 {
t10 f0;
t9 f1;
};
struct t12 {
t11 f0;
t2 f1;
};
extern "C" const unsigned long long size = sizeof(t12);
extern "C" const unsigned long long alignment = alignof(t12);
extern "C" const unsigned long long field_0 = __builtin_offsetof(t12, f0);
extern "C" const unsigned long long field_1 = __builtin_offsetof(t12, f1);
extern "C" const unsigned long long primitive_size_1 = sizeof(t1);
extern "C" const unsigned long long primitive_alignment_1 = alignof(t1);
