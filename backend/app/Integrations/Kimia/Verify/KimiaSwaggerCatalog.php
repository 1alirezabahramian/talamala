<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia\Verify;

/**
 * Extracts write-related paths/schemas from live OpenAPI body.
 * Does NOT hard-code Action codes 32/64/2/4 — those remain historical reference only.
 */
final class KimiaSwaggerCatalog
{
    /**
     * @return array{
     *   version:string,
     *   sha256:string,
     *   paths:array<string, list<string>>,
     *   post_paths:list<string>,
     *   schemas:list<string>,
     *   action_enums:array<string, mixed>,
     *   historical_action_reference:array<string, string>
     * }
     */
    public static function analyze(string $swaggerBody): array
    {
        $sha = hash('sha256', $swaggerBody);
        $json = json_decode($swaggerBody, true);
        $version = is_array($json) ? (string) ($json['info']['version'] ?? 'unknown') : 'non_json';

        $paths = [];
        $postPaths = [];
        if (is_array($json) && isset($json['paths']) && is_array($json['paths'])) {
            foreach ($json['paths'] as $path => $ops) {
                if (is_array($ops) && isset($ops['post'])) {
                    $postPaths[] = (string) $path;
                }
                $p = (string) $path;
                if (!preg_match('/exchange|trade|transfer|voucher|account|cash|adjust|customer|person/i', $p)) {
                    continue;
                }
                $methods = [];
                if (is_array($ops)) {
                    foreach ($ops as $m => $_) {
                        if (is_string($m) && preg_match('/^(get|post|put|patch|delete)$/i', $m)) {
                            $methods[] = strtoupper($m);
                        }
                    }
                }
                $paths[$p] = $methods;
            }
        }

        $schemas = [];
        $actionEnums = [];
        if (is_array($json)) {
            $components = $json['components']['schemas'] ?? $json['definitions'] ?? [];
            if (is_array($components)) {
                foreach ($components as $name => $schema) {
                    $n = (string) $name;
                    if (preg_match('/Exchange|Trade|Transfer|Adjust|Request|Account|Customer|Person/i', $n)) {
                        $schemas[] = $n;
                    }
                    if (is_array($schema)) {
                        self::collectActionEnums($n, $schema, $actionEnums);
                    }
                }
            }
        }

        return [
            'version' => $version,
            'sha256' => $sha,
            'paths' => $paths,
            'post_paths' => array_values(array_unique($postPaths)),
            'schemas' => $schemas,
            'action_enums' => $actionEnums,
            'historical_action_reference' => [
                'note' => 'NOT authoritative — archive only; mapping must come from live enum + Evidence',
                'exchange_buy_ref' => '32',
                'exchange_sell_ref' => '64',
                'cash_receive_ref' => '2',
                'cash_pay_ref' => '4',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $out
     */
    private static function collectActionEnums(string $schemaName, array $schema, array &$out): void
    {
        foreach (['Action', 'action', 'ActionName', 'actionName'] as $key) {
            if (!isset($schema['properties'][$key]) && !isset($schema[$key])) {
                continue;
            }
            $prop = $schema['properties'][$key] ?? $schema[$key] ?? null;
            if (!is_array($prop)) {
                continue;
            }
            if (isset($prop['enum']) && is_array($prop['enum'])) {
                $out[$schemaName . '.' . $key] = $prop['enum'];
            }
            if (isset($prop['description'])) {
                $out[$schemaName . '.' . $key . '.description'] = $prop['description'];
            }
        }
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $pk => $pv) {
                if (is_array($pv) && preg_match('/action/i', (string) $pk)) {
                    if (isset($pv['enum'])) {
                        $out[$schemaName . '.' . $pk] = $pv['enum'];
                    }
                }
            }
        }
    }
}
