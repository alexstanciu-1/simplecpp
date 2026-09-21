; v2 PHS backend-emission LLVM text sink
; backend_value_stack_status=ok
; backend_local_slot_status=ok
source_filename = "compiler/reset-runner-entry"
target triple = "x86_64-pc-linux-gnu"

define i32 @scpp_run() {
entry:
  %local_1 = alloca i32, align 4
  store i32 42, ptr %local_1, align 4
  %local_load_3 = load i32, ptr %local_1, align 4
  ret i32 %local_load_3
}

define i32 @main() {
entry:
  %run_value = call i32 @scpp_run()
  ret i32 %run_value
}
