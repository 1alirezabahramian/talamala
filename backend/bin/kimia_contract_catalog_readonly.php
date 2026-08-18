<?php

declare(strict_types=1);

/**
 * Read-only structural extractor for the live Kimia Swagger already captured by preflight.
 * It performs NO network calls and NO mutations. It prints only schema structure needed
 * to prepare the Owner-authorized verification batch; examples/default values are omitted.
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
            if (!is_string($name) || !is_array($prop)) {
                continue;
            }
            $entry = [
                'type' => $prop['type'] ?? null,
                'format' => $prop['format'] ?? null,
                'nullable' => $prop['nullable'] ?? null,
                'ref' => $prop['$ref'] ?? null,
                'description' => isset($prop['description']) && is_string($prop['description'])
                    ? preg_replace('/\s+/', ' ', strip_tags($prop['description']))
                    : null,
            ];
            if (isset($prop['items']) && is_array($prop['items'])) {
                $entry['items_type'] = $prop['items']['type'] ?? null;
                $entry['items_ref'] = $prop['items']['$ref'] ?? null;
            }
            $properties[$name] = $entry;
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

    $schema = requestSchema($doc, $post);
    echo 'PREFLIGHT_CONTRACT_PATH=' . $target . PHP_EOL;
    echo 'PREFLIGHT_CONTRACT_OPERATION_ID=' . (string) ($post['operationId'] ?? '') . PHP_EOL;
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
        echo 'PREFLIGHT_CONTRACT_FIELD=' . $target . ' ' . $name . ' ' . json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}

echo 'PREFLIGHT_CONTRACT_EXTRACT_OK=true' . PHP_EOL;
exit(0);
