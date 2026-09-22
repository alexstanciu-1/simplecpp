struct alignas(16) t1 { unsigned char bytes[32]; };
using t2 = t1[4];
extern "C" const unsigned long long size = sizeof(t2);
extern "C" const unsigned long long alignment = alignof(t2);
