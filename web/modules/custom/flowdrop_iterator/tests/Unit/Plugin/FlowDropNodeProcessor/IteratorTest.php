<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_iterator\Unit\Plugin\FlowDropNodeProcessor;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\flowdrop\DTO\ParameterBag;
use Drupal\flowdrop_iterator\Plugin\FlowDropNodeProcessor\Iterator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Iterator node processor plugin.
 *
 * @coversDefaultClass \Drupal\flowdrop_iterator\Plugin\FlowDropNodeProcessor\Iterator
 * @group flowdrop_iterator
 */
class IteratorTest extends TestCase {

  /**
   * Mock logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * Mock logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The processor under test.
   *
   * @var \Drupal\flowdrop_iterator\Plugin\FlowDropNodeProcessor\Iterator
   */
  protected Iterator $processor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method("get")->willReturn($this->logger);

    $this->processor = new Iterator(
      [],
      "iterator",
      ["id" => "iterator"],
      $this->loggerFactory,
    );
  }

  /**
   * Test getType returns correct value.
   *
   * @covers ::getType
   */
  public function testGetType(): void {
    $this->assertSame("iterator", $this->processor->getType());
  }

  /**
   * Test validateParams always returns true.
   *
   * @covers ::validateParams
   */
  public function testValidateParams(): void {
    $this->assertTrue($this->processor->validateParams([]));
    $this->assertTrue($this->processor->validateParams(["data" => []]));
    $this->assertTrue($this->processor->validateParams(["data" => [1, 2, 3]]));
    $this->assertTrue($this->processor->validateParams(["random" => "value"]));
  }

  /**
   * Test getParameterSchema returns expected structure.
   *
   * @covers ::getParameterSchema
   */
  public function testGetParameterSchema(): void {
    $schema = $this->processor->getParameterSchema();

    $this->assertIsArray($schema);
    $this->assertSame("object", $schema["type"]);
    $this->assertArrayHasKey("properties", $schema);

    // Check data parameter.
    $this->assertArrayHasKey("data", $schema["properties"]);
    $this->assertSame("array", $schema["properties"]["data"]["type"]);
  }

  /**
   * Test getOutputSchema returns expected structure.
   *
   * @covers ::getOutputSchema
   */
  public function testGetOutputSchema(): void {
    $schema = $this->processor->getOutputSchema();

    $this->assertIsArray($schema);
    $this->assertSame("object", $schema["type"]);
    $this->assertArrayHasKey("properties", $schema);

    // Check item output.
    $this->assertArrayHasKey("item", $schema["properties"]);
    $this->assertSame("mixed", $schema["properties"]["item"]["type"]);
    $this->assertSame("item", $schema["properties"]["item"]["portType"]);

    // Check done output.
    $this->assertArrayHasKey("done", $schema["properties"]);
    $this->assertSame("array", $schema["properties"]["done"]["type"]);
    $this->assertSame("done", $schema["properties"]["done"]["portType"]);

    // Check index output.
    $this->assertArrayHasKey("index", $schema["properties"]);
    $this->assertSame("integer", $schema["properties"]["index"]["type"]);

    // Check total output.
    $this->assertArrayHasKey("total", $schema["properties"]);
    $this->assertSame("integer", $schema["properties"]["total"]["type"]);

    // Check isComplete output.
    $this->assertArrayHasKey("isComplete", $schema["properties"]);
    $this->assertSame("boolean", $schema["properties"]["isComplete"]["type"]);
  }

  /**
   * Test requiresSpecialOrchestration returns true.
   *
   * @covers ::requiresSpecialOrchestration
   */
  public function testRequiresSpecialOrchestration(): void {
    $this->assertTrue($this->processor->requiresSpecialOrchestration());
  }

  /**
   * Test getSpecialPorts returns expected ports.
   *
   * @covers ::getSpecialPorts
   */
  public function testGetSpecialPorts(): void {
    $ports = $this->processor->getSpecialPorts();

    $this->assertIsArray($ports);
    $this->assertArrayHasKey("loopback", $ports);
    $this->assertSame("input", $ports["loopback"]);
    $this->assertArrayHasKey("item", $ports);
    $this->assertSame("output", $ports["item"]);
  }

  /**
   * Test fallback process method with array data.
   *
   * Note: This tests the fallback behavior when the processor is called
   * directly instead of through the orchestrator. The orchestrator should
   * intercept iterator nodes and use IteratorExecutor instead.
   *
   * @covers ::process
   */
  public function testFallbackProcessWithArray(): void {
    // This warning should be logged when fallback is used.
    $this->logger->expects($this->once())
      ->method("warning")
      ->with($this->stringContains("orchestrator should handle"));

    $params = new ParameterBag(["data" => ["a", "b", "c"]]);

    // Use reflection to call protected method.
    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod("process");
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    $this->assertIsArray($result);
    $this->assertSame(["a", "b", "c"], $result["done"]);
    $this->assertTrue($result["isComplete"]);
    $this->assertSame(3, $result["index"]);
    $this->assertSame(3, $result["total"]);
    $this->assertTrue($result["_fallback"]);
  }

  /**
   * Test fallback process method with non-array data.
   *
   * @covers ::process
   */
  public function testFallbackProcessWithNonArray(): void {
    $params = new ParameterBag(["data" => "single_value"]);

    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod("process");
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    // Non-array should be wrapped in array.
    $this->assertSame(["single_value"], $result["done"]);
    $this->assertTrue($result["isComplete"]);
    $this->assertSame(1, $result["total"]);
  }

  /**
   * Test fallback process method with empty data.
   *
   * @covers ::process
   */
  public function testFallbackProcessWithEmptyData(): void {
    $params = new ParameterBag(["data" => []]);

    $reflection = new \ReflectionClass($this->processor);
    $method = $reflection->getMethod("process");
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->processor, $params);

    $this->assertSame([], $result["done"]);
    $this->assertTrue($result["isComplete"]);
    $this->assertSame(0, $result["total"]);
  }

}
