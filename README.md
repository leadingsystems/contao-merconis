Leading Systems Contao Merconis bundle
=================================

Search term mapping (input augmentation)
----------------------------------------

This feature lets you map user input tokens to additional tokens that should also be searched (e.g., map "Viega" → "7122" so products with producer number 7122 rank higher). It also supports correcting misspellings (e.g., "Bosh" → "Bosch").

- Backend: Manage mappings in `MERCONIS - Shop → Search term mappings`.
- Table: `tl_ls_shop_search_term_mapping` with fields `sourceTerm`, `targetTerm`, `active`.
- Behavior: For each input token, if an active mapping exists, the mapped token is appended to the search query (original tokens are preserved). Duplicate tokens are avoided case-insensitively.
- Scope: Applied to the classic MySQL search by default. For Elasticsearch, disabled by default but can be enabled.

Configuration (via bundle config tree):

```
# config/packages/merconis.yaml
merconis:
  search:
    input_mapping:
      enabled: true
      apply_in_elasticsearch: false
```

Search modes (Quick vs Full)
----------------------------

Mappings can be scoped to a search mode:

- Mode values on mappings: `both` (default), `full`, `quick`.
- At runtime, only mappings with `mode ∈ {both, <active mode>}` are applied.
- Use Quick mode for stricter queries (e.g., map GTIN‑shaped tokens to `{field:gtin}{exact:1}{must:1}`); use Full for broader recall.

Usage:

- Choose the mode in your integration by calling `Adapter::setMappingMode('quick'|'full')` before setting the `fulltext` criterion.
- The default is `full` if not set.

Fulltext search modifiers (DirectMySQL)
---------------------------------------

You can annotate individual search tokens with modifiers using a `{name:value}` syntax. Modifiers can be appended to the token (e.g. `8711698701040{field:gtin}{exact:1}`) or provided as standalone tokens immediately after a term (e.g. `philips {field:producer}{must:1}`). Multiple modifiers are allowed and can be concatenated.

### Supported modifiers

- `field:<name>`
  - Targets a specific field. Recognized names and aliases:
    - Descriptive (fulltext): `title`, `keywords`, `shortdescription`, `description`
    - Code identifiers: `code`, `productcode`, `lsshopproductcode`, `mpn`, `gtin`
    - Producer: `producer`, `manufacturer`
  - If no `field:` is given, the term is treated as descriptive by default.

- `exact:<bool>`
  - Truthy values: `1,true,yes,on`; falsy: `0,false,no,off,''` (empty).
  - For `code/mpn/gtin` and `producer`, `exact:1` uses equality (`=`) instead of a partial match.
  - Has no effect on descriptive fields, which always use `MATCH() AGAINST ... IN BOOLEAN MODE`.

- `boost:<number>`
  - Multiplies the base relevance contributed by a term. Clamped to `0.1 … 100.0`.
  - Code/producer: adds per‑term score components derived from the base TL_CONFIG boosts (e.g. `ls_shop_dmysql_gtin_boost_exactTerm`) multiplied by the term’s boost. Baseline boosts remain intact for compatibility.
  - Descriptive: adds per‑term MATCH() contributions in addition to the existing field‑weighted scoring. Safeguards:
    - Only the first 4 boosted descriptive terms are considered.
    - Tokens shorter than 3 characters and some common EN/DE stopwords are ignored.
  - If no term uses `boost`, the SQL is unchanged (no extra MATCH/CASE parts).

- `must:<bool>` (aliases: `require`, `required`)
  - Marks a term as required (mandatory). Required groups are ANDed; optional groups remain ORed.
  - Behavior per target:
    - Descriptive: for each required term, it must match at least one descriptive column (AND over terms, OR across columns).
    - Code/MPN/GTIN: the term must match in its target; multiple code targets are ORed within the code group, and the whole group is required.
    - Producer: analogous to code.

### Clause combination (WHERE)

- Base constraints (published, pages, group restrictions, etc.) are ANDed.
- Required blocks (from `must:1`) are each ANDed with the query.
- Optional fulltext blocks are ORed together in one group.

In simplified form:

```
WHERE
  base_filters
  AND [required_descriptive]
  AND [required_code]
  AND [required_producer]
  AND ([optional_descriptive] OR [optional_code] OR [optional_producer])
```

### Scoring (SELECT)

- Descriptive (optional): unchanged field‑weighted `MATCH()` scoring; per‑term boosts add extra weighted `MATCH()` parts only when used.
- Descriptive (required): required terms contribute via the same `MATCH()` expressions and optional per‑term boost deltas.
- Code/Producer: baseline TL_CONFIG boosts remain; per‑term boosts add additional `CASE WHEN … THEN <boost> ELSE 0 END` components per term.

### Examples

- Exact GTIN scan with higher relevance:
  - `8711698701040{field:gtin}{exact:1}{boost:10}`
- Require producer match and prefer it:
  - `philips{field:producer}{must:1}{boost:3}`
- Descriptive bias on a phrase while keeping all else optional:
  - `"stainless steel"{boost:2}`
- Require both a descriptive term and a code prefix:
  - `alpha {must:1} 7122{field:code}`

### Performance notes

- If you do not use `boost`, the descriptive SQL is identical to the baseline.
- Per‑term boosts add small CPU overhead for code/producer (extra CASEs) and bounded overhead for descriptive (extra `MATCH()` per boosted term × column, capped at 4 terms and skipping very short/stop terms).

### Debugging

- Enable `ls_shop_debugSearch` and `ls_shop_debugSearchScoring` in the shop settings to write diagnostic output and per‑row scoring breakdowns to the logs in `var/logs`. Debug projections (`dbg_*` columns) are also added to the SQL when enabled.