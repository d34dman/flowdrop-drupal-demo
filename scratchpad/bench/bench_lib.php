<?php
/**
 * Shared helpers for the harness: where the bench repo is served from, and a
 * cached fetch of its manifest and prompt files.
 *
 * Everything the harness needs from flowdrop-ai-bench is read over HTTP from its
 * GitHub Pages site, so a run is reproducible from URLs alone and the ledger can
 * record exactly which corpus version and prompt it used. BENCH_BASE overrides
 * the base (e.g. a local checkout served with `python3 -m http.server`).
 */
const BENCH_BASE_DEFAULT = 'https://d34dman.github.io/flowdrop-ai-bench/';

function bench_base(): string {
  $b = getenv('BENCH_BASE') ?: BENCH_BASE_DEFAULT;
  return rtrim($b, '/') . '/';
}

function bench_fetch(string $rel, string $cacheDir): string {
  $url = bench_base() . ltrim($rel, '/');
  $body = \Drupal::httpClient()->request('GET', $url, ['timeout' => 30])->getBody()->getContents();
  if ($body === '') { throw new \RuntimeException("empty response from $url"); }
  if (!is_dir($cacheDir)) { mkdir($cacheDir, 0777, TRUE); }
  file_put_contents($cacheDir . '/' . str_replace('/', '__', $rel), $body);
  return $body;
}

function bench_manifest(string $version, string $cacheDir): array {
  $m = json_decode(bench_fetch("corpus/$version/manifest.json", $cacheDir), TRUE);
  if (!isset($m['pages'])) { throw new \RuntimeException('manifest has no pages'); }
  return $m;
}

/** Splits a prompt file into [front-matter array, body]. */
function bench_prompt_file(string $rel, string $cacheDir): array {
  $txt = bench_fetch($rel, $cacheDir);
  $meta = [];
  $body = $txt;
  if (preg_match('/\A---\n(.*?)\n---\n(.*)\z/s', $txt, $m)) {
    foreach (explode("\n", $m[1]) as $line) {
      if (!str_contains($line, ':')) { continue; }
      [$k, $v] = explode(':', $line, 2);
      $v = trim($v);
      if (str_starts_with($v, '[')) { $v = array_map('trim', explode(',', trim($v, '[]'))); }
      else { $v = trim($v, '"'); }
      $meta[trim($k)] = $v;
    }
    $body = $m[2];
  }
  return [$meta, rtrim($body, "\n"), hash('sha256', $txt)];
}

function bench_flowdrop_version(): string {
  $lock = json_decode((string) file_get_contents(dirname(DRUPAL_ROOT) . '/composer.lock'), TRUE);
  foreach ($lock['packages'] ?? [] as $p) {
    if ($p['name'] === 'drupal/flowdrop') {
      return $p['version'] . '@' . substr($p['source']['reference'] ?? '', 0, 12);
    }
  }
  return 'unknown';
}
