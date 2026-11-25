; ModuleID = '/tmp/strpos-slice.xYg5J1/new/thin/cpp.o.4.opt.bc'
source_filename = "/tmp/strpos-slice.xYg5J1/new/emitted/units/f8fcf1d475f159f605c755ef0a3b1574d3096bb3cd6108037b460e0240654764.cpp"
target datalayout = "e-m:e-p270:32:32-p271:32:32-p272:64:64-i64:64-i128:128-f80:128-n8:16:32:64-S128"
target triple = "x86_64-pc-linux-gnu"

; Function Attrs: mustprogress uwtable
define dso_local noundef i64 @_ZN3App5ctwo_ERN4scpp6stringES2_(ptr noundef nonnull align 8 dereferenceable(24) %0, ptr nocapture noundef nonnull readonly align 8 dereferenceable(24) %1) local_unnamed_addr #0 {
  %3 = tail call noundef i64 @_ZN4scpp3str4findERKNS_6stringES3_(ptr noundef nonnull align 8 dereferenceable(24) %0, ptr noundef nonnull align 8 dereferenceable(24) %1)
  ret i64 %3
}

; Function Attrs: mustprogress uwtable
define dso_local noundef i64 @_ZN3App7cthree_ERN4scpp6stringES2_l(ptr noundef nonnull align 8 dereferenceable(24) %0, ptr noundef nonnull align 8 dereferenceable(24) %1, i64 noundef %2) local_unnamed_addr #0 {
  %4 = tail call noundef i64 @_ZN4scpp3str4findERKNS_6stringES3_lNS0_13search_policyE(ptr noundef nonnull align 8 dereferenceable(24) %0, ptr noundef nonnull align 8 dereferenceable(24) %1, i64 noundef %2, i32 noundef 2)
  ret i64 %4
}

declare dso_local noundef i64 @_ZN4scpp3str4findERKNS_6stringES3_lNS0_13search_policyE(ptr noundef nonnull align 8 dereferenceable(24), ptr noundef nonnull align 8 dereferenceable(24), i64 noundef, i32 noundef) local_unnamed_addr #1

; Function Attrs: mustprogress uwtable
declare dso_local noundef i64 @_ZN4scpp3str4findERKNS_6stringES3_(ptr noundef nonnull align 8 dereferenceable(24), ptr nocapture noundef nonnull readonly align 8 dereferenceable(24)) local_unnamed_addr #0

attributes #0 = { mustprogress uwtable "min-legal-vector-width"="0" "no-trapping-math"="true" "stack-protector-buffer-size"="8" "target-cpu"="x86-64" "target-features"="+cmov,+cx8,+fxsr,+mmx,+sse,+sse2,+x87" "tune-cpu"="generic" }
attributes #1 = { "no-trapping-math"="true" "stack-protector-buffer-size"="8" "target-cpu"="x86-64" "target-features"="+cmov,+cx8,+fxsr,+mmx,+sse,+sse2,+x87" "tune-cpu"="generic" }

!llvm.linker.options = !{}
!llvm.module.flags = !{!0, !1, !2, !3, !4}
!llvm.ident = !{!5, !5}

!0 = !{i32 1, !"wchar_size", i32 4}
!1 = !{i32 8, !"PIC Level", i32 2}
!2 = !{i32 7, !"PIE Level", i32 2}
!3 = !{i32 7, !"uwtable", i32 2}
!4 = !{i32 1, !"EnableSplitLTOUnit", i32 0}
!5 = !{!"Ubuntu clang version 18.1.3 (1ubuntu1)"}
