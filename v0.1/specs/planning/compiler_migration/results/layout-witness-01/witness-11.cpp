struct t1 {
};
struct alignas(8) t2 { unsigned char bytes[16]; };
struct t3 {
t1 f0;
t2 f1;
t1 f2;
};
extern "C" const unsigned long long size = sizeof(t3);
extern "C" const unsigned long long alignment = alignof(t3);
extern "C" const unsigned long long field_0 = __builtin_offsetof(t3, f0);
extern "C" const unsigned long long field_1 = __builtin_offsetof(t3, f1);
extern "C" const unsigned long long field_2 = __builtin_offsetof(t3, f2);
