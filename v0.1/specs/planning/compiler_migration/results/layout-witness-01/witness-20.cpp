using t1 = unsigned _BitInt(32);
struct t3 {
t1 f0;
t1 f1;
t1 f2;
};
struct t5 {
t1 f0;
t3 f1;
};
struct t8 {
t5 f0;
};
struct alignas(8) t2 { unsigned char bytes[16]; };
struct t11 {
t8 f0;
t2 f1;
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
