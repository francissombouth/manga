<?php

namespace App\Tests\Service;

use App\Service\CoverService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CoverServiceTest extends TestCase
{
    private CoverService $coverService;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private ParameterBagInterface $params;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        
        $this->coverService = new CoverService($this->httpClient, $this->logger, $this->params);
    }

    public function testSearchAndDownloadCoverSuccess(): void
    {
        // Mock de la réponse Google Books API
        $googleResponse = $this->createMock(ResponseInterface::class);
        $googleResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'volumeInfo' => [
                        'title' => 'Test Book',
                        'authors' => ['Test Author'],
                        'imageLinks' => [
                            'large' => 'https://example.com/large.jpg'
                        ]
                    ]
                ]
            ]
        ]);

        // Mock de la réponse de téléchargement d'image
        $imageResponse = $this->createMock(ResponseInterface::class);
        $imageResponse->method('getStatusCode')->willReturn(200);
        $imageResponse->method('getContent')->willReturn('fake-image-content');

        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($googleResponse, $imageResponse);

        $this->params->method('get')->willReturn('/tmp');

        $result = $this->coverService->searchAndDownloadCover('Test Book', 'Test Author');
        
        $this->assertStringStartsWith('/uploads/covers/', $result);
    }

    public function testSearchAndDownloadCoverNoResults(): void
    {
        $googleResponse = $this->createMock(ResponseInterface::class);
        $googleResponse->method('toArray')->willReturn(['items' => []]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($googleResponse);

        $this->logger->expects($this->once())->method('info');

        $result = $this->coverService->searchAndDownloadCover('Non Existent Book');
        
        $this->assertNull($result);
    }

    public function testSearchAndDownloadCoverNoImageLinks(): void
    {
        $googleResponse = $this->createMock(ResponseInterface::class);
        $googleResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'volumeInfo' => [
                        'title' => 'Test Book',
                        'authors' => ['Test Author']
                        // Pas d'imageLinks
                    ]
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($googleResponse);

        $this->logger->expects($this->once())->method('info');

        $result = $this->coverService->searchAndDownloadCover('Test Book', 'Test Author');
        
        $this->assertNull($result);
    }

    public function testSearchAndDownloadCoverHttpError(): void
    {
        $googleResponse = $this->createMock(ResponseInterface::class);
        $googleResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'volumeInfo' => [
                        'title' => 'Test Book',
                        'imageLinks' => ['large' => 'https://example.com/large.jpg']
                    ]
                ]
            ]
        ]);

        $imageResponse = $this->createMock(ResponseInterface::class);
        $imageResponse->method('getStatusCode')->willReturn(404);

        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($googleResponse, $imageResponse);

        // Mock le paramètre pour éviter les problèmes de permission
        $this->params->method('get')->willReturn('/tmp');

        $this->logger->expects($this->once())->method('error');

        $result = $this->coverService->searchAndDownloadCover('Test Book');
        
        $this->assertNull($result);
    }

    public function testSearchAndDownloadCoverException(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'));

        $this->logger->expects($this->once())->method('error');

        $result = $this->coverService->searchAndDownloadCover('Test Book');
        
        $this->assertNull($result);
    }

    public function testTitleMatchesExact(): void
    {
        $this->assertTrue($this->invokePrivateMethod('titleMatches', ['Test Book', 'Test Book']));
    }

    public function testTitleMatchesCaseInsensitive(): void
    {
        $this->assertTrue($this->invokePrivateMethod('titleMatches', ['test book', 'TEST BOOK']));
    }

    public function testTitleMatchesPartial(): void
    {
        $this->assertTrue($this->invokePrivateMethod('titleMatches', ['Test', 'Test Book']));
        $this->assertTrue($this->invokePrivateMethod('titleMatches', ['Test Book', 'Test']));
    }

    public function testTitleMatchesSimilar(): void
    {
        $this->assertTrue($this->invokePrivateMethod('titleMatches', ['Test Book', 'Test Bok']));
    }

    public function testTitleMatchesNoMatch(): void
    {
        $this->assertFalse($this->invokePrivateMethod('titleMatches', ['Test Book', 'Different Book']));
    }

    public function testGetImageExtensionFromMimeType(): void
    {
        $jpegContent = "\xFF\xD8\xFF"; // JPEG header
        $extension = $this->invokePrivateMethod('getImageExtension', ['https://example.com/image.jpg', $jpegContent]);
        $this->assertEquals('jpg', $extension);
    }

    public function testGetImageExtensionFromUrl(): void
    {
        $extension = $this->invokePrivateMethod('getImageExtension', ['https://example.com/image.png', 'fake-content']);
        $this->assertEquals('png', $extension);
    }

    public function testGetImageExtensionFallback(): void
    {
        $extension = $this->invokePrivateMethod('getImageExtension', ['https://example.com/image', 'fake-content']);
        $this->assertEquals('jpg', $extension);
    }

    public function testGenerateFilename(): void
    {
        $filename = $this->invokePrivateMethod('generateFilename', ['Test Book Title!']);
        $this->assertMatchesRegularExpression('/^Test_Book_Title(_[0-9]+)?$/', $filename);
    }

    public function testGenerateFilenameWithSpecialChars(): void
    {
        $filename = $this->invokePrivateMethod('generateFilename', ['Test@Book#Title$']);
        $this->assertMatchesRegularExpression('/^Test_Book_Title(_[0-9]+)?$/', $filename);
    }

    public function testGenerateFilenameWithMultipleUnderscores(): void
    {
        $filename = $this->invokePrivateMethod('generateFilename', ['Test   Book    Title']);
        $this->assertMatchesRegularExpression('/^Test_Book_Title(_[0-9]+)?$/', $filename);
    }

    public function testGetPlaceholderUrl(): void
    {
        $placeholderUrl = $this->coverService->getPlaceholderUrl();
        $this->assertEquals('/images/placeholder-book.jpg', $placeholderUrl);
    }

    public function testDeleteCoverSuccess(): void
    {
        // On force le chemin projet vers /tmp
        $this->params->method('get')->willReturn('/tmp');
        $relativePath = '/uploads/covers/test_cover_' . uniqid() . '.jpg';
        $fullPath = '/tmp/public' . $relativePath;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($fullPath, 'test content');
        $this->assertFileExists($fullPath);
        $result = $this->coverService->deleteCover($relativePath);
        $this->assertTrue($result);
        $this->assertFileDoesNotExist($fullPath);
    }

    public function testDeleteCoverFileNotExists(): void
    {
        $result = $this->coverService->deleteCover('/non/existent/file.jpg');
        
        $this->assertFalse($result);
    }

    public function testSearchAndDownloadCoverWithDifferentImageSizes(): void
    {
        $googleResponse = $this->createMock(ResponseInterface::class);
        $googleResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'volumeInfo' => [
                        'title' => 'Test Book',
                        'imageLinks' => [
                            'small' => 'https://example.com/small.jpg',
                            'thumbnail' => 'https://example.com/thumb.jpg'
                        ]
                    ]
                ]
            ]
        ]);

        $imageResponse = $this->createMock(ResponseInterface::class);
        $imageResponse->method('getStatusCode')->willReturn(200);
        $imageResponse->method('getContent')->willReturn('fake-image-content');

        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($googleResponse, $imageResponse);

        $this->params->method('get')->willReturn('/tmp');

        $result = $this->coverService->searchAndDownloadCover('Test Book');
        
        $this->assertStringStartsWith('/uploads/covers/', $result);
    }

    public function testSearchAndDownloadCoverWithAuthor(): void
    {
        $googleResponse = $this->createMock(ResponseInterface::class);
        $googleResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'volumeInfo' => [
                        'title' => 'Test Book',
                        'authors' => ['Test Author'],
                        'imageLinks' => ['large' => 'https://example.com/large.jpg']
                    ]
                ]
            ]
        ]);

        $imageResponse = $this->createMock(ResponseInterface::class);
        $imageResponse->method('getStatusCode')->willReturn(200);
        $imageResponse->method('getContent')->willReturn('fake-image-content');

        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($googleResponse, $imageResponse);

        $this->params->method('get')->willReturn('/tmp');

        $result = $this->coverService->searchAndDownloadCover('Test Book', 'Test Author');
        
        $this->assertStringStartsWith('/uploads/covers/', $result);
    }

    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($this->coverService);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($this->coverService, $parameters);
    }
} 