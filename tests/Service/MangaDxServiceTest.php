<?php

namespace App\Tests\Service;

use App\Service\MangaDxService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Psr\Log\LoggerInterface;

class MangaDxServiceTest extends TestCase
{
    private MangaDxService $mangaDxService;
    private MockHttpClient $httpClient;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mangaDxService = new MangaDxService($this->httpClient, $this->logger);
    }

    public function testGetPopularManga(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'data' => [
                [
                    'id' => '1',
                    'attributes' => [
                        'title' => ['en' => 'Test Manga'],
                        'description' => ['en' => 'Test Description'],
                        'status' => 'ongoing'
                    ]
                ]
            ]
        ]));
        $this->httpClient->setResponseFactory($mockResponse);
        $result = $this->mangaDxService->getPopularManga(1, 0);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('1', $result[0]['id']);
    }

    public function testGetMangaById(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'data' => [
                'id' => '1',
                'attributes' => [
                    'title' => ['en' => 'Test Manga'],
                    'description' => ['en' => 'Test Description'],
                    'status' => 'ongoing'
                ]
            ]
        ]));
        $this->httpClient->setResponseFactory($mockResponse);
        $result = $this->mangaDxService->getMangaById('1');
        $this->assertIsArray($result);
        $this->assertEquals('1', $result['id']);
    }

    public function testSearchManga(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'data' => [
                [
                    'id' => '1',
                    'attributes' => [
                        'title' => ['en' => 'Test Manga'],
                        'description' => ['en' => 'Test Description'],
                        'status' => 'ongoing'
                    ]
                ]
            ]
        ]));
        $this->httpClient->setResponseFactory($mockResponse);
        $result = $this->mangaDxService->searchManga('test');
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('1', $result[0]['id']);
    }

    public function testGetMangaChapters(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'data' => [
                [
                    'id' => '1',
                    'attributes' => [
                        'title' => 'Chapter 1',
                        'chapter' => '1',
                        'pages' => 20
                    ]
                ]
            ]
        ]));
        $this->httpClient->setResponseFactory($mockResponse);
        $result = $this->mangaDxService->getMangaChapters('1');
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('1', $result[0]['id']);
    }
} 