# Git Workflow and Release Procedure
Doc Status: normative
Status: Active
Purpose: define the authoritative repository procedure for git-flow branching, GitHub pull requests, and release-note-driven releases.

---

## 1. Scope

This document is the authoritative workflow procedure for:
- branch roles
- git-flow usage
- GitHub pull request targets
- release branch handling
- release notes handling
- AI behavior when required tooling is missing

If another supporting or planning document describes a different Git workflow, this document takes priority for repository operations.

---

## 2. Required Branch Model

The repository uses a git-flow-compatible model with these long-lived branches:

- `main`
  - release-ready history only
  - every published release tag must point to `main`
- `develop`
  - integration branch for ongoing feature work

Short-lived branches:

- `feature/<name>`
  - branch from `develop`
  - merge back into `develop`
- `release/<version>`
  - branch from `develop`
  - used only for release stabilization, release notes, and release-only fixes
  - merge into `main`
  - then merge back into `develop`
- `hotfix/<version>`
  - branch from `main`
  - merge into `main`
  - then merge back into `develop`

The `codex/` prefix may still be used for temporary AI-local working branches when explicitly requested, but it does not replace the authoritative git-flow branch roles above.

---

## 3. Pull Request Rules

GitHub pull requests are the required review and merge mechanism.

Expected PR targets:

- `feature/*` -> `develop`
- `release/*` -> `main`
- `hotfix/*` -> `main`

After a `release/*` or `hotfix/*` PR is merged to `main`, a follow-up PR or merge back into `develop` is required so the integration branch stays current.

Direct pushes to `main` are not part of the normal release path.

---

## 4. Release Notes Rule

Every release must have explicit release notes.

Authoritative release-note source:

- `CHANGELOG.md`

Release notes for a version must be prepared on the corresponding `release/<version>` or `hotfix/<version>` branch before the PR to `main` is merged.

Minimum release-note content:

- version
- release date
- user-visible additions
- fixes
- breaking changes, if any
- migration notes, if any

The GitHub Release body must be derived from the same release-note content that was committed in the repository.

GitHub-generated notes may be used as raw input, but they do not replace the checked-in release notes.

---

## 5. Standard Feature Procedure

1. Start from updated `develop`.
2. Create a `feature/<name>` branch.
3. Do the work on that branch.
4. Run the required validations and tests for the scope of change.
5. Open a GitHub pull request from `feature/<name>` into `develop`.
6. Merge only through the pull request flow.

Feature branches do not go directly to `main`.

---

## 6. Release Procedure

1. Ensure `develop` is in the intended release state.
2. Create `release/<version>` from `develop`.
3. On `release/<version>`:
   - stabilize only
   - update version markers if the repo uses them
   - update `CHANGELOG.md`
   - run the required validation/test suites
4. Open a GitHub pull request from `release/<version>` into `main`.
5. After approval and merge into `main`:
   - create the release tag on `main`
   - publish the GitHub Release using the committed release notes
6. Merge `release/<version>` back into `develop`.

Release branches are not for new feature development.

---

## 7. Hotfix Procedure

1. Create `hotfix/<version>` from `main`.
2. Apply the minimal required fix.
3. Update `CHANGELOG.md` for the patch release.
4. Open a GitHub pull request from `hotfix/<version>` into `main`.
5. After merge:
   - tag the patch release on `main`
   - publish the GitHub Release
6. Merge `hotfix/<version>` back into `develop`.

---

## 8. Tooling Requirements

The following tooling is required before the AI or a contributor executes this workflow:

- `git`
- GitHub pull-request tooling (`gh`) when PR creation or release publication is requested
- `git flow` tooling when the workflow step explicitly depends on git-flow commands rather than plain git equivalents
- on Windows:
  - WSL for normal project work
  - Git Bash for Windows-side Git operations when Windows-side execution is required

If a required tool is missing, broken, or not authenticated correctly for the requested operation, the AI must stop and ask the user for assistance instead of improvising or simulating completion.

Examples:

- do not fake `git flow` initialization if `git flow` is not installed
- do not claim a PR was created if `gh` is missing or unauthenticated
- do not proceed with a release publication if tagging or GitHub release creation cannot be completed correctly

---

## 9. AI Mandatory Behavior

When operating on this repository, the AI must follow these rules:

- treat this document as authoritative for Git workflow decisions
- do not invent alternate branch roles
- do not merge feature work directly into `main`
- do not skip release notes for a release or hotfix
- do not silently continue when required Git or GitHub tooling is unavailable
- explicitly tell the user what tool is missing and what assistance is needed

If a requested action conflicts with this procedure, the AI should pause and surface the conflict clearly before proceeding.

---

## 10. Relationship to Other Workflow Docs

- `specs/AI_WORKFLOW.md`
  - operational chat/session guidance
  - subordinate to this document for Git workflow and release handling
- `README.md`
  - onboarding and repo overview
  - should point here for authoritative workflow rules
- `PROJECT_CHATGPT_BOOTSTRAP.md`
  - environment/bootstrap guidance
  - should not override this procedure
