<?php

declare(strict_types=1);

// pgvector configuration (backend.md §12). Embeddings are produced by the Python
// scraper (scraping.md §8) and stored here; the dimension MUST match the model the
// scraper uses. Default 384 = a CPU-friendly multilingual model (e.g. MiniLM-L12-v2).
// Change the dimension only alongside the scraper's embedding model.
return [
    'dimensions' => (int) env('EMBEDDING_DIMENSIONS', 384),

    // Index method for similarity search: 'hnsw' (no training, great recall) or
    // 'ivfflat'. HNSW is the default; both ship with pgvector >= 0.5.
    'index' => env('VECTOR_INDEX', 'hnsw'),

    // Distance operator class used by the indexes + queries.
    'ops' => env('VECTOR_OPS', 'vector_cosine_ops'),
];
