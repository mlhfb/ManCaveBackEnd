# NCAAF team list (`ncaateams.list`) format and usage

- File: `html/ncaateams.list`
- Each non-comment line is `id,displayName`
- Lines starting with `#` are inactive
- Filtering is driven by ESPN team ID (`team.id`), with a name-match fallback only when IDs are not provided

Example active line:
```text
275,Wisconsin Badgers
```

Example inactive line:
```text
#254,Utah Utes
```

Where this list is used
- `sport=big10` in `html/espn_scores_rss.php`
- `html/ncaaf.php` (legacy endpoint aliasing the same filtered feed)

How to update teams
1. Find the ESPN team id (`tools/generate_ncaateams.ps1` can help).
2. Add `id,displayName` to `html/ncaateams.list`.
3. Prefix with `#` to disable a team.
