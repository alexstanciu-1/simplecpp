struct alignas(8) t2 { unsigned char bytes[16]; };
struct t3 {
t2 f0;
t2 f1;
t2 f2;
};
struct t4 {
t3 f0;
};
struct t5 {
t4 f0;
};
struct t11 {
t5 f0;
t4 f1;
t2 f2;
};
struct t12 {
t11 f0;
t2 f1;
};
extern "C" const unsigned long long size = sizeof(t12);
extern "C" const unsigned long long alignment = alignof(t12);
extern "C" const unsigned long long field_0 = __builtin_offsetof(t12, f0);
extern "C" const unsigned long long field_1 = __builtin_offsetof(t12, f1);
