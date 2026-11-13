# Agent instructions: Alt-txt-ai

<!-- BEGIN SHIP STANDARD -->
## Ship Standard (13 November 2026)

Follow the canonical [Ship Standard](https://github.com/PhDNetworks-Systems/quoteforge/blob/main/docs/standards/SHIP-STANDARD.md) and [Ship Engineer](https://github.com/PhDNetworks-Systems/quoteforge/blob/main/.claude/agents/ship-engineer.md) in every software repository. This section supersedes older instructions to stop at a draft PR or wait for Danny to review, merge or deploy ordinary engineering work. Preserve repository-specific checks, data rules, protected production release ceremonies and generated-file sources.

Done means built, verified, independently reviewed, merged to the default branch, deployed, checked live and recorded in Asana (and Linear once connected). Danny provides the outcome; agents own the engineering steps.

1. Read the repository instructions and check existing branches, PRs and tasks before starting. Continue existing work rather than duplicate it.
2. Write a plan and any interface contract before non-trivial work. Give parallel agents separate files.
3. Branch from the latest default branch. Open a normal PR into it; never strand a draft or stack new work on another PR branch.
4. Run the repository's executable checks locally on existing hardware; inspect UI screenshots at 390 px and 1440 px when applicable. Obtain fresh-context independent review and fix findings. Retain different-family review where required. Never replace required checks with a claim that they passed.
5. Merge after verification passes, using squash; the phd-vault repository retains merge commits to preserve vault/live. Do not ask Danny to manage GitHub. Confirm the documented deployment and live smoke check, then record the commit, commands, results and links.
6. If production breaks, use the documented rollback and verify recovery, then fix forward. Retain backup-before-deploy and release-specific WordPress approval controls. If blocked, finish independent work and record the exact missing check or access in the PR and Asana.

These are the Danny-only boundaries: client/customer messages and client-brand social posts (draft for him to send); spend beyond the agreed envelope; a credential, login, OAuth or 2FA only he can provide; DNS, domain, certificate or billing changes; deleting repositories, data or production resources. They are additive to the existing money/legal gates (including new commitments over £50, card/funds actions, invoice/tax issuance and contract signatures), protected live-client WordPress release approvals, App Store submission and certificate revocation. Ordinary branches, checks, reviews, merges and non-WordPress deployments are autonomous.

Never force-push or rewrite a default branch; delete repositories, unmerged branches with unique work or anything you did not create; skip, weaken or hide checks; commit secrets or real customer data; contact clients; name suppliers in client-facing text; change DNS; or re-enable GitHub Actions. Leave every PR you opened merged or with a written, evidenced blocker.

Claude Code only: use classifier-guarded auto mode rather than bypass permissions. Codex routine engineering uses the workspace-write sandbox with network access for git and gh; never turn off security protections to ship.

<!-- END SHIP STANDARD -->

<!-- BEGIN REPOSITORY RULES -->

<!-- END REPOSITORY RULES -->

<!-- BEGIN REPOSITORY FACTS -->
## Repository facts and verification

Source: GitHub default-branch inventory for `PhDNetworks/Alt-txt-ai` on 13 November 2026.

- Default branch: `main`.
- Keep repository-specific tests, private-data restrictions and release controls in force.
- Repository description: Ai SEO Optimised Alt text Wordpress Plugin

Release/live/runbook-review script candidates declared in `worker/package.json` (identified from names, commands and transitive script calls). Run only through their documented path after verification; preserve backup, credential, data, WordPress and owner gates:

- Script `deploy`: `wrangler deploy`.

Configuration paths observed (inspect the owning runbook to distinguish current production, fixtures and legacy configuration):

- `worker/wrangler.toml`.

If a deploy/publish command writes to a live client WordPress/Krystal site, stop at the protected exact-release approval and fresh-backup ceremony. If it publishes client-brand social content or communicates with customers, prepare a draft for Danny to send. Neither a script name nor this inventory grants those permissions.

For a documentation-only change, run Markdown/link sanity and `git diff --check`, plus any executable repository check that reads these files. For code changes, run the component’s full required verification from its instructions and workflows locally; keep GitHub Actions retired. Missing verification is a recorded failure, never a pass.

Deploy through the existing verified production path after merge. Where a platform integration is absent, the Ship Engineer owns the documented deploy command and live smoke check. Do not invent a deployment command or production URL. Establish an unknown path from the owning configuration, record an exact access blocker if required, and complete independent work.

Rollback a released change by reverting its merge through a new verified PR, then confirm the platform redeploy and live recovery. Preserve unique branches. For databases and WordPress, retain the existing fresh-backup, additive-migration and protected release-approval rules.

Secrets belong in the existing secret store/environment, never Git. Discover required variable names from the owning config without printing their values. Never commit customer data, private inputs or generated customer artefacts.
<!-- END REPOSITORY FACTS -->
