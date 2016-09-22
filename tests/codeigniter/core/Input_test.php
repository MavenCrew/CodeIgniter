<?php

class Input_test extends CI_TestCase
{
    public function set_up()
    {
        // Set server variable to GET as default, since this will leave unset in STDIN env
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Set config for Input class
        $this->ci_set_config('allow_get_array', true);
        $this->ci_set_config('global_xss_filtering', false);
        $this->ci_set_config('csrf_protection', false);

        $security = new Mock_Core_Security();
        $utf8 = new Mock_Core_Utf8();

        $this->input = new Mock_Core_Input($security, $utf8);
    }

    // --------------------------------------------------------------------

    public function test_get_not_exists()
    {
        $this->assertTrue($this->input->get() === []);
        $this->assertTrue($this->input->get('foo') === null);
    }

    // --------------------------------------------------------------------

    public function test_get_exist()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['foo'] = 'bar';

        $this->assertArrayHasKey('foo', $this->input->get());
        $this->assertEquals('bar', $this->input->get('foo'));
    }

    // --------------------------------------------------------------------

    public function test_get_exist_with_xss_clean()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['harm'] = "Hello, i try to <script>alert('Hack');</script> your site";

        $this->assertArrayHasKey('harm', $this->input->get());
        $this->assertEquals("Hello, i try to <script>alert('Hack');</script> your site", $this->input->get('harm'));
        $this->assertEquals("Hello, i try to [removed]alert&#40;'Hack'&#41;;[removed] your site", $this->input->get('harm', true));
    }

    // --------------------------------------------------------------------

    public function test_post_not_exists()
    {
        $this->assertTrue($this->input->post() === []);
        $this->assertTrue($this->input->post('foo') === null);
    }

    // --------------------------------------------------------------------

    public function test_post_exist()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['foo'] = 'bar';

        $this->assertArrayHasKey('foo', $this->input->post());
        $this->assertEquals('bar', $this->input->post('foo'));
    }

    // --------------------------------------------------------------------

    public function test_post_exist_with_xss_clean()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['harm'] = "Hello, i try to <script>alert('Hack');</script> your site";

        $this->assertArrayHasKey('harm', $this->input->post());
        $this->assertEquals("Hello, i try to <script>alert('Hack');</script> your site", $this->input->post('harm'));
        $this->assertEquals("Hello, i try to [removed]alert&#40;'Hack'&#41;;[removed] your site", $this->input->post('harm', true));
    }

    // --------------------------------------------------------------------

    public function test_post_get()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['foo'] = 'bar';

        $this->assertEquals('bar', $this->input->post_get('foo'));
    }

    // --------------------------------------------------------------------

    public function test_get_post()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['foo'] = 'bar';

        $this->assertEquals('bar', $this->input->get_post('foo'));
    }

    // --------------------------------------------------------------------

    public function test_cookie()
    {
        $_COOKIE['foo'] = 'bar';
        $this->assertEquals('bar', $this->input->cookie('foo'));
        $this->assertNull($this->input->cookie('bar'));
    }

    // --------------------------------------------------------------------

    public function test_server()
    {
        $this->assertEquals('GET', $this->input->server('REQUEST_METHOD'));
    }

    // --------------------------------------------------------------------

    public function test_fetch_from_array()
    {
        $data = [
            'foo'  => 'bar',
            'harm' => 'Hello, i try to <script>alert(\'Hack\');</script> your site',
        ];

        $foo = $this->input->fetch_from_array($data, 'foo');
        $harm = $this->input->fetch_from_array($data, 'harm');
        $harmless = $this->input->fetch_from_array($data, 'harm', true);

        $this->assertEquals('bar', $foo);
        $this->assertEquals("Hello, i try to <script>alert('Hack');</script> your site", $harm);
        $this->assertEquals("Hello, i try to [removed]alert&#40;'Hack'&#41;;[removed] your site", $harmless);
    }

    // --------------------------------------------------------------------

    public function test_valid_ip()
    {
        $ip_v4 = '192.18.0.1';
        $this->assertTrue($this->input->valid_ip($ip_v4));

        $ip_v6 = ['2001:0db8:0000:85a3:0000:0000:ac1f:8001', '2001:db8:0:85a3:0:0:ac1f:8001', '2001:db8:0:85a3::ac1f:8001'];
        foreach ($ip_v6 as $ip) {
            $this->assertTrue($this->input->valid_ip($ip));
        }
    }

    // --------------------------------------------------------------------

    public function test_method()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals('get', $this->input->method());
        $this->assertEquals('GET', $this->input->method(true));
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals('post', $this->input->method());
        $this->assertEquals('POST', $this->input->method(true));
    }

    // --------------------------------------------------------------------

    public function test_is_ajax_request()
    {
        $this->assertFalse($this->input->is_ajax_request());
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'test';
        $this->assertFalse($this->input->is_ajax_request());
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($this->input->is_ajax_request());
    }
}
