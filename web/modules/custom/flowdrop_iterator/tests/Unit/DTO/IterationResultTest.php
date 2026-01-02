<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_iterator\Unit\DTO;

use Drupal\flowdrop_iterator\DTO\IterationResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the IterationResult DTO.
 *
 * @coversDefaultClass \Drupal\flowdrop_iterator\DTO\IterationResult
 * @group flowdrop_iterator
 */
class IterationResultTest extends TestCase {

  /**
   * Test constructor and getters.
   *
   * @covers ::__construct
   * @covers ::getIndex
   * @covers ::getInputItem
   * @covers ::getOutputResult
   * @covers ::isSuccess
   * @covers ::getError
   * @covers ::getMetadata
   */
  public function testConstructorAndGetters(): void {
    $result = new IterationResult(
      index: 5,
      inputItem: ["data" => "input"],
      outputResult: ["data" => "output"],
      success: TRUE,
      error: NULL,
      metadata: ["key" => "value"],
    );

    $this->assertSame(5, $result->getIndex());
    $this->assertSame(["data" => "input"], $result->getInputItem());
    $this->assertSame(["data" => "output"], $result->getOutputResult());
    $this->assertTrue($result->isSuccess());
    $this->assertNull($result->getError());
    $this->assertSame(["key" => "value"], $result->getMetadata());
  }

  /**
   * Test failed result.
   *
   * @covers ::__construct
   * @covers ::isSuccess
   * @covers ::getError
   */
  public function testFailedResult(): void {
    $result = new IterationResult(
      index: 3,
      inputItem: "item",
      outputResult: NULL,
      success: FALSE,
      error: "Processing failed",
    );

    $this->assertFalse($result->isSuccess());
    $this->assertSame("Processing failed", $result->getError());
    $this->assertNull($result->getOutputResult());
  }

  /**
   * Test getMetadataValue with existing key.
   *
   * @covers ::getMetadataValue
   */
  public function testGetMetadataValueExists(): void {
    $result = new IterationResult(
      index: 0,
      inputItem: NULL,
      outputResult: NULL,
      metadata: [
        "executionTime" => 1.5,
        "jobId" => "job_123",
      ],
    );

    $this->assertSame(1.5, $result->getMetadataValue("executionTime"));
    $this->assertSame("job_123", $result->getMetadataValue("jobId"));
  }

  /**
   * Test getMetadataValue with missing key returns default.
   *
   * @covers ::getMetadataValue
   */
  public function testGetMetadataValueDefault(): void {
    $result = new IterationResult(
      index: 0,
      inputItem: NULL,
      outputResult: NULL,
      metadata: [],
    );

    $this->assertNull($result->getMetadataValue("missing"));
    $this->assertSame("default_value", $result->getMetadataValue("missing", "default_value"));
    $this->assertSame(42, $result->getMetadataValue("missing", 42));
  }

  /**
   * Test success factory method.
   *
   * @covers ::success
   */
  public function testSuccessFactory(): void {
    $result = IterationResult::success(
      index: 10,
      inputItem: "input_data",
      outputResult: "output_data",
      metadata: ["time" => 100],
    );

    $this->assertSame(10, $result->getIndex());
    $this->assertSame("input_data", $result->getInputItem());
    $this->assertSame("output_data", $result->getOutputResult());
    $this->assertTrue($result->isSuccess());
    $this->assertNull($result->getError());
    $this->assertSame(["time" => 100], $result->getMetadata());
  }

  /**
   * Test success factory without metadata.
   *
   * @covers ::success
   */
  public function testSuccessFactoryWithoutMetadata(): void {
    $result = IterationResult::success(
      index: 0,
      inputItem: "in",
      outputResult: "out",
    );

    $this->assertTrue($result->isSuccess());
    $this->assertSame([], $result->getMetadata());
  }

  /**
   * Test failure factory method.
   *
   * @covers ::failure
   */
  public function testFailureFactory(): void {
    $result = IterationResult::failure(
      index: 5,
      inputItem: ["failed" => "item"],
      error: "Node execution failed",
      metadata: ["retryCount" => 3],
    );

    $this->assertSame(5, $result->getIndex());
    $this->assertSame(["failed" => "item"], $result->getInputItem());
    $this->assertNull($result->getOutputResult());
    $this->assertFalse($result->isSuccess());
    $this->assertSame("Node execution failed", $result->getError());
    $this->assertSame(["retryCount" => 3], $result->getMetadata());
  }

  /**
   * Test failure factory without metadata.
   *
   * @covers ::failure
   */
  public function testFailureFactoryWithoutMetadata(): void {
    $result = IterationResult::failure(
      index: 0,
      inputItem: NULL,
      error: "Error message",
    );

    $this->assertFalse($result->isSuccess());
    $this->assertSame([], $result->getMetadata());
  }

  /**
   * Test skipped factory method.
   *
   * @covers ::skipped
   */
  public function testSkippedFactory(): void {
    $result = IterationResult::skipped(
      index: 7,
      inputItem: "skipped_item",
      reason: "Validation failed",
      metadata: ["validated" => FALSE],
    );

    $this->assertSame(7, $result->getIndex());
    $this->assertSame("skipped_item", $result->getInputItem());
    // Skipped is still a "success" (didn't fail).
    $this->assertTrue($result->isSuccess());
    $this->assertNull($result->getError());

    // Output should contain skipped marker.
    $output = $result->getOutputResult();
    $this->assertIsArray($output);
    $this->assertTrue($output["_skipped"]);
    $this->assertSame("Validation failed", $output["_reason"]);

    // Metadata should include skipped flag.
    $this->assertTrue($result->getMetadataValue("skipped"));
    $this->assertFalse($result->getMetadataValue("validated"));
  }

  /**
   * Test skipped factory without metadata.
   *
   * @covers ::skipped
   */
  public function testSkippedFactoryWithoutMetadata(): void {
    $result = IterationResult::skipped(
      index: 0,
      inputItem: NULL,
      reason: "Skip reason",
    );

    $this->assertTrue($result->isSuccess());
    $this->assertTrue($result->getMetadataValue("skipped"));
  }

  /**
   * Test toArray method.
   *
   * @covers ::toArray
   */
  public function testToArray(): void {
    $result = new IterationResult(
      index: 2,
      inputItem: "input",
      outputResult: "output",
      success: TRUE,
      error: NULL,
      metadata: ["key" => "val"],
    );

    $array = $result->toArray();

    $this->assertSame(2, $array["index"]);
    $this->assertSame("input", $array["inputItem"]);
    $this->assertSame("output", $array["outputResult"]);
    $this->assertTrue($array["success"]);
    $this->assertNull($array["error"]);
    $this->assertSame(["key" => "val"], $array["metadata"]);
  }

  /**
   * Test toArray with failed result.
   *
   * @covers ::toArray
   */
  public function testToArrayFailed(): void {
    $result = IterationResult::failure(
      index: 1,
      inputItem: "bad",
      error: "Failed",
    );

    $array = $result->toArray();

    $this->assertFalse($array["success"]);
    $this->assertSame("Failed", $array["error"]);
    $this->assertNull($array["outputResult"]);
  }

  /**
   * Test fromArray method.
   *
   * @covers ::fromArray
   */
  public function testFromArray(): void {
    $data = [
      "index" => 3,
      "inputItem" => ["in" => "data"],
      "outputResult" => ["out" => "data"],
      "success" => TRUE,
      "error" => NULL,
      "metadata" => ["m" => "v"],
    ];

    $result = IterationResult::fromArray($data);

    $this->assertSame(3, $result->getIndex());
    $this->assertSame(["in" => "data"], $result->getInputItem());
    $this->assertSame(["out" => "data"], $result->getOutputResult());
    $this->assertTrue($result->isSuccess());
    $this->assertNull($result->getError());
    $this->assertSame(["m" => "v"], $result->getMetadata());
  }

  /**
   * Test fromArray with defaults.
   *
   * @covers ::fromArray
   */
  public function testFromArrayDefaults(): void {
    $data = [];

    $result = IterationResult::fromArray($data);

    $this->assertSame(0, $result->getIndex());
    $this->assertNull($result->getInputItem());
    $this->assertNull($result->getOutputResult());
    $this->assertTrue($result->isSuccess());
    $this->assertNull($result->getError());
    $this->assertSame([], $result->getMetadata());
  }

  /**
   * Test round-trip conversion.
   *
   * @covers ::toArray
   * @covers ::fromArray
   */
  public function testRoundTrip(): void {
    $original = new IterationResult(
      index: 99,
      inputItem: ["complex" => ["nested" => "data"]],
      outputResult: "result",
      success: FALSE,
      error: "Test error",
      metadata: ["a" => 1, "b" => 2],
    );

    $restored = IterationResult::fromArray($original->toArray());

    $this->assertSame($original->getIndex(), $restored->getIndex());
    $this->assertSame($original->getInputItem(), $restored->getInputItem());
    $this->assertSame($original->getOutputResult(), $restored->getOutputResult());
    $this->assertSame($original->isSuccess(), $restored->isSuccess());
    $this->assertSame($original->getError(), $restored->getError());
    $this->assertSame($original->getMetadata(), $restored->getMetadata());
  }

  /**
   * Test with various input item types.
   *
   * @covers ::getInputItem
   * @dataProvider inputItemProvider
   */
  public function testVariousInputTypes(mixed $inputItem): void {
    $result = new IterationResult(
      index: 0,
      inputItem: $inputItem,
      outputResult: NULL,
    );

    $this->assertSame($inputItem, $result->getInputItem());
  }

  /**
   * Data provider for various input item types.
   *
   * @return array<string, array<int, mixed>>
   *   Test cases.
   */
  public static function inputItemProvider(): array {
    return [
      "string" => ["hello"],
      "integer" => [42],
      "float" => [3.14],
      "boolean" => [TRUE],
      "null" => [NULL],
      "array" => [["a", "b", "c"]],
      "associative array" => [["key" => "value"]],
      "nested array" => [["level1" => ["level2" => "deep"]]],
      "empty array" => [[]],
    ];
  }

}
