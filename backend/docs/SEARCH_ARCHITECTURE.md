# Search architecture
Universal search uses direct, bound Eloquent queries over ten public modules. This avoids a new search dependency at current scale and remains replaceable behind `UniversalSearchService`. Results normalize type, title, description, URL, publication time and score. Exact title and prefix matches rank above body matches. A global merge supplies pagination and type facets.
