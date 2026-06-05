"""Source modules — one per data source, added on site.

Each module fetches + parses ONE source and yields IngestRecord objects.
Only fetch from allow-listed public domains (SSRF guard, security.md §4).
"""
