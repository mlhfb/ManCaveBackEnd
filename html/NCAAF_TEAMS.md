# NCAAF Team List (`ncaateams.list`)

File
- `html/ncaateams.list`

Line format
- Active line: `id,displayName`
- Inactive line: `#id,displayName`

Behavior
- Used by the `big10` custom feed filter.
- `html/ncaaf.php` returns this filtered feed.
- `html/test_ncaaf_filter.php` validates this filter path.

Filtering logic
- Primary match: ESPN numeric team ID (`team.id`).
- Fallback match: team names if IDs are unavailable.

Examples
- Active:
```text
275,Wisconsin Badgers
```

- Inactive:
```text
#254,Utah Utes
```

How to update
1. Find/confirm ESPN team IDs.
2. Add or edit rows in `html/ncaateams.list`.
3. Prefix with `#` to disable a team.
4. Add a matching `'NCAA Football:<id>' => '#RRGGBB'` entry to `$ledTeamColors` in
   `html/espn_scores_common.php` so the team has an LED-optimized color in JSON output.

Generation helpers
- `pwsh tools/generate_ncaateams.ps1`
- `php html/generate_ncaateams_list.php`
