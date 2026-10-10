<?php

declare(strict_types=1);

namespace Drupal\fd_demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\flowdrop_session\Constants\SessionStatus;
use Drupal\flowdrop_workflow\Entity\FlowDropWorkflow;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * "Try it" for people who may not open the editor: a fresh session in the
 * standalone Playground, without the add-session form. Everyone else goes to
 * the editor's Test mode; the standalone Playground is deprecated in FlowDrop
 * 2.7 and becomes a redirect to Test mode in 3.0.
 *
 * Mirrors WorkflowSessionAddForm::submitForm(), with the workflow's label as
 * the session name.
 */
final class TryController extends ControllerBase {

  /**
   * Creates the session and redirects into it.
   */
  public function try(FlowDropWorkflow $flowdrop_workflow): RedirectResponse {
    $session = $this->entityTypeManager()->getStorage('flowdrop_session')->create([
      'name' => (string) $flowdrop_workflow->label(),
      'workflow_id' => $flowdrop_workflow->id(),
      'status' => SessionStatus::Idle->value,
      'uid' => $this->currentUser()->id(),
    ]);
    $session->save();

    return $this->redirect('flowdrop_playground.workflow.session.page', [
      'flowdrop_workflow' => $flowdrop_workflow->id(),
      'flowdrop_session' => $session->id(),
    ]);
  }

}
