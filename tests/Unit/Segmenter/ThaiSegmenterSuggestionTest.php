<?php

use Farzai\ThaiWord\Contracts\DictionaryInterface;
use Farzai\ThaiWord\Contracts\SuggestionInterface;
use Farzai\ThaiWord\Exceptions\SegmentationException;
use Farzai\ThaiWord\Segmenter\ThaiSegmenter;
use Farzai\ThaiWord\Suggestions\Strategies\LevenshteinSuggestionStrategy;

describe('ThaiSegmenter with Suggestions', function () {
    beforeEach(function () {
        $this->mockDictionary = Mockery::mock(DictionaryInterface::class);
        $this->mockSuggestionStrategy = Mockery::mock(SuggestionInterface::class);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('can initialize with suggestion strategy', function () {
        $segmenter = new ThaiSegmenter(
            $this->mockDictionary,
            null,
            $this->mockSuggestionStrategy
        );

        expect($segmenter->getSuggestionStrategy())->toBe($this->mockSuggestionStrategy);
    });

    it('can set suggestion strategy', function () {
        $this->mockSuggestionStrategy
            ->shouldReceive('getThreshold')
            ->andReturn(0.6);

        $this->mockSuggestionStrategy
            ->shouldReceive('setThreshold')
            ->with(0.6);

        $segmenter = new ThaiSegmenter($this->mockDictionary);
        $segmenter->setSuggestionStrategy($this->mockSuggestionStrategy);

        expect($segmenter->getSuggestionStrategy())->toBe($this->mockSuggestionStrategy);
    });

    it('automatically initializes suggestion strategy when enabled', function () {
        $segmenter = new ThaiSegmenter(
            $this->mockDictionary,
            null,
            null,
            ['enable_suggestions' => true]
        );

        $strategy = $segmenter->getSuggestionStrategy();
        expect($strategy)->toBeInstanceOf(LevenshteinSuggestionStrategy::class);
    });

    it('can enable suggestions with default config', function () {
        $segmenter = new ThaiSegmenter($this->mockDictionary);
        $segmenter->enableSuggestions();

        expect($segmenter->getSuggestionStrategy())->toBeInstanceOf(LevenshteinSuggestionStrategy::class)
            ->and($segmenter->getConfig()['enable_suggestions'])->toBeTrue();
    });

    it('can enable suggestions with custom config', function () {
        $segmenter = new ThaiSegmenter($this->mockDictionary);
        $segmenter->enableSuggestions([
            'threshold' => 0.8,
            'max_suggestions' => 3,
        ]);

        $config = $segmenter->getConfig();
        expect($config['enable_suggestions'])->toBeTrue()
            ->and($config['suggestion_threshold'])->toBe(0.8)
            ->and($config['max_suggestions'])->toBe(3);
    });

    it('can disable suggestions', function () {
        $segmenter = new ThaiSegmenter($this->mockDictionary);
        $segmenter->enableSuggestions();
        $segmenter->disableSuggestions();

        expect($segmenter->getConfig()['enable_suggestions'])->toBeFalse();
    });

    it('throws exception when suggesting without strategy', function () {
        $segmenter = new ThaiSegmenter($this->mockDictionary);

        expect(fn () => $segmenter->suggest('test'))
            ->toThrow(SegmentationException::class, 'Suggestion feature is not enabled');
    });

    it('can suggest words with strategy', function () {
        $expectedSuggestions = [
            ['word' => 'สวัสดี', 'score' => 0.9],
            ['word' => 'สวัสด', 'score' => 0.8],
        ];

        $this->mockSuggestionStrategy
            ->shouldReceive('suggest')
            ->once()
            ->with('สวัสดุ', $this->mockDictionary, 5)
            ->andReturn($expectedSuggestions);

        $segmenter = new ThaiSegmenter(
            $this->mockDictionary,
            null,
            $this->mockSuggestionStrategy
        );

        $suggestions = $segmenter->suggest('สวัสดุ');

        expect($suggestions)->toBe($expectedSuggestions);
    });

    it('can suggest words with custom max suggestions', function () {
        $this->mockSuggestionStrategy
            ->shouldReceive('suggest')
            ->once()
            ->with('สวัสดุ', $this->mockDictionary, 3)
            ->andReturn([]);

        $segmenter = new ThaiSegmenter(
            $this->mockDictionary,
            null,
            $this->mockSuggestionStrategy
        );

        $segmenter->suggest('สวัสดุ', 3);
    });

    it('segment with suggestions returns words without suggestions when disabled', function () {
        $this->mockDictionary
            ->shouldReceive('contains')
            ->andReturn(true);

        $segmenter = new ThaiSegmenter($this->mockDictionary);

        // Mock the segment method to return test data
        $segmenterMock = Mockery::mock(ThaiSegmenter::class, [$this->mockDictionary])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $segmenterMock
            ->shouldReceive('segment')
            ->andReturn(['สวัสดี', 'ครับ']);

        $result = $segmenterMock->segmentWithSuggestions('สวัสดีครับ');

        $expected = [
            ['word' => 'สวัสดี'],
            ['word' => 'ครับ'],
        ];

        expect($result)->toBe($expected);
    });

    it('segment with suggestions includes suggestions for unknown words', function () {
        $suggestions = [
            ['word' => 'สวัสดี', 'score' => 0.9],
        ];

        $this->mockDictionary
            ->shouldReceive('contains')
            ->andReturnUsing(fn ($word) => $word !== 'ส'); // 'ส' is unknown

        $this->mockSuggestionStrategy
            ->shouldReceive('suggest')
            ->with('ส', $this->mockDictionary, 5)
            ->andReturn($suggestions);

        $segmenter = new ThaiSegmenter(
            $this->mockDictionary,
            null,
            $this->mockSuggestionStrategy,
            ['enable_suggestions' => true]
        );

        // Mock the segment method
        $segmenterMock = Mockery::mock(ThaiSegmenter::class, [
            $this->mockDictionary,
            null,
            $this->mockSuggestionStrategy,
            ['enable_suggestions' => true],
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $segmenterMock
            ->shouldReceive('segment')
            ->andReturn(['สวัสดี', 'ส', 'ครับ']);

        $result = $segmenterMock->segmentWithSuggestions('test');

        $expected = [
            ['word' => 'สวัสดี'],
            ['word' => 'ส', 'suggestions' => $suggestions],
            ['word' => 'ครับ'],
        ];

        expect($result)->toBe($expected);
    });

    it('segment with suggestions does not suggest for multi character words', function () {
        $this->mockDictionary
            ->shouldReceive('contains')
            ->andReturn(false); // All words are unknown

        $this->mockSuggestionStrategy
            ->shouldNotReceive('suggest');

        $segmenter = new ThaiSegmenter(
            $this->mockDictionary,
            null,
            $this->mockSuggestionStrategy,
            ['enable_suggestions' => true]
        );

        // Mock the segment method
        $segmenterMock = Mockery::mock(ThaiSegmenter::class, [
            $this->mockDictionary,
            null,
            $this->mockSuggestionStrategy,
            ['enable_suggestions' => true],
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $segmenterMock
            ->shouldReceive('segment')
            ->andReturn(['สวัสดี', 'ครับ']); // Multi-character words

        $result = $segmenterMock->segmentWithSuggestions('test');

        $expected = [
            ['word' => 'สวัสดี'],
            ['word' => 'ครับ'],
        ];

        expect($result)->toBe($expected);
    });

    it('updates configuration when setting suggestion strategy', function () {
        $segmenter = new ThaiSegmenter($this->mockDictionary);

        // Mock strategy returns default threshold (0.6), so setThreshold should be called
        $this->mockSuggestionStrategy
            ->shouldReceive('getThreshold')
            ->andReturn(0.6);

        $this->mockSuggestionStrategy
            ->shouldReceive('setThreshold')
            ->once()
            ->with(0.6);

        $segmenter->setSuggestionStrategy($this->mockSuggestionStrategy);

        expect($segmenter->getConfig()['enable_suggestions'])->toBeTrue();
    });

    it('disables suggestions when setting null strategy', function () {
        $segmenter = new ThaiSegmenter(
            $this->mockDictionary,
            null,
            $this->mockSuggestionStrategy
        );

        $segmenter->setSuggestionStrategy(null);

        expect($segmenter->getConfig()['enable_suggestions'])->toBeFalse()
            ->and($segmenter->getSuggestionStrategy())->toBeNull();
    });
});
