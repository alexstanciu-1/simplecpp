//! Source syntax selection, independent of project semantic/strictness policies.
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum Flavor {
    Cpp,
    Php,
    Js,
}

impl std::str::FromStr for Flavor {
    type Err = &'static str;
    fn from_str(value: &str) -> Result<Self, Self::Err> {
        match value {
            "cpp" => Ok(Self::Cpp),
            "php" => Ok(Self::Php),
            "js" => Ok(Self::Js),
            _ => Err("flavor must be cpp, php or js"),
        }
    }
}
