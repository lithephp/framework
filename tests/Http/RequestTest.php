<?php

use Lithe\Base\Upload;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();

        $url = '/produtos/eletronicos/celulares';

        // Set up the superglobals to simulate a request environment
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = $url;
        $_SERVER['SCRIPT_NAME'] = '/projeto/index.php';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $_COOKIE['test_cookie'] = 'cookie_value';

        $_POST['name'] = 'Test';
        $_POST['email'] = 'test@example.com';
        $_POST['age'] = 30;

        $_FILES['file'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => '/tmp/phpYzdqkD',
            'error' => 0,
            'size' => 123
        ];

        $_FILES['files'] = [
            'name' => ['file1.txt', 'file2.txt', 'file3.txt'],
            'type' => ['text/plain', 'text/plain', 'text/plain'],
            'tmp_name' => ['/tmp/phpYzdqkD1', '/tmp/phpYzdqkD2', '/tmp/phpYzdqkD3'],
            'error' => [0, 0, 0],
            'size' => [12345, 67890, 23456]
        ];

        $parameters = [];
        // Assuming Request.php returns an instance of Request
        $this->request = include __DIR__ . '/../../src/Http/Request.php';
    }

    public function testGetMethod()
    {
        $this->assertEquals('POST', $this->request->method()); // Check the HTTP method
    }

    public function testGetIp()
    {
        $this->assertEquals('127.0.0.1', $this->request->ip); // Check the IP address
    }

    public function testGetUrl()
    {
        $this->assertEquals('/produtos/eletronicos/celulares', $this->request->url); // Check the URL without the script name
    }

    public function testGetHeaders()
    {
        $this->assertArrayHasKey('X-Forwarded-For', $this->request->headers); // Check headers
        $this->assertEquals('127.0.0.1', $this->request->headers['X-Forwarded-For']);
    }

    public function testFilterEmail()
    {
        $result = $this->request->filter('email', 'email'); // Filter for email
        $this->assertEquals('test@example.com', $result);
    }

    public function testFilterInt()
    {
        $result = $this->request->filter('age', 'int'); // Filter for integer
        $this->assertEquals(30, $result);
    }

    public function testGetBody()
    {
        $this->assertEquals('Test', $this->request->body->name); // Check body data
    }

    public function testBodyExcludesKeys()
    {
        $result = $this->request->body(null, ['age']); // Exclude 'age'
        $expected = (object)[
            'name' => 'Test',
            'email' => 'test@example.com'
        ];
        $this->assertEquals($expected, $result);
    }

    public function testBodyIncludesAndExcludesKeys()
    {
        $result = $this->request->body(['name', 'email'], ['age']); // Include 'name' and 'email', exclude 'age'
        $expected = (object)[
            'name' => 'Test',
            'email' => 'test@example.com'
        ];
        $this->assertEquals($expected, $result);
    }

    public function testIsAjax()
    {
        $this->assertTrue($this->request->isAjax()); // Check if request is an Ajax request
    }

    public function testGetCookie()
    {
        $this->assertEquals('cookie_value', $this->request->cookie('test_cookie')); // Check cookie value
        $this->assertNull($this->request->cookie('non_existent_cookie')); // Check non-existent cookie
    }

    public function testWantsJson()
    {
        $this->assertTrue($this->request->wantsJson()); // Check if request accepts JSON
    }

    public function testSecure()
    {
        $this->assertTrue($this->request->secure()); // Check if request is secure (HTTPS)
    }

    public function testProtocol()
    {
        $this->assertEquals('https', $this->request->protocol()); // Check request protocol
    }

    public function testInput()
    {
        $this->assertEquals('Test', $this->request->input('name')); // Check POST input data
        $this->assertNull($this->request->input('nonexistent')); // Check non-existent input
    }

    public function testHas()
    {
        $this->assertTrue($this->request->has('name')); // Check if input exists
        $this->assertFalse($this->request->has('nonexistent')); // Check non-existent input
    }

    public function testIsMethod()
    {
        $this->assertTrue($this->request->isMethod('POST')); // Check request method
        $this->assertFalse($this->request->isMethod('GET')); // Check non-matching method
    }

    public function testValidate()
    {
        $rules = ['name' => 'required'];
        $validation = $this->request->validate($rules); // Validate request data

        $this->assertTrue($validation->passed()); // Check validation result
        $this->assertEmpty($validation->errors()); // Check if there are any errors
    }

    public function testFileMethod()
    {
        $upload = $this->request->file('file'); // Get file from request

        $this->assertInstanceOf(\Lithe\Base\Upload::class, $upload); // Check file instance
        $this->assertTrue($upload->isUploaded()); // Check if file is uploaded
        $this->assertEquals('text/plain', $upload->getMimeType()); // Check MIME type
        $this->assertEquals(123, $upload->getSize()); // Check file size
    }

    public function testFileMethodWithMultipleFiles()
    {
        $files = $this->request->file('files');

        // Verifica se a função retorna um array de arquivos
        $this->assertIsArray($files);
        $this->assertCount(3, $files);

        // Verifica cada arquivo individualmente
        $file1 = $files[0];
        $this->assertInstanceOf(Upload::class, $file1);
        $this->assertTrue($file1->isUploaded());
        $this->assertEquals('text/plain', $file1->getMimeType());
        $this->assertEquals(12345, $file1->getSize());

        $file2 = $files[1];
        $this->assertInstanceOf(Upload::class, $file2);
        $this->assertTrue($file2->isUploaded());
        $this->assertEquals('text/plain', $file2->getMimeType());
        $this->assertEquals(67890, $file2->getSize());

        $file3 = $files[2];
        $this->assertInstanceOf(Upload::class, $file3);
        $this->assertTrue($file3->isUploaded());
        $this->assertEquals('text/plain', $file3->getMimeType());
        $this->assertEquals(23456, $file3->getSize());
    }

    public function testIs()
    {
        $this->assertTrue($this->request->is('/produtos/eletronicos/celulares'), 'A URL deveria corresponder exatamente.');
        $this->assertTrue($this->request->is('/produtos/*'), 'A URL deveria corresponder ao padrão /produtos/*.');
        $this->assertTrue($this->request->is('/produtos/eletronicos/*'), 'A URL deveria corresponder ao padrão /produtos/eletronicos/*.');
        $this->assertFalse($this->request->is('/servicos/*'), 'A URL não deveria corresponder ao padrão /servicos/*.');
    }
}
