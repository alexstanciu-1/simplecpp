define i32 @target() { ret i32 42 }
define i32 @main() {
entry:
  %direct_call_result_1 = call i32 @target()
  ret i32 %direct_call_result_1
}

