use scpp_compiler::{
    diagnostic::Diagnostic,
    inputs::source::SourceMap,
    language::Flavor,
    parse::{ast::*, parse},
    tokenize::{scanner::scan, token::TokenKind as K},
};

fn parsed(text: &str, flavor: Flavor) -> Result<ParsedFile, Diagnostic> {
    let source = SourceMap::default()
        .add("example".into(), text.as_bytes().to_vec())
        .unwrap();
    parse(scan(source, 0, flavor)?)
}
fn expression(file: &ParsedFile, id: ExprId) -> String {
    let node = &file.expressions[id.0];
    match &node.kind {
        ExpressionKind::Integer => {
            String::from_utf8(file.tokens.source().slice(node.span).unwrap().to_vec()).unwrap()
        }
        ExpressionKind::Name(name) => name.text.clone(),
        ExpressionKind::Unary { operator, operand } => {
            format!("({operator:?} {})", expression(file, *operand))
        }
        ExpressionKind::Binary {
            operator,
            left,
            right,
        } => format!(
            "({operator:?} {} {})",
            expression(file, *left),
            expression(file, *right)
        ),
        ExpressionKind::Group(id) => format!("(Group {})", expression(file, *id)),
    }
}

#[test]
fn explicit_flavor_declarations_normalize_to_the_same_source_intent() {
    for (flavor, text) in [
        (
            Flavor::Cpp,
            "int count = 3 + 4 * 5; { int next = count - 1; }",
        ),
        (
            Flavor::Php,
            "$count int = 3 + 4 * 5; { $next int = $count - 1; }",
        ),
        (
            Flavor::Js,
            "let count: int = 3 + 4 * 5; { let next: int = count - 1; }",
        ),
    ] {
        let file = parsed(text, flavor).unwrap();
        assert_eq!(file.tokens.flavor(), flavor);
        assert_eq!(file.statements.len(), 2);
        let StatementKind::Local {
            name,
            annotation,
            initializer,
        } = &file.statements[0].kind
        else {
            panic!()
        };
        assert_eq!(name.text, "count");
        assert_eq!(annotation.as_ref().unwrap().name.text, "int");
        assert_eq!(expression(&file, *initializer), "(Add 3 (Multiply 4 5))");
        assert_eq!(file.statements[0].span.start(), 0);
        let spelling = file.tokens.source().slice(name.span).unwrap();
        assert_eq!(
            spelling,
            if flavor == Flavor::Php {
                &b"$count"[..]
            } else {
                &b"count"[..]
            }
        );
        let StatementKind::Block(body) = &file.statements[1].kind else {
            panic!()
        };
        let StatementKind::Local { initializer, .. } = body[0].kind else {
            panic!()
        };
        assert_eq!(expression(&file, initializer), "(Subtract count 1)");
    }
}

#[test]
fn inference_is_syntax_only_and_php_assignment_remains_unresolved() {
    for (flavor, text) in [
        (Flavor::Cpp, "auto count = 3; count = 4;"),
        (Flavor::Js, "let count = 3; count = 4;"),
    ] {
        let file = parsed(text, flavor).unwrap();
        assert!(matches!(
            file.statements[0].kind,
            StatementKind::Local {
                annotation: None,
                ..
            }
        ));
        assert!(matches!(
            file.statements[1].kind,
            StatementKind::Assignment {
                mode: AssignmentMode::ExistingBinding,
                ..
            }
        ));
    }
    let file = parsed("$count = 3; $count = 4;", Flavor::Php).unwrap();
    assert!(file.statements.iter().all(|s| matches!(
        s.kind,
        StatementKind::Assignment {
            mode: AssignmentMode::DeclareOrAssign,
            ..
        }
    )));
    // Unknown types and names, duplicate bindings, and huge integer values are analysis concerns.
    for text in [
        "Unknown x = missing;",
        "int x = 1; int x = 2;",
        "auto x = 9999999999999999999999999999999999999;",
    ] {
        assert!(parsed(text, Flavor::Cpp).is_ok());
    }
}

#[test]
fn type_angles_and_shift_expressions_share_immutable_tokens() {
    for (flavor, text) in [
        (Flavor::Cpp, "vector<vector<int>> value = input >> 2 + 1;"),
        (Flavor::Php, "$value vector<vector<int>> = $input >> 2 + 1;"),
        (
            Flavor::Js,
            "let value: vector<vector<int>> = input >> 2 + 1;",
        ),
    ] {
        let source = SourceMap::default()
            .add("nested".into(), text.as_bytes().to_vec())
            .unwrap();
        let tokens = scan(source, 0, flavor).unwrap();
        let retained = tokens.clone();
        let file = parse(tokens).unwrap();
        assert_eq!(retained.tokens(), file.tokens.tokens());
        assert_eq!(retained.tokens().as_ptr(), file.tokens.tokens().as_ptr());
        assert_eq!(
            retained
                .tokens()
                .iter()
                .filter(|t| t.kind() == K::GREATER)
                .count(),
            4
        );
        let StatementKind::Local {
            annotation,
            initializer,
            ..
        } = &file.statements[0].kind
        else {
            panic!()
        };
        let ty = annotation.as_ref().unwrap();
        assert_eq!(ty.arguments[0].arguments[0].name.text, "int");
        assert_eq!(
            expression(&file, *initializer),
            "(ShiftRight input (Add 2 1))"
        );
    }
    for (flavor, name) in [(Flavor::Cpp, "x"), (Flavor::Js, "x"), (Flavor::Php, "$x")] {
        for gap in [" ", "/**/", "\n"] {
            assert!(parsed(&format!("{name} >{gap}> 1;"), flavor).is_err());
        }
    }
}

#[test]
fn arithmetic_precedence_associativity_groups_and_unary_are_shared() {
    for flavor in [Flavor::Cpp, Flavor::Php, Flavor::Js] {
        let file = parsed("10 - 3 - 2 * -(4 + 1) << 2;", flavor).unwrap();
        let StatementKind::Expression(id) = file.statements[0].kind else {
            panic!()
        };
        assert_eq!(
            expression(&file, id),
            "(ShiftLeft (Subtract (Subtract 10 3) (Multiply 2 (Minus (Group (Add 4 1))))) 2)"
        );
    }
}

#[test]
fn flavor_lexical_rules_do_not_leak_between_files() {
    assert!(parsed("# comment\n$x = 1;", Flavor::Php).is_ok());
    for flavor in [Flavor::Cpp, Flavor::Js] {
        assert!(parsed("# comment\n", flavor).is_err());
    }
    assert!(parsed("let $count = 1;", Flavor::Js).is_ok());
    assert!(parsed("int $count = 1;", Flavor::Cpp).is_err());
    assert!(parsed("int echo = 1;", Flavor::Cpp).is_ok());
    assert!(parsed("let echo = 1;", Flavor::Js).is_ok());
    for (text, flavor) in [
        ("let x = 1;", Flavor::Php),
        ("$x int = 1;", Flavor::Js),
        ("auto x = 1;", Flavor::Js),
        ("let x: int = 1;", Flavor::Cpp),
    ] {
        assert!(parsed(text, flavor).is_err(), "{text}");
    }
}

#[test]
fn syntax_failures_have_locations_and_no_partial_ast() {
    for (text, flavor) in [
        ("int x = ;", Flavor::Cpp),
        ("let x = (1;", Flavor::Js),
        ("$x = 1", Flavor::Php),
        ("{ int x = 1;", Flavor::Cpp),
        ("int x = 1.5;", Flavor::Cpp),
        ("let x = 'string';", Flavor::Js),
        ("int x = f();", Flavor::Cpp),
        ("$x = 1 = 2;", Flavor::Php),
    ] {
        let error = parsed(text, flavor).unwrap_err();
        assert!(error.span().end() as usize <= text.len());
        assert!(error.to_string().contains("example:1:"));
    }
    let error = parsed("let x = 1;\nlet y =", Flavor::Js).unwrap_err();
    assert!(error.span().is_empty());
    assert!(error.to_string().contains("example:2:8:"));
    assert!(parsed("", Flavor::Cpp).unwrap().statements.is_empty());
}

#[test]
fn depth_is_bounded_but_long_flat_arithmetic_uses_an_arena() {
    for text in [
        format!("{}1{};", "(".repeat(150), ")".repeat(150)),
        format!("{}{}", "{".repeat(150), "}".repeat(150)),
        format!(
            "int x = {}1;",
            "-".repeat(150)
                .chars()
                .map(|c| format!("{c} "))
                .collect::<String>()
        ),
    ] {
        assert!(
            parsed(&text, Flavor::Cpp)
                .unwrap_err()
                .message()
                .contains("nesting")
        );
    }
    let file = parsed(&format!("{}1;", "1+".repeat(10_000)), Flavor::Cpp).unwrap();
    assert_eq!(file.expressions.len(), 20_001);
}
