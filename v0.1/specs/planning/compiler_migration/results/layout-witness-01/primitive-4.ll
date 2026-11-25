target datalayout = "e-m:e-p270:32:32-p271:32:32-p272:64:64-i64:64-i128:128-f80:128-n8:16:32:64-S128"
target triple = "x86_64-unknown-linux-gnu"
@primitive_size_1 = constant i64 ptrtoint (ptr getelementptr (i1, ptr null, i32 1) to i64)
@primitive_alignment_1 = constant i64 ptrtoint (ptr getelementptr ({ i8, i1 }, ptr null, i32 0, i32 1) to i64)
