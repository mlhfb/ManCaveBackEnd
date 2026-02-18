# TODO / Wishlist

This project is small and personal—here are next improvements and wishlist items.

- teams_admin UI: add `html/teams_admin.php` — web UI to toggle teams in `html/ncaateams.list`.
  - Auth: simple shared `ADMIN_TOKEN` env var checked on POST (recommended).
  - Server should validate `id,displayName` lines and write atomically, keeping a `.bak`.
  - Rationale: makes enabling/disabling teams easier for non-technical users.

- Persist canonical name→ESPN-ID mapping file (optional): allow `ncaateams.list` to accept names or IDs.

- CI: add strict checks to fail when filtered items == 0 (already implemented in test).

- Cleanup: expose an endpoint or script to list current ESPN team IDs (we added `tools/generate_ncaateams.ps1`).

Notes:
- Currently `html/ncaateams.list` contains numeric `id,displayName` lines; non-active teams are commented out with `#`.
- I noticed `127,Michigan State Spartans` is commented out in the list; keep or enable as needed.

If you'd like, I can implement the `teams_admin.php` page next (token-auth protected). Otherwise this TODO documents the idea.
