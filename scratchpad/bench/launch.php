<?php
/**
 * Launches bench cells and appends one ledger line per run to runs.jsonl.
 *
 * The ledger is deliberately thin: run identity, the pipeline it produced and
 * the context uuid its model calls carried. Everything else is derived later
 * by collect.php from stored state, so a run never has to be watched.
 *
 * wall_seconds is the one thing that cannot be derived — the stored job stamps
 * are second-resolution, and orchestrator overhead is a sub-second quantity —
 * so the launch is timed here and carried through.
 *
 * Usage: drush php:script launch.php -- <out_dir> <workflows,csv> <urlkeys,csv> [reps] [tag]
 */
$outDir   = $extra[0] ?? 'scratchpad/bench/results';
$wfIds    = array_filter(explode(',', $extra[1] ?? ''));
$urlKeys  = array_filter(explode(',', $extra[2] ?? 'small,medium,large'));
$reps     = (int) ($extra[3] ?? 1);
$tag      = $extra[4] ?? '';

if ($outDir[0] !== '/') { $outDir = dirname(DRUPAL_ROOT) . '/' . $outDir; }
foreach ([$outDir, "$outDir/outputs"] as $d) {
  if (!is_dir($d) && !mkdir($d, 0777, TRUE) && !is_dir($d)) {
    throw new \RuntimeException("cannot create output directory: $d");
  }
}

$URLS = [
  'small'  => 'https://www.drupal.org/about',
  'medium' => 'https://www.ibm.com/think/topics/drupal-wordpress',
  'large'  => 'https://en.wikipedia.org/wiki/Drupal',
];

// drush php:script executes as anonymous, whose quota is a small shared
// ceiling meant for public traffic; a benchmark exhausts it mid-matrix.
\Drupal::service('account_switcher')->switchTo(\Drupal\user\Entity\User::load(1));

$etm = \Drupal::entityTypeManager();
$launcher = \Drupal::service('flowdrop_workflow_executor.launcher');
$runContext = \Drupal::service('fd_bench.run_context');
$wfStorage = $etm->getStorage('flowdrop_workflow');

$fh = fopen("$outDir/runs.jsonl", 'a');
if ($fh === FALSE) { throw new \RuntimeException("cannot append to $outDir/runs.jsonl"); }

foreach (range(1, $reps) as $rep) {
  foreach ($urlKeys as $urlKey) {
    $url = $URLS[$urlKey] ?? $urlKey;
    foreach ($wfIds as $wfId) {
      $wf = $wfStorage->load($wfId);
      if (!$wf) { printf("!! missing workflow %s\n", $wfId); continue; }

      $runId = sprintf('%s__%s__r%d__%d', $wfId, $urlKey, $rep, time());
      $runUuid = \Drupal::service('uuid')->generate();
      $runContext->set($runUuid);

      $t0 = microtime(TRUE);
      $pipelineId = NULL; $status = 'error'; $error = NULL;
      try {
        $res = $launcher->launch($wf, ['url' => $url],
          new \Drupal\flowdrop_workflow_executor\DTO\LaunchOptions(wait: TRUE));
        $pipelineId = $res->pipelineId;
        $status = $res->status;
      }
      catch (\Throwable $e) { $error = $e->getMessage(); }
      $wall = microtime(TRUE) - $t0;
      $runContext->set(NULL);

      $record = [
        'run_id' => $runId,
        'workflow' => $wfId,
        'url_key' => $urlKey,
        'url' => $url,
        'rep' => $rep,
        'pipeline_id' => (string) $pipelineId,
        'context_uuid' => $runUuid,
        'wall_seconds' => round($wall, 4),
        'launch_status' => $status,
        'launch_error' => $error,
        'tag' => $tag,
        'ts' => gmdate('c'),
      ];
      fwrite($fh, json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
      fflush($fh);

      printf("  %-28s %-7s r%-3d %-10s wall=%7.2fs pipeline=%s%s\n",
        $wfId, $urlKey, $rep, $status, $wall, $pipelineId, $error ? "  ERR: $error" : '');
    }
  }
}
fclose($fh);
printf("appended to %s/runs.jsonl\n", $outDir);
