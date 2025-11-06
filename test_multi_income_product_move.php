<?php

require_once 'bootstrap/app.php';

use App\Services\StockService;
use Psr\Http\Message\ResponseInterface;

// Mock Response class for testing
class MockResponse implements Psr\Http\Message\ResponseInterface {
    public $body = '';
    public $status = 200;
    public $headers = [];

    public function getBody() {
        return new class($this->body) {
            private $content;
            public function __construct($content) { $this->content = $content; }
            public function write($data) { $this->content .= $data; }
            public function getContents() { return $this->content; }
        };
    }

    public function withStatus($code) {
        $this->status = $code;
        return $this;
    }

    public function withHeader($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getStatusCode(): int { return $this->status; }
    public function withBody($body) { return $this; }
    public function getHeaders() { return $this->headers; }
    public function hasHeader($name) { return isset($this->headers[$name]); }
    public function getHeader($name) { return $this->headers[$name] ?? []; }
    public function getHeaderLine($name) { return implode(', ', $this->getHeader($name)); }
    public function withAddedHeader($name, $value) { return $this; }
    public function withoutHeader($name) { return $this; }
    public function getProtocolVersion() { return '1.1'; }
    public function withProtocolVersion($version) { return $this; }
    public function getReasonPhrase() { return ''; }
}

echo "Testing createMultiIncomeProductMove\n\n";

// Test 1: Missing products array
echo "Test 1: Missing products array\n";
$data1 = [];
$response1 = new MockResponse();
$result1 = StockService::createMultiIncomeProductMove($response1, $data1);
echo "Status: " . $response1->status . "\n";
echo "Body: " . $response1->getBody()->getContents() . "\n\n";

// Test 2: Empty products array
echo "Test 2: Empty products array\n";
$data2 = ['products' => []];
$response2 = new MockResponse();
$result2 = StockService::createMultiIncomeProductMove($response2, $data2);
echo "Status: " . $response2->status . "\n";
echo "Body: " . $response2->getBody()->getContents() . "\n\n";

// Test 3: Missing product_id in product data
echo "Test 3: Missing product_id in product data\n";
$data3 = ['products' => [['outlet_id' => 1, 'quantity' => 5]]];
$response3 = new MockResponse();
$result3 = StockService::createMultiIncomeProductMove($response3, $data3);
echo "Status: " . $response3->status . "\n";
echo "Body: " . $response3->getBody()->getContents() . "\n\n";

// Test 4: Missing outlet_id in product data
echo "Test 4: Missing outlet_id in product data\n";
$data4 = ['products' => [['product_id' => 1, 'quantity' => 5]]];
$response4 = new MockResponse();
$result4 = StockService::createMultiIncomeProductMove($response4, $data4);
echo "Status: " . $response4->status . "\n";
echo "Body: " . $response4->getBody()->getContents() . "\n\n";

// Test 5: Invalid quantity (negative)
echo "Test 5: Invalid quantity (negative)\n";
$data5 = ['products' => [['product_id' => 1, 'outlet_id' => 1, 'quantity' => -5]]];
$response5 = new MockResponse();
$result5 = StockService::createMultiIncomeProductMove($response5, $data5);
echo "Status: " . $response5->status . "\n";
echo "Body: " . $response5->getBody()->getContents() . "\n\n";

// Test 6: Valid data (assuming product_id 1 and 2 exist)
echo "Test 6: Valid data\n";
$data6 = [
    'products' => [
        ['product_id' => 1, 'outlet_id' => 1, 'quantity' => 10, 'tanggal' => '2023-10-01', 'pic' => 'Admin', 'keterangan' => 'Restock'],
        ['product_id' => 2, 'outlet_id' => 1, 'quantity' => 5]
    ]
];
$response6 = new MockResponse();
$result6 = StockService::createMultiIncomeProductMove($response6, $data6);
echo "Status: " . $response6->status . "\n";
echo "Body: " . $response6->getBody()->getContents() . "\n\n";

echo "Testing completed.\n";
