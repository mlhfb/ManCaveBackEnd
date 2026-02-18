# NCAAF team list (`ncaateams.list`) — format and usage

- File: `html/ncaateams.list`
- Each non-comment line must be: `id,displayName` (comma-separated)
- The code filters strictly by ESPN team ID (the numeric `team.id` from ESPN JSON).
- Lines beginning with `#` are inactive and ignored — remove the leading `#` to activate a team.
- Keeping the `displayName` is for human readability; the running code uses only the `id`.

Example (active):

275,Wisconsin Badgers

Example (inactive):

#254,Utah Utes

To add or enable a team:
- Find the ESPN team id (use `tools/generate_ncaateams.ps1` to auto-generate the list),
- Add the line `id,displayName` to `html/ncaateams.list` (no leading `#`).

To disable a team, prefix its line with `#`.

Note: `ncaaf.php` derives allowed IDs by reading `ncaateams.list` and matching only on numeric IDs. This avoids false positives caused by display-name variations.
