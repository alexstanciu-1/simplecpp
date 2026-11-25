; ModuleID = '/tmp/strpos-slice.xYg5J1/new/thin/php.o.4.opt.bc'
source_filename = "/tmp/strpos-slice.xYg5J1/new/emitted/units/eabba9bf6336071c4bb41e0268425b7c38e9a1b1546882f2edd46e6d72df08a5.cpp"
target datalayout = "e-m:e-p270:32:32-p271:32:32-p272:64:64-i64:64-i128:128-f80:128-n8:16:32:64-S128"
target triple = "x86_64-pc-linux-gnu"

%"class.scpp::result_or_false_int64" = type { ptr }
%"class.std::__1::__variant_detail::__base.23" = type <{ %"union.std::__1::__variant_detail::__union.24", i32, [4 x i8] }>
%"union.std::__1::__variant_detail::__union.24" = type { %"union.std::__1::__variant_detail::__union.25" }
%"union.std::__1::__variant_detail::__union.25" = type { %struct.anon.0 }
%struct.anon.0 = type { i64 }
%"class.scpp::detail::carrier_storage.13" = type { %"class.std::__1::variant.14", i8, [7 x i8] }
%"class.std::__1::variant.14" = type { %"class.std::__1::__variant_detail::__impl.15" }
%"class.std::__1::__variant_detail::__impl.15" = type { %"class.std::__1::__variant_detail::__copy_assignment.base", [4 x i8] }
%"class.std::__1::__variant_detail::__copy_assignment.base" = type { %"class.std::__1::__variant_detail::__move_assignment.base" }
%"class.std::__1::__variant_detail::__move_assignment.base" = type { %"class.std::__1::__variant_detail::__assignment.base" }
%"class.std::__1::__variant_detail::__assignment.base" = type { %"class.std::__1::__variant_detail::__copy_constructor.base" }
%"class.std::__1::__variant_detail::__copy_constructor.base" = type { %"class.std::__1::__variant_detail::__move_constructor.base" }
%"class.std::__1::__variant_detail::__move_constructor.base" = type { %"class.std::__1::__variant_detail::__ctor.base" }
%"class.std::__1::__variant_detail::__ctor.base" = type { %"class.std::__1::__variant_detail::__dtor.base" }
%"class.std::__1::__variant_detail::__dtor.base" = type { %"class.std::__1::__variant_detail::__base.base" }
%"class.std::__1::__variant_detail::__base.base" = type <{ %"union.std::__1::__variant_detail::__union.24", i32 }>

; Function Attrs: mustprogress uwtable
define dso_local void @_ZN3App5ptwo_ERN4scpp6stringES2_(ptr dead_on_unwind noalias nocapture writable writeonly sret(%"class.scpp::result_or_false_int64") align 8 %0, ptr noundef nonnull align 8 dereferenceable(24) %1, ptr nocapture noundef nonnull readonly align 8 dereferenceable(24) %2) local_unnamed_addr #0 personality ptr @__gxx_personality_v0 {
  %4 = tail call noundef i64 @_ZN4scpp3str4findERKNS_6stringES3_(ptr noundef nonnull align 8 dereferenceable(24) %1, ptr noundef nonnull align 8 dereferenceable(24) %2), !noalias !6
  %5 = icmp eq i64 %4, -1
  br i1 %5, label %10, label %6

6:                                                ; preds = %3
  %7 = tail call noalias noundef nonnull dereferenceable(24) ptr @_Znwm(i64 noundef 24) #3
  store i64 %4, ptr %7, align 8, !tbaa !9
  %8 = getelementptr inbounds %"class.std::__1::__variant_detail::__base.23", ptr %7, i64 0, i32 1
  store i32 1, ptr %8, align 8, !tbaa !14
  %9 = getelementptr inbounds %"class.scpp::detail::carrier_storage.13", ptr %7, i64 0, i32 1
  store i8 0, ptr %9, align 8, !tbaa !17
  br label %10

10:                                               ; preds = %6, %3
  %storemerge = phi ptr [ %7, %6 ], [ null, %3 ]
  store ptr %storemerge, ptr %0, align 8, !tbaa !29
  ret void
}

; Function Attrs: mustprogress uwtable
define dso_local void @_ZN3App7pthree_ERN4scpp6stringES2_l(ptr dead_on_unwind noalias nocapture writable writeonly sret(%"class.scpp::result_or_false_int64") align 8 %0, ptr noundef nonnull align 8 dereferenceable(24) %1, ptr noundef nonnull align 8 dereferenceable(24) %2, i64 noundef %3) local_unnamed_addr #0 personality ptr @__gxx_personality_v0 {
  %5 = tail call noundef i64 @_ZN4scpp3str4findERKNS_6stringES3_lNS0_13search_policyE(ptr noundef nonnull align 8 dereferenceable(24) %1, ptr noundef nonnull align 8 dereferenceable(24) %2, i64 noundef %3, i32 noundef 0), !noalias !32
  %6 = icmp eq i64 %5, -1
  br i1 %6, label %11, label %7

7:                                                ; preds = %4
  %8 = tail call noalias noundef nonnull dereferenceable(24) ptr @_Znwm(i64 noundef 24) #3
  store i64 %5, ptr %8, align 8, !tbaa !9
  %9 = getelementptr inbounds %"class.std::__1::__variant_detail::__base.23", ptr %8, i64 0, i32 1
  store i32 1, ptr %9, align 8, !tbaa !14
  %10 = getelementptr inbounds %"class.scpp::detail::carrier_storage.13", ptr %8, i64 0, i32 1
  store i8 0, ptr %10, align 8, !tbaa !17
  br label %11

11:                                               ; preds = %7, %4
  %storemerge = phi ptr [ %8, %7 ], [ null, %4 ]
  store ptr %storemerge, ptr %0, align 8, !tbaa !29
  ret void
}

declare dso_local noundef i64 @_ZN4scpp3str4findERKNS_6stringES3_lNS0_13search_policyE(ptr noundef nonnull align 8 dereferenceable(24), ptr noundef nonnull align 8 dereferenceable(24), i64 noundef, i32 noundef) local_unnamed_addr #1

; Function Attrs: mustprogress uwtable
declare dso_local noundef i64 @_ZN4scpp3str4findERKNS_6stringES3_(ptr noundef nonnull align 8 dereferenceable(24), ptr nocapture noundef nonnull readonly align 8 dereferenceable(24)) local_unnamed_addr #0

declare i32 @__gxx_personality_v0(...)

; Function Attrs: nobuiltin allocsize(0)
declare noundef nonnull ptr @_Znwm(i64 noundef) local_unnamed_addr #2

attributes #0 = { mustprogress uwtable "min-legal-vector-width"="0" "no-trapping-math"="true" "stack-protector-buffer-size"="8" "target-cpu"="x86-64" "target-features"="+cmov,+cx8,+fxsr,+mmx,+sse,+sse2,+x87" "tune-cpu"="generic" }
attributes #1 = { "no-trapping-math"="true" "stack-protector-buffer-size"="8" "target-cpu"="x86-64" "target-features"="+cmov,+cx8,+fxsr,+mmx,+sse,+sse2,+x87" "tune-cpu"="generic" }
attributes #2 = { nobuiltin allocsize(0) "no-trapping-math"="true" "stack-protector-buffer-size"="8" "target-cpu"="x86-64" "target-features"="+cmov,+cx8,+fxsr,+mmx,+sse,+sse2,+x87" "tune-cpu"="generic" }
attributes #3 = { builtin allocsize(0) }

!llvm.linker.options = !{}
!llvm.module.flags = !{!0, !1, !2, !3, !4}
!llvm.ident = !{!5, !5}

!0 = !{i32 1, !"wchar_size", i32 4}
!1 = !{i32 8, !"PIC Level", i32 2}
!2 = !{i32 7, !"PIE Level", i32 2}
!3 = !{i32 7, !"uwtable", i32 2}
!4 = !{i32 1, !"EnableSplitLTOUnit", i32 0}
!5 = !{!"Ubuntu clang version 18.1.3 (1ubuntu1)"}
!6 = !{!7}
!7 = distinct !{!7, !8, !"_ZN4scpp3php6strposERKNS_6stringES3_: argument 0"}
!8 = distinct !{!8, !"_ZN4scpp3php6strposERKNS_6stringES3_"}
!9 = !{!10, !11, i64 0}
!10 = !{!"_ZTSNSt3__116__variant_detail5__altILm1ElEE", !11, i64 0}
!11 = !{!"long", !12, i64 0}
!12 = !{!"omnipotent char", !13, i64 0}
!13 = !{!"Simple C++ TBAA"}
!14 = !{!15, !16, i64 8}
!15 = !{!"_ZTSNSt3__116__variant_detail6__baseILNS0_6_TraitE0EJNS_9monostateElS3_EEE", !12, i64 0, !16, i64 8}
!16 = !{!"int", !12, i64 0}
!17 = !{!18, !28, i64 16}
!18 = !{!"_ZTSN4scpp6detail15carrier_storageIlNSt3__19monostateEEE", !19, i64 0, !28, i64 16}
!19 = !{!"_ZTSNSt3__17variantIJNS_9monostateElS1_EEE", !20, i64 0}
!20 = !{!"_ZTSNSt3__116__variant_detail6__implIJNS_9monostateElS2_EEE", !21, i64 0}
!21 = !{!"_ZTSNSt3__116__variant_detail17__copy_assignmentINS0_8__traitsIJNS_9monostateElS3_EEELNS0_6_TraitE0EEE", !22, i64 0}
!22 = !{!"_ZTSNSt3__116__variant_detail17__move_assignmentINS0_8__traitsIJNS_9monostateElS3_EEELNS0_6_TraitE0EEE", !23, i64 0}
!23 = !{!"_ZTSNSt3__116__variant_detail12__assignmentINS0_8__traitsIJNS_9monostateElS3_EEEEE", !24, i64 0}
!24 = !{!"_ZTSNSt3__116__variant_detail18__copy_constructorINS0_8__traitsIJNS_9monostateElS3_EEELNS0_6_TraitE0EEE", !25, i64 0}
!25 = !{!"_ZTSNSt3__116__variant_detail18__move_constructorINS0_8__traitsIJNS_9monostateElS3_EEELNS0_6_TraitE0EEE", !26, i64 0}
!26 = !{!"_ZTSNSt3__116__variant_detail6__ctorINS0_8__traitsIJNS_9monostateElS3_EEEEE", !27, i64 0}
!27 = !{!"_ZTSNSt3__116__variant_detail6__dtorINS0_8__traitsIJNS_9monostateElS3_EEELNS0_6_TraitE0EEE", !15, i64 0}
!28 = !{!"bool", !12, i64 0}
!29 = !{!30, !31, i64 0}
!30 = !{!"_ZTSN4scpp21result_or_false_int64E", !31, i64 0}
!31 = !{!"any pointer", !12, i64 0}
!32 = !{!33}
!33 = distinct !{!33, !34, !"_ZN4scpp3php6strposERKNS_6stringES3_l: argument 0"}
!34 = distinct !{!34, !"_ZN4scpp3php6strposERKNS_6stringES3_l"}
