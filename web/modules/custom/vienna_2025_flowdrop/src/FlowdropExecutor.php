<?php

declare(strict_types=1);

namespace Drupal\vienna_2025_flowdrop;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\flowdrop_job\FlowDropJobInterface;
use Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface;
use Drupal\flowdrop_pipeline\Service\JobGenerationService;
use Drupal\flowdrop_runtime\Service\Orchestrator\SynchronousOrchestrator;
use Drupal\flowdrop_workflow\FlowDropWorkflowInterface;
use Drupal\vienna_2025_flowdrop\DTO\Vienna2024UserDTO;

/**
 * @todo Add class description.
 */
final class FlowdropExecutor {

  /**
   * Constructs a FlowdropExecutor object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly JobGenerationService $pipelineJobGeneration,
    private readonly SynchronousOrchestrator $synchronousOrchestrator,
  ) {}

  public function loadWorkflow(string $workflow_id) {
    return $this
      ->entityTypeManager
      ->getStorage('flowdrop_workflow')
      ->load($workflow_id);
  }

  public function loadPipeline(string $pipeline_id) {
    return $this
      ->entityTypeManager
      ->getStorage('flowdrop_pipeline')
      ->load($pipeline_id);
  }

  public function generateNewPipeline(FlowDropWorkflowInterface $workflow): FlowDropPipelineInterface {
    $pipeline_storage = $this->entityTypeManager->getStorage("flowdrop_pipeline");
    /** @var FlowDropPipelineInterface $pipeline */
    $pipeline = $pipeline_storage->create([
      "label" => $workflow->label(),
      "bundle" => "default",
      "workflow_id" => ["target_id" => $workflow->id()],
    ]);
    $pipeline->save();
    $this->pipelineJobGeneration->generateJobs($pipeline);
    return $pipeline;
  }

  public function executePipeline(FlowDropPipelineInterface $pipeline, Vienna2024UserDTO $user_info): bool {
    // Find the entry point job (vienna_2025_frontdesk node).
    $jobs = $this->getPipelineJobByPluginId($pipeline, 'vienna_2025_frontdesk');
    $job = reset($jobs);
    
    if ($job instanceof FlowDropJobInterface) {
      // Get existing job data to preserve config.
      $existing_data = $job->getInputData();
      
      // Prepare input data in the format expected by orchestrator.
      // The orchestrator expects: ['inputs' => [...], 'config' => [...]]
      $job_data = [
        'config' => $existing_data['config'] ?? [],
        'inputs' => [
          'message' => json_encode($user_info),
        ],
      ];
      
      $job->setInputData($job_data);
      $job->save();
      
      $this->loggerFactory
        ->get('flowdrop_executor')
        ->debug('Set input data for job @job_id: @data', [
          '@job_id' => $job->id(),
          '@data' => json_encode($job_data),
        ]);
    }

    $this->synchronousOrchestrator->executePipeline($pipeline);
    return TRUE;
  }

  /**
   * Reset all jobs in a pipeline to pending state.
   *
   * This method resets all jobs to their initial "pending" state,
   * clears output data, and removes error messages. Useful for
   * re-running a pipeline from scratch.
   *
   * @param \Drupal\flowdrop_pipeline\Entity\FlowDropPipelineInterface $pipeline
   *   The pipeline whose jobs should be reset.
   * @param bool $preserve_input
   *   Whether to preserve input data. Default is TRUE.
   *
   * @return int
   *   The number of jobs that were reset.
   */
  public function resetPipelineJobs(FlowDropPipelineInterface $pipeline, bool $preserve_input = TRUE): int {
    $jobs = $pipeline->getJobs();
    $reset_count = 0;

    /** @var FlowDropJobInterface $job */
    foreach ($jobs as $job) {
      // Reset status to pending.
      $job->set('status', 'pending');

      // Clear output data.
      $job->setOutputData([]);

      // Clear error message and retry count.
      $job->set('error_message', NULL);
      $job->set('retry_count', 0);

      // Optionally clear input data (except for initial/entry point jobs).
      if (!$preserve_input) {
        $job->setInputData([]);
      }

      // Save the job.
      $job->save();

      $reset_count++;
    }

    $this->loggerFactory
      ->get('flowdrop_executor')
      ->info('Reset @count jobs for pipeline @pipeline_id', [
        '@count' => $reset_count,
        '@pipeline_id' => $pipeline->id(),
      ]);

    return $reset_count;
  }

  /**
   * Get list of all jobs that matches a plugin id.
   *
   * @param FlowDropPipelineInterface $pipeline
   *   Pipeline for which jobs needs to be matched with.
   * @param string $flowdrop_node_type_plugin_id
   *   The Plugin Id that the job needs to be matched with.
   * @return FlowDropJobInterface[]
   *  List of matching jobs.
   */
  private function getPipelineJobByPluginId(FlowDropPipelineInterface $pipeline, string $flowdrop_node_type_plugin_id): array {
    $jobs = $pipeline->getJobs();
    $matches = [];
    /** @var FlowDropJobInterface $job */
    foreach ($jobs as $job) {
      // Get node_type_id from metadata
      $nodeTypeId = $job->getMetadataValue("node_type_id", "");
      if ($flowdrop_node_type_plugin_id == $nodeTypeId) {
        $matches[] = $job;
      }
    }
    return $matches;
  }

  private function getPipelineJobByNodeId(FlowDropPipelineInterface $pipeline, string $flowdrop_node_type_plugin_id): ?FlowDropJobInterface {
    $jobs = $pipeline->getJobs();
    /** @var FlowDropJobInterface $job */
    foreach ($jobs as $job) {
      if ($flowdrop_node_type_plugin_id == $job->getNodeId()) {
        return $job;
      }
    }
    return $job;
  }

}
