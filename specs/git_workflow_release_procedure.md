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
- release-time agent skill maintenance
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

Standard tag naming:

- `v<version>`

Example:

- `v0.1.1`

The release tag must reference the release-ready commit on `main` that carries the final checked-in release notes for that version.

---

## 5. Agent Skill Release Hygiene

Before a release is published, the release branch must review repo-local Agent Skills under:

- `.agents/skills/`

If a release changes user-facing language behavior, project workflow, diagnostics, library surfaces, examples, module usage, dependency composition, or other guidance an AI agent would rely on, the relevant skill files must be updated before the release PR is merged.

This check is mandatory even when no skill changes are needed. In that case, the release review should explicitly conclude that `.agents/skills/*` remains current.

Agent Skills are not the highest semantic authority. They are operational guidance derived from specs, docs, examples, and current tooling. Do not change a skill to invent semantics; update the owning spec/doc/code first, then update the skill to reflect it.

---

## 6. Standard Feature Procedure

1. Start from updated `develop`.
2. Create a `feature/<name>` branch.
3. Do the work on that branch.
4. Run the required validations and tests for the scope of change.
5. Open a GitHub pull request from `feature/<name>` into `develop`.
6. Merge only through the pull request flow.

Feature branches do not go directly to `main`.

---

## 7. Release Procedure

1. Ensure `develop` is in the intended release state.
2. Create `release/<version>` from `develop`.
3. On `release/<version>`:
   - stabilize only
   - update version markers if the repo uses them
   - update `CHANGELOG.md`
   - review `.agents/skills/*` and update affected Agent Skills before release, or record that no skill update is needed
   - run the required validation/test suites
4. Open a GitHub pull request from `release/<version>` into `main`.
5. After approval and merge into `main`:
   - fast-forward local `main`
   - confirm the checked-in release notes on `main`
   - create tag `v<version>` on the release-ready `main` commit
   - push the tag
   - publish the GitHub Release using the same checked-in notes from `CHANGELOG.md`
6. Synchronize the release result back into `develop`.
   - If `develop` already contains the release content and only release-note bookkeeping changed on `main`, merge or fast-forward that note update back into `develop`.
   - Otherwise merge the release result back into `develop` normally.
7. Delete `release/<version>` locally and remotely after the release is safely published.

Release branches are not for new feature development.

---

## 8. Hotfix Procedure

1. Create `hotfix/<version>` from `main`.
2. Apply the minimal required fix.
3. Update `CHANGELOG.md` for the patch release.
4. Review `.agents/skills/*` and update affected Agent Skills when the hotfix changes user-facing behavior or agent guidance, or record that no skill update is needed.
5. Open a GitHub pull request from `hotfix/<version>` into `main`.
6. After merge:
   - fast-forward local `main`
   - create tag `v<version>` on the release-ready `main` commit
   - push the tag
   - publish the GitHub Release from the checked-in release notes
7. Merge the hotfix result back into `develop`.
8. Delete `hotfix/<version>` locally and remotely after publication.

---

## 9. Standard Release Output Checklist

Every published release should leave the repository in this state:

- `main` contains the merged release PR
- `CHANGELOG.md` contains the release notes for that version
- `.agents/skills/*` has been reviewed and any release-relevant skill updates are included
- tag `v<version>` exists on GitHub
- a GitHub Release exists for that tag
- `develop` is synchronized with the release-note/bookkeeping outcome
- the temporary `release/*` or `hotfix/*` branch has been cleaned up

---

## 10. Tooling Requirements

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

## 11. AI Mandatory Behavior

When operating on this repository, the AI must follow these rules:

- treat this document as authoritative for Git workflow decisions
- do not invent alternate branch roles
- do not merge feature work directly into `main`
- do not skip release notes for a release or hotfix
- do not skip the `.agents/skills/*` release-hygiene check
- do not treat a merged release PR as a complete published release until the tag and GitHub Release also exist
- do not silently continue when required Git or GitHub tooling is unavailable
- explicitly tell the user what tool is missing and what assistance is needed

If a requested action conflicts with this procedure, the AI should pause and surface the conflict clearly before proceeding.

---

## 12. Relationship to Other Workflow Docs

- `specs/AI_WORKFLOW.md`
  - operational chat/session guidance
  - subordinate to this document for Git workflow and release handling
- `README.md`
  - onboarding and repo overview
  - should point here for authoritative workflow rules
- `PROJECT_CHATGPT_BOOTSTRAP.md`
  - environment/bootstrap guidance
  - should not override this procedure
