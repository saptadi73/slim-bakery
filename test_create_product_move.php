<?php

require_once 'bootstrap/app.php';

use App\Services\StockService;
use Psr\Http\Message\ResponseInterface;

// Mock Response class for testing
class MockResponse implements Psr\Http\Message\ResponseInterface {
    public $body = '';
    public $status = 200;
    public $headers = [];

    public function getBody(): Psr\Http\Message\StreamInterface {
        return new class($this->body) implements Psr\Http\Message\StreamInterface {
            private $content;
            public function __construct($content) { $this->content = $content; }
            public function write(string $string): int { $this->content .= $string; return strlen($string); }
            public function getContents(): string { return $this->content; }
            public function __toString() { return $this->content; }
            public function close(): void {}
            public function detach() { return null; }
            public function getSize(): ?int { return strlen($this->content); }
            public function tell(): int { return 0; }
            public function eof(): bool { return false; }
            public function isSeekable(): bool { return false; }
            public function seek(int $offset, int $whence = SEEK_SET): void {}
            public function rewind(): void {}
            public function isWritable(): bool { return true; }
            public function isReadable(): bool { return true; }
            public function read(int $length): string { return substr($this->content, 0, $length); }
            public function getMetadata($key = null) { return null; }
        };
    }

    public function withStatus(int $code, string $reasonPhrase = ''): Psr\Http\Message\ResponseInterface {
        $this->status = $code;
        return $this;
    }

    public function withHeader(string $name, $value): Psr\Http\Message\MessageInterface {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getStatusCode(): int { return $this->status; }
    public function withBody(Psr\Http\Message\StreamInterface $body): Psr\Http\Message\MessageInterface { return $this; }
    public function getHeaders(): array { return $this->headers; }
    public function hasHeader(string $name): bool { return isset($this->headers[$name]); }
    public function getHeader(string $name): array { return $this->headers[$name] ?? []; }
    public function getHeaderLine(string $name): string { return implode(', ', $this->getHeader($name)); }
    public function withAddedHeader(string $name, $value): Psr\Http\Message\MessageInterface { return $this; }
    public function withoutHeader(string $name): Psr\Http\Message\MessageInterface { return $this; }
    public function getProtocolVersion(): string { return '1.1'; }
    public function withProtocolVersion(string $version): Psr\Http\Message\MessageInterface { return $this; }
    public function getReasonPhrase(): string { return ''; }
}

echo "Testing createProductMoving\n\n";

// Test 1: Missing required fields
echo "Test 1: Missing required fields\n";
$data1 = [];
$response1 = new MockResponse();
$result1 = StockService::createProductMoving($response1, $data1);
echo "Status: " . $response1->status . "\n";
echo "Body: " . $response1->getBody()->getContents() . "\n\n";

// Test 2: Invalid outlet_id (non-numeric)
echo "Test 2: Invalid outlet_id (non-numeric)\n";
$data2 = ['product_id' => 1, 'type' => 'income', 'quantity' => 10, 'outlet_id' => 'abc'];
$response2 = new MockResponse();
$result2 = StockService::createProductMoving($response2, $data2);
echo "Status: " . $response2->status . "\n";
echo "Body: " . $response2->getBody()->getContents() . "\n\n";

// Test 3: Invalid outlet_id (negative)
echo "Test 3: Invalid outlet_id (negative)\n";
$data3 = ['product_id' => 1, 'type' => 'income', 'quantity' => 10, 'outlet_id' => -1];
$response3 = new MockResponse();
$result3 = StockService::createProductMoving($response3, $data3);
echo "Status: " . $response3->status . "\n";
echo "Body: " . $response3->getBody()->getContents() . "\n\n";

// Test 4: Invalid type
echo "Test 4: Invalid type\n";
$data4 = ['product_id' => 1, 'type' => 'invalid', 'quantity' => 10, 'outlet_id' => 1];
$response4 = new MockResponse();
$result4 = StockService::createProductMoving($response4, $data4);
echo "Status: " . $response4->status . "\n";
echo "Body: " . $response4->getBody()->getContents() . "\n\n";

// Test 5: Invalid quantity (non-numeric)
echo "Test 5: Invalid quantity (non-numeric)\n";
$data5 = ['product_id' => 1, 'type' => 'income', 'quantity' => 'abc', 'outlet_id' => 1];
$response5 = new MockResponse();
$result5 = StockService::createProductMoving($response5, $data5);
echo "Status: " . $response5->status . "\n";
echo "Body: " . $response5->getBody()->getContents() . "\n\n";

// Test 6: Invalid quantity (negative)
echo "Test 6: Invalid quantity (negative)\n";
$data6 = ['product_id' => 1, 'type' => 'income', 'quantity' => -10, 'outlet_id' => 1];
$response6 = new MockResponse();
$result6 = StockService::createProductMoving($response6, $data6);
echo "Status: " . $response6->status . "\n";
echo "Body: " . $response6->getBody()->getContents() . "\n\n";

// Test 7: Valid income data
echo "Test 7: Valid income data\n";
$data7 = ['product_id' => 1, 'type' => 'income', 'quantity' => 10, 'outlet_id' => 1];
$response7 = new MockResponse();
$result7 = StockService::createProductMoving($response7, $data7);
echo "Status: " . $response7->status . "\n";
echo "Body: " . $response7->getBody()->getContents() . "\n\n";

// Test 8: Valid outcome data
echo "Test 8: Valid outcome data\n";
$data8 = ['product_id' => 1, 'type' => 'outcome', 'quantity' => 5, 'outlet_id' => 1];
$response8 = new MockResponse();
$result8 = StockService::createProductMoving($response8, $data8);
echo "Status: " . $response8->status . "\n";
echo "Body: " . $response8->getBody()->getContents() . "\n\n";

echo "Testing completed.\n";
