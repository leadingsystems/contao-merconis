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