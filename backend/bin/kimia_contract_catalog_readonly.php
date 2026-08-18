<?php

declare(strict_types=1);

/**
 * READ-ONLY live Swagger semantic extractor.
 * Reads only var/kimia-verify/swagger_live.json captured by preflight.
 * Performs NO network calls and NO mutations.
 */

$root = dirname(__DIR__, 2);
$path = $root . '/var/kimia-verify/swagger_live.json';
if (!is_file($path)) {
    fwrite(STDERR, "CONTRACT_FAIL swagger_live.json missing\n");
    exit(2);
}

$raw = (string) file_get_contents($path);
$doc = json_decode($raw, true);
if (!is_array($doc)) {
    fwrite(STDERR, "CONTRACT_FAIL swagger_live.json invalid\n");
    exit(2);
}

$targets = [
    '/api/voucher/exchangegold',
    '/api/voucher/tradecash',
];

function cleanText(mixed $value): mixed
{
    if (!is_string($value)) {
        return $value;
    }
    return trim((string) preg_replace('/\s+/', ' ', strip_tags($value)));
}

/** @return mixed */
function resolveRef(array $doc, string $ref)
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

/** @return array<string,mixed>|null */
function requestSchema(array $doc, array $post): ?array
{
    $rb = $post['requestBody'] ?? null;
    if (is_array($rb)) {
        if (isset($rb['$ref']) && is_string($rb['$ref'])) {
            $resolved = resolveRef($doc, $rb['$ref']);
            if (is_array($resolved)) {
                $rb = $resolved;
            }
        }
        $content = $rb['content'] ?? null;
        if (is_array($content)) {
            $media = $content['application/json'] ?? $content['application/*+json'] ?? null;
            if (!is_array($media)) {
                foreach ($content as $candidate) {
                    if (is_array($candidate) && isset($candidate['schema'])) {
                        $media = $candidate;
                        break;
                    }
                }
            }
            if (is_array($media) && isset($media['schema']) && is_array($media['schema'])) {
                return $media['schema'];
            }
        }
    }

    foreach (($post['parameters'] ?? []) as $parameter) {
        if (!is_array($parameter)) {
            continue;
        }
        if (isset($parameter['$ref']) && is_string($parameter['$ref'])) {
            $resolved = resolveRef($doc, $parameter['$ref']);
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

/** @return array<string,mixed> */
function propertyMeta(array $doc, array $prop): array
{
    $resolved = $prop;
    $ref = isset($prop['$ref']) && is_string($prop['$ref']) ? $prop['$ref'] : null;
    if ($ref !== null) {
        $candidate = resolveRef($doc, $ref);
        if (is_array($candidate)) {
            $resolved = array_merge($candidate, $prop);
        }
    }

    $keys = [
        'type', 'format', 'nullable', 'readOnly', 'writeOnly',
        'minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum',
        'minLength', 'maxLength', 'pattern', 'multipleOf',
        'default', 'example', 'enum', 'title', 'description'
    ];
    $out = ['ref' => $ref];
    foreach ($keys as $key) {
        if (array_key_exists($key, $resolved)) {
            $value = $resolved[$key];
            if (in_array($key, ['description', 'title'], true)) {
                $value = cleanText($value);
            }
            $out[$key] = $value;
        }
    }

    foreach (['oneOf', 'anyOf', 'allOf'] as $combiner) {
        if (isset($resolved[$combiner]) && is_array($resolved[$combiner])) {
            $summary = [];
            foreach ($resolved[$combiner] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $summary[] = [
                    'ref' => $item['$ref'] ?? null,
                    'type' => $item['type'] ?? null,
                    'format' => $item['format'] ?? null,
                    'enum' => $item['enum'] ?? null,
                    'description' => isset($item['description']) ? cleanText($item['description']) : null,
                ];
            }
            $out[$combiner] = $summary;
        }
    }

    if (isset($resolved['items']) && is_array($resolved['items'])) {
        $out['items'] = propertyMeta($doc, $resolved['items']);
    }

    return $out;
}

/** @return array<string,mixed> */
function flattenSchema(array $doc, array $schema, array &$seen = []): array
{
    if (isset($schema['$ref']) && is_string($schema['$ref'])) {
        $ref = $schema['$ref'];
        if (isset($seen[$ref])) {
            return ['ref' => $ref, 'recursive' => true, 'required' => [], 'properties' => []];
        }
        $seen[$ref] = true;
        $resolved = resolveRef($doc, $ref);
        if (!is_array($resolved)) {
            return ['ref' => $ref, 'unresolved' => true, 'required' => [], 'properties' => []];
        }
        $flat = flattenSchema($doc, $resolved, $seen);
        $flat['ref'] = $ref;
        return $flat;
    }

    $required = [];
    $properties = [];
    $parts = [$schema];
    if (isset($schema['allOf']) && is_array($schema['allOf'])) {
        $parts = array_merge($parts, $schema['allOf']);
    }

    foreach ($parts as $part) {
        if (!is_array($part)) {
            continue;
        }
        if (isset($part['$ref']) && is_string($part['$ref'])) {
            $nestedSeen = $seen;
            $flat = flattenSchema($doc, $part, $nestedSeen);
            $required = array_merge($required, $flat['required'] ?? []);
            $properties = array_merge($properties, $flat['properties'] ?? []);
            continue;
        }
        foreach (($part['required'] ?? []) as $name) {
            if (is_string($name)) {
                $required[] = $name;
            }
        }
        foreach (($part['properties'] ?? []) as $name => $prop) {
            if (is_string($name) && is_array($prop)) {
                $properties[$name] = propertyMeta($doc, $prop);
            }
        }
    }

    $required = array_values(array_unique($required));
    sort($required);
    ksort($properties);
    return ['required' => $required, 'properties' => $properties];
}

foreach ($targets as $target) {
    $ops = $doc['paths'][$target] ?? null;
    $post = is_array($ops) ? ($ops['post'] ?? null) : null;
    if (!is_array($post)) {
        echo 'PREFLIGHT_CONTRACT_MISSING=' . $target . PHP_EOL;
        continue;
    }

    echo 'PREFLIGHT_CONTRACT_PATH=' . $target . PHP_EOL;
    echo 'PREFLIGHT_CONTRACT_OPERATION_ID=' . (string) ($post['operationId'] ?? '') . PHP_EOL;
    echo 'PREFLIGHT_CONTRACT_SUMMARY=' . (string) cleanText($post['summary'] ?? '') . PHP_EOL;
    echo 'PREFLIGHT_CONTRACT_DESCRIPTION=' . (string) cleanText($post['description'] ?? '') . PHP_EOL;

    $schema = requestSchema($doc, $post);
    if (!is_array($schema)) {
        echo 'PREFLIGHT_CONTRACT_SCHEMA=missing' . PHP_EOL;
        continue;
    }

    $ref = isset($schema['$ref']) && is_string($schema['$ref']) ? $schema['$ref'] : '';
    echo 'PREFLIGHT_CONTRACT_REQUEST_REF=' . $ref . PHP_EOL;
    $seen = [];
    $flat = flattenSchema($doc, $schema, $seen);
    echo 'PREFLIGHT_CONTRACT_REQUIRED=' . json_encode($flat['required'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    foreach (($flat['properties'] ?? []) as $name => $meta) {
        echo 'PREFLIGHT_CONTRACT_FIELD=' . $target . ' ' . $name . ' ' . json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . PHP_EOL;
    }
}

// Also surface any component/schema/property explicitly related to GoldUnit.
foreach (($doc['components']['schemas'] ?? []) as $schemaName => $schema) {
    if (!is_string($schemaName) || !is_array($schema)) {
        continue;
    }
    $nameHit = stripos($schemaName, 'goldunit') !== false;
    $propHits = [];
    foreach (($schema['properties'] ?? []) as $propName => $prop) {
        if (is_string($propName) && stripos($propName, 'goldunit') !== false && is_array($prop)) {
            $propHits[$propName] = propertyMeta($doc, $prop);
        }
    }
    if ($nameHit || $propHits !== []) {
        echo 'PREFLIGHT_GOLDUNIT_SCHEMA=' . $schemaName . ' ' . json_encode([
            'title' => isset($schema['title']) ? cleanText($schema['title']) : null,
            'description' => isset($schema['description']) ? cleanText($schema['description']) : null,
            'type' => $schema['type'] ?? null,
            'enum' => $schema['enum'] ?? null,
            'properties' => $propHits,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) . PHP_EOL;
    }
}

echo 'PREFLIGHT_CONTRACT_EXTRACT_OK=true' . PHP_EOL;
exit(0);
