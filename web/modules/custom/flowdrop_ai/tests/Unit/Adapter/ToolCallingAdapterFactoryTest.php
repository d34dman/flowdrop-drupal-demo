<?php

declare(strict_types=1);

namespace Drupal\Tests\flowdrop_ai\Unit\Adapter;

use Drupal\flowdrop_ai\Adapter\AnthropicToolCallingAdapter;
use Drupal\flowdrop_ai\Adapter\OpenAiToolCallingAdapter;
use Drupal\flowdrop_ai\Adapter\ToolCallingAdapterFactory;
use Drupal\flowdrop_ai\Exception\LlmApiException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ToolCallingAdapterFactory.
 *
 * @coversDefaultClass \Drupal\flowdrop_ai\Adapter\ToolCallingAdapterFactory
 * @group flowdrop_ai
 */
class ToolCallingAdapterFactoryTest extends TestCase {

  /**
   * The mock OpenAI adapter.
   *
   * @var \Drupal\flowdrop_ai\Adapter\OpenAiToolCallingAdapter|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $openAiAdapter;

  /**
   * The mock Anthropic adapter.
   *
   * @var \Drupal\flowdrop_ai\Adapter\AnthropicToolCallingAdapter|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $anthropicAdapter;

  /**
   * The factory under test.
   *
   * @var \Drupal\flowdrop_ai\Adapter\ToolCallingAdapterFactory
   */
  protected ToolCallingAdapterFactory $factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->openAiAdapter = $this->createMock(OpenAiToolCallingAdapter::class);
    $this->openAiAdapter->method('getProvider')->willReturn('openai');
    $this->openAiAdapter->method('supportsModel')->willReturnCallback(
      fn($model) => str_starts_with($model, 'gpt-')
    );
    $this->openAiAdapter->method('getAvailableModels')->willReturn([
      'gpt-4' => ['id' => 'gpt-4', 'name' => 'GPT-4', 'max_tokens' => 8192],
    ]);

    $this->anthropicAdapter = $this->createMock(AnthropicToolCallingAdapter::class);
    $this->anthropicAdapter->method('getProvider')->willReturn('anthropic');
    $this->anthropicAdapter->method('supportsModel')->willReturnCallback(
      fn($model) => str_starts_with($model, 'claude-')
    );
    $this->anthropicAdapter->method('getAvailableModels')->willReturn([
      'claude-3-sonnet' => ['id' => 'claude-3-sonnet', 'name' => 'Claude 3 Sonnet', 'max_tokens' => 4096],
    ]);

    $this->factory = new ToolCallingAdapterFactory(
      $this->openAiAdapter,
      $this->anthropicAdapter,
    );
  }

  /**
   * Test getAdapter for OpenAI model.
   *
   * @covers ::getAdapter
   */
  public function testGetAdapterOpenAi(): void {
    $adapter = $this->factory->getAdapter('gpt-4');

    $this->assertSame($this->openAiAdapter, $adapter);
  }

  /**
   * Test getAdapter for Anthropic model.
   *
   * @covers ::getAdapter
   */
  public function testGetAdapterAnthropic(): void {
    $adapter = $this->factory->getAdapter('claude-3-sonnet');

    $this->assertSame($this->anthropicAdapter, $adapter);
  }

  /**
   * Test getAdapter throws for unsupported model.
   *
   * @covers ::getAdapter
   */
  public function testGetAdapterUnsupportedModel(): void {
    $this->expectException(LlmApiException::class);
    $this->expectExceptionMessage("Model 'unsupported-model' is not available");

    $this->factory->getAdapter('unsupported-model');
  }

  /**
   * Test getAdapterByProvider for OpenAI.
   *
   * @covers ::getAdapterByProvider
   */
  public function testGetAdapterByProviderOpenAi(): void {
    $adapter = $this->factory->getAdapterByProvider('openai');

    $this->assertSame($this->openAiAdapter, $adapter);
  }

  /**
   * Test getAdapterByProvider for Anthropic.
   *
   * @covers ::getAdapterByProvider
   */
  public function testGetAdapterByProviderAnthropic(): void {
    $adapter = $this->factory->getAdapterByProvider('anthropic');

    $this->assertSame($this->anthropicAdapter, $adapter);
  }

  /**
   * Test getAdapterByProvider throws for unknown provider.
   *
   * @covers ::getAdapterByProvider
   */
  public function testGetAdapterByProviderUnknown(): void {
    $this->expectException(LlmApiException::class);
    $this->expectExceptionMessage("Provider 'unknown' is not supported");

    $this->factory->getAdapterByProvider('unknown');
  }

  /**
   * Test getAdapters returns all adapters.
   *
   * @covers ::getAdapters
   */
  public function testGetAdapters(): void {
    $adapters = $this->factory->getAdapters();

    $this->assertCount(2, $adapters);
    $this->assertArrayHasKey('openai', $adapters);
    $this->assertArrayHasKey('anthropic', $adapters);
  }

  /**
   * Test getAllModels returns models from all providers.
   *
   * @covers ::getAllModels
   */
  public function testGetAllModels(): void {
    $models = $this->factory->getAllModels();

    $this->assertArrayHasKey('gpt-4', $models);
    $this->assertArrayHasKey('claude-3-sonnet', $models);

    // Check provider is added.
    $this->assertSame('openai', $models['gpt-4']['provider']);
    $this->assertSame('anthropic', $models['claude-3-sonnet']['provider']);
  }

  /**
   * Test supportsModel for supported models.
   *
   * @covers ::supportsModel
   */
  public function testSupportsModelTrue(): void {
    $this->assertTrue($this->factory->supportsModel('gpt-4'));
    $this->assertTrue($this->factory->supportsModel('gpt-3.5-turbo'));
    $this->assertTrue($this->factory->supportsModel('claude-3-opus'));
  }

  /**
   * Test supportsModel for unsupported models.
   *
   * @covers ::supportsModel
   */
  public function testSupportsModelFalse(): void {
    $this->assertFalse($this->factory->supportsModel('llama-2'));
    $this->assertFalse($this->factory->supportsModel('unknown'));
  }

  /**
   * Test getProviderForModel for OpenAI.
   *
   * @covers ::getProviderForModel
   */
  public function testGetProviderForModelOpenAi(): void {
    $provider = $this->factory->getProviderForModel('gpt-4-turbo');

    $this->assertSame('openai', $provider);
  }

  /**
   * Test getProviderForModel for Anthropic.
   *
   * @covers ::getProviderForModel
   */
  public function testGetProviderForModelAnthropic(): void {
    $provider = $this->factory->getProviderForModel('claude-3-haiku');

    $this->assertSame('anthropic', $provider);
  }

  /**
   * Test getProviderForModel for unknown model.
   *
   * @covers ::getProviderForModel
   */
  public function testGetProviderForModelUnknown(): void {
    $provider = $this->factory->getProviderForModel('unknown-model');

    $this->assertNull($provider);
  }

}
