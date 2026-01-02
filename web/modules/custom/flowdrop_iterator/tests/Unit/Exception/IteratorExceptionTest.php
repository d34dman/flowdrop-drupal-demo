<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_iterator\Unit\Exception;

use Drupal\flowdrop_iterator\Exception\IteratorException;
use Drupal\flowdrop_iterator\Exception\IterationFailedException;
use Drupal\flowdrop_iterator\Exception\MaxIterationsExceededException;
use Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Iterator exception classes.
 *
 * @group flowdrop_iterator
 */
class IteratorExceptionTest extends TestCase {

  /**
   * Test IteratorException basic functionality.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\IteratorException
   */
  public function testIteratorException(): void {
    $exception = new IteratorException("Test error");

    $this->assertSame("Test error", $exception->getMessage());
    $this->assertNull($exception->getIteratorNodeId());
    $this->assertSame([], $exception->getContext());
  }

  /**
   * Test IteratorException with node ID.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\IteratorException::setIteratorNodeId
   * @covers \Drupal\flowdrop_iterator\Exception\IteratorException::getIteratorNodeId
   */
  public function testIteratorExceptionWithNodeId(): void {
    $exception = new IteratorException("Error");
    $exception->setIteratorNodeId("iterator_1");

    $this->assertSame("iterator_1", $exception->getIteratorNodeId());
  }

  /**
   * Test IteratorException with context.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\IteratorException::setContext
   * @covers \Drupal\flowdrop_iterator\Exception\IteratorException::getContext
   */
  public function testIteratorExceptionWithContext(): void {
    $exception = new IteratorException("Error");
    $context = ["key" => "value", "items" => [1, 2, 3]];
    $exception->setContext($context);

    $this->assertSame($context, $exception->getContext());
  }

  /**
   * Test IteratorException forNode factory.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\IteratorException::forNode
   */
  public function testIteratorExceptionForNode(): void {
    $previous = new \RuntimeException("Original error");
    $exception = IteratorException::forNode("Failed", "iter_1", $previous);

    $this->assertSame("Failed", $exception->getMessage());
    $this->assertSame("iter_1", $exception->getIteratorNodeId());
    $this->assertSame($previous, $exception->getPrevious());
  }

  /**
   * Test IterationFailedException basic functionality.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\IterationFailedException
   */
  public function testIterationFailedException(): void {
    $exception = new IterationFailedException("Iteration error");

    $this->assertSame("Iteration error", $exception->getMessage());
    $this->assertSame(0, $exception->getIterationIndex());
    $this->assertNull($exception->getFailedItem());
  }

  /**
   * Test IterationFailedException with index.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\IterationFailedException::setIterationIndex
   * @covers \Drupal\flowdrop_iterator\Exception\IterationFailedException::getIterationIndex
   */
  public function testIterationFailedExceptionWithIndex(): void {
    $exception = new IterationFailedException("Error");
    $exception->setIterationIndex(5);

    $this->assertSame(5, $exception->getIterationIndex());
  }

  /**
   * Test IterationFailedException with failed item.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\IterationFailedException::setFailedItem
   * @covers \Drupal\flowdrop_iterator\Exception\IterationFailedException::getFailedItem
   */
  public function testIterationFailedExceptionWithFailedItem(): void {
    $exception = new IterationFailedException("Error");
    $item = ["data" => "test"];
    $exception->setFailedItem($item);

    $this->assertSame($item, $exception->getFailedItem());
  }

  /**
   * Test IterationFailedException forIteration factory.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\IterationFailedException::forIteration
   */
  public function testIterationFailedExceptionForIteration(): void {
    $previous = new \Exception("Original");
    $item = ["id" => 123];

    $exception = IterationFailedException::forIteration(
      "Processing failed",
      "iterator_1",
      3,
      $item,
      $previous
    );

    $this->assertStringContainsString("Processing failed", $exception->getMessage());
    $this->assertStringContainsString("iteration 3", $exception->getMessage());
    $this->assertStringContainsString("iterator_1", $exception->getMessage());
    $this->assertSame("iterator_1", $exception->getIteratorNodeId());
    $this->assertSame(3, $exception->getIterationIndex());
    $this->assertSame($item, $exception->getFailedItem());
    $this->assertSame($previous, $exception->getPrevious());
  }

  /**
   * Test SubWorkflowDetectionException basic functionality.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException
   */
  public function testSubWorkflowDetectionException(): void {
    $exception = new SubWorkflowDetectionException("Detection failed");

    $this->assertSame("Detection failed", $exception->getMessage());
    $this->assertSame([], $exception->getDetectionErrors());
  }

  /**
   * Test SubWorkflowDetectionException with errors.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException::setDetectionErrors
   * @covers \Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException::getDetectionErrors
   */
  public function testSubWorkflowDetectionExceptionWithErrors(): void {
    $exception = new SubWorkflowDetectionException("Error");
    $errors = ["Error 1", "Error 2"];
    $exception->setDetectionErrors($errors);

    $this->assertSame($errors, $exception->getDetectionErrors());
  }

  /**
   * Test SubWorkflowDetectionException noItemPortConnection factory.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException::noItemPortConnection
   */
  public function testSubWorkflowDetectionExceptionNoItemPort(): void {
    $exception = SubWorkflowDetectionException::noItemPortConnection("iter_1");

    $this->assertStringContainsString("iter_1", $exception->getMessage());
    $this->assertStringContainsString("item", $exception->getMessage());
    $this->assertSame("iter_1", $exception->getIteratorNodeId());
    $this->assertNotEmpty($exception->getDetectionErrors());
  }

  /**
   * Test SubWorkflowDetectionException noLoopbackEdge factory.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException::noLoopbackEdge
   */
  public function testSubWorkflowDetectionExceptionNoLoopback(): void {
    $exception = SubWorkflowDetectionException::noLoopbackEdge("iter_1");

    $this->assertStringContainsString("iter_1", $exception->getMessage());
    $this->assertStringContainsString("loopback", $exception->getMessage());
    $this->assertSame("iter_1", $exception->getIteratorNodeId());
    $this->assertNotEmpty($exception->getDetectionErrors());
  }

  /**
   * Test SubWorkflowDetectionException emptySubWorkflow factory.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException::emptySubWorkflow
   */
  public function testSubWorkflowDetectionExceptionEmptySubWorkflow(): void {
    $exception = SubWorkflowDetectionException::emptySubWorkflow("iter_1");

    $this->assertStringContainsString("iter_1", $exception->getMessage());
    $this->assertStringContainsString("no nodes", $exception->getMessage());
    $this->assertSame("iter_1", $exception->getIteratorNodeId());
    $this->assertNotEmpty($exception->getDetectionErrors());
  }

  /**
   * Test SubWorkflowDetectionException withErrors factory.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException::withErrors
   */
  public function testSubWorkflowDetectionExceptionWithErrorsFactory(): void {
    $errors = ["Missing connection", "Invalid edge type"];

    $exception = SubWorkflowDetectionException::withErrors("iter_1", $errors);

    $this->assertStringContainsString("iter_1", $exception->getMessage());
    $this->assertStringContainsString("Missing connection", $exception->getMessage());
    $this->assertStringContainsString("Invalid edge type", $exception->getMessage());
    $this->assertSame("iter_1", $exception->getIteratorNodeId());
    $this->assertSame($errors, $exception->getDetectionErrors());
  }

  /**
   * Test MaxIterationsExceededException basic functionality.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\MaxIterationsExceededException
   */
  public function testMaxIterationsExceededException(): void {
    $exception = new MaxIterationsExceededException("Too many items");

    $this->assertSame("Too many items", $exception->getMessage());
    $this->assertSame(0, $exception->getRequestedIterations());
    $this->assertSame(0, $exception->getMaxIterations());
  }

  /**
   * Test MaxIterationsExceededException with values.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\MaxIterationsExceededException::setRequestedIterations
   * @covers \Drupal\flowdrop_iterator\Exception\MaxIterationsExceededException::getRequestedIterations
   * @covers \Drupal\flowdrop_iterator\Exception\MaxIterationsExceededException::setMaxIterations
   * @covers \Drupal\flowdrop_iterator\Exception\MaxIterationsExceededException::getMaxIterations
   */
  public function testMaxIterationsExceededExceptionWithValues(): void {
    $exception = new MaxIterationsExceededException("Error");
    $exception->setRequestedIterations(5000);
    $exception->setMaxIterations(1000);

    $this->assertSame(5000, $exception->getRequestedIterations());
    $this->assertSame(1000, $exception->getMaxIterations());
  }

  /**
   * Test MaxIterationsExceededException exceeded factory.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\MaxIterationsExceededException::exceeded
   */
  public function testMaxIterationsExceededExceptionFactory(): void {
    $exception = MaxIterationsExceededException::exceeded("iter_1", 2500, 1000);

    $this->assertStringContainsString("iter_1", $exception->getMessage());
    $this->assertStringContainsString("2500", $exception->getMessage());
    $this->assertStringContainsString("1000", $exception->getMessage());
    $this->assertStringContainsString("truncated", $exception->getMessage());
    $this->assertSame("iter_1", $exception->getIteratorNodeId());
    $this->assertSame(2500, $exception->getRequestedIterations());
    $this->assertSame(1000, $exception->getMaxIterations());
  }

  /**
   * Test exception inheritance chain.
   *
   * @covers \Drupal\flowdrop_iterator\Exception\IteratorException
   * @covers \Drupal\flowdrop_iterator\Exception\IterationFailedException
   * @covers \Drupal\flowdrop_iterator\Exception\SubWorkflowDetectionException
   * @covers \Drupal\flowdrop_iterator\Exception\MaxIterationsExceededException
   */
  public function testExceptionInheritance(): void {
    // All should extend IteratorException.
    $iterationFailed = new IterationFailedException("Test");
    $subWorkflow = new SubWorkflowDetectionException("Test");
    $maxIterations = new MaxIterationsExceededException("Test");

    $this->assertInstanceOf(IteratorException::class, $iterationFailed);
    $this->assertInstanceOf(IteratorException::class, $subWorkflow);
    $this->assertInstanceOf(IteratorException::class, $maxIterations);

    // All should be catchable as RuntimeException.
    $this->assertInstanceOf(\RuntimeException::class, $iterationFailed);
    $this->assertInstanceOf(\RuntimeException::class, $subWorkflow);
    $this->assertInstanceOf(\RuntimeException::class, $maxIterations);
  }

}
