<?php

declare(strict_types=1);

/**
 * READ-ONLY Create Customer Ground Truth extractor.
 * Reads only the Swagger snapshot already captured by kimia_verify_runner.php catalog.
 * Performs NO network calls and NO mutations.
 */

$root = dirname(__DIR__, 2);
$swaggerPath = $root . '/var/kimia-verify/swagger_live.json';
$target = '/api/account';

if (!is_file($swaggerPath)) {
    fwrite(STDERR, "CREATE_CUSTOMER_GT_FAIL=swagger_live_missing\n");
    exit(2);
}

$doc = json_decode((string) file_get_contents($swaggerPath), true);
if (!is_array($doc)) {
    fwrite(STDERR, "CREATE_CUSTOMER_GT_FAIL=swagger_live_invalid\n");
    exit(2);
}

function gtResolveRef(array $doc, string $ref): mixed
{
    if (!str_starts_with($ref, '#/')) {
        return null;
    }
    $node = $doc;
    foreach (explode('/', substr($ref, 2)) as $part) {
        $part = str_replace(['~1', '~0'], ['/', '~'], $part);
        if (!is_array($node) || !array_key_exists($part, $node)) {
            return null;
        }
        $node = $node[$part];
    }
    return $node;
}

function gtClean(mixed $value): mixed
{
    return is_string($value)
        ? trim((string) preg_replace('/\s+/', ' ', strip_tags($value)))
        : $value;
}

function gtSchemaSummary(array $doc, array $schema, array &$seen = []): array
{
    if (isset($schema['$ref']) && is_string($schema['$ref'])) {
        $ref = $schema['$ref'];
        if (isset($seen[$ref])) {
            return ['ref' => $ref, 'recursive' => true];
        }
        $seen[$ref] = true;
        $resolved = gtResolveRef($doc, $ref);
        if (!is_array($resolved)) {
            return ['ref' => $ref, 'unresolved' => true];
        }
        $out = gtSchemaSummary($doc, $resolved, $seen);
        $out['ref'] = $ref;
        return $out;
    }

    $out = [];
    foreach (['type','format','nullable','title','description','default','enum','example','minimum','maximum','minLength','maxLength','pattern'] as $key) {
        if (array_key_exists($key, $schema)) {
            $out[$key] = in_array($key, ['title','description'], true) ? gtClean($schema[$key]) : $schema[$key];
        }
    }

    if (isset($schema['required']) && is_array($schema['required'])) {
        $out['required'] = array_values(array_filter($schema['required'], 'is_string'));
        sort($out['required']);
    }

    if (isset($schema['properties']) && is_array($schema['properties'])) {
        $properties = [];
        foreach ($schema['properties'] as $name => $property) {
            if (!is_string($name) || !is_array($property)) {
                continue;
            }
            $nestedSeen = $seen;
            $properties[$name] = gtSchemaSummary($doc, $property, $nestedSeen);
        }
        ksort($properties);
        $out['properties'] = $properties;
    }

    if (isset($schema['items']) && is_array($schema['items'])) {
        $nestedSeen = $seen;
        $out['items'] = gtSchemaSummary($doc, $schema['items'], $nestedSeen);
    }

    foreach (['allOf','oneOf','anyOf'] as $combiner) {
        if (!isset($schema[$combiner]) || !is_array($schema[$combiner])) {
            continue;
        }
        $items = [];
        foreach ($schema[$combiner] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $nestedSeen = $seen;
            $items[] = gtSchemaSummary($doc, $item, $nestedSeen);
        }
        $out[$combiner] = $items;
    }

    return $out;
}

function gtRequestSchema(array $doc, array $operation): ?array
{
    $body = $operation['requestBody'] ?? null;
    if (is_array($body) && isset($body['$ref']) && is_string($body['$ref'])) {
        $resolved = gtResolveRef($doc, $body['$ref']);
        if (is_array($resolved)) {
            $body = $resolved;
        }
    }
    if (is_array($body)) {
        foreach (($body['content'] ?? []) as $media) {
            if (is_array($media) && isset($media['schema']) && is_array($media['schema'])) {
                return $media['schema'];
            }
        }
    }

    foreach (($operation['parameters'] ?? []) as $parameter) {
        if (!is_array($parameter)) {
            continue;
        }
        if (isset($parameter['$ref']) && is_string($parameter['$ref'])) {
            $resolved = gtResolveRef($doc, $parameter['$ref']);
            if (is_array($resolved)) {
                $parameter = $resolved;
            }
        }
        if (($parameter['in'] ?? null) === 'body' && isset($parameter['schema']) && is_array($parameter['schema'])) {
            return $parameter['schema'];
        }
    }
    return null;
}

$pathItem = $doc['paths'][$target] ?? null;
$post = is_array($pathItem) ? ($pathItem['post'] ?? null) : null;
if (!is_array($post)) {
    echo "CREATE_CUSTOMER_GT_PATH_MISSING={$target}\n";
    exit(3);
}

echo "CREATE_CUSTOMER_GT_METHOD=POST\n";
echo "CREATE_CUSTOMER_GT_PATH={$target}\n";
echo 'CREATE_CUSTOMER_GT_OPERATION_ID=' . (string) ($post['operationId'] ?? '') . PHP_EOL;
echo 'CREATE_CUSTOMER_GT_SUMMARY=' . (string) gtClean($post['summary'] ?? '') . PHP_EOL;
echo 'CREATE_CUSTOMER_GT_DESCRIPTION=' . (string) gtClean($post['description'] ?? '') . PHP_EOL;

$requestSchema = gtRequestSchema($doc, $post);
if (!is_array($requestSchema)) {
    echo "CREATE_CUSTOMER_GT_REQUEST_SCHEMA=missing\n";
} else {
    $requestRef = isset($requestSchema['$ref']) && is_string($requestSchema['$ref']) ? $requestSchema['$ref'] : '';
    echo 'CREATE_CUSTOMER_GT_REQUEST_REF=' . $requestRef . PHP_EOL;
    $seen = [];
    echo 'CREATE_CUSTOMER_GT_REQUEST=' . json_encode(gtSchemaSummary($doc, $requestSchema, $seen), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . PHP_EOL;
}

$responses = $post['responses'] ?? [];
if (!is_array($responses)) {
    $responses = [];
}
ksort($responses);
foreach ($responses as $status => $response) {
    if (!is_string((string) $status) || !is_array($response)) {
        continue;
    }
    if (isset($response['$ref']) && is_string($response['$ref'])) {
        $resolved = gtResolveRef($doc, $response['$ref']);
        if (is_array($resolved)) {
            $response = $resolved;
        }
    }
    $summary = [
        'description' => gtClean($response['description'] ?? null),
        'schemas' => [],
    ];
    foreach (($response['content'] ?? []) as $mediaType => $media) {
        if (!is_string($mediaType) || !is_array($media) || !isset($media['schema']) || !is_array($media['schema'])) {
            continue;
        }
        $seen = [];
        $summary['schemas'][$mediaType] = gtSchemaSummary($doc, $media['schema'], $seen);
    }
    echo 'CREATE_CUSTOMER_GT_RESPONSE=' . $status . ' ' . json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . PHP_EOL;
}

echo "CREATE_CUSTOMER_GT_EXTRACT_OK=true\n";
exit(0);
