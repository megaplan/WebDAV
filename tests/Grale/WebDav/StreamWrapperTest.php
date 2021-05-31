<?php
/**
 * This file is part of the WebDav package.
 *
 * (c) Geoffroy Letournel <geoffroy.letournel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grale\WebDav;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Stream;

/**
 * @covers Grale\WebDav\StreamWrapper
 */
class StreamWrapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $client;

    public function setUp(): void
    {
        $httpClient = $this->createMock(Client::class);
        $wdavClient = $this->createMock(WebDavClient::class);

        $wdavClient->method('getHttpClient')->willReturn($httpClient);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'Hello World!');
        rewind($stream);

        $stream = new Stream($stream);
        $wdavClient->method('getStream')->willReturn($stream);

        $propfind = MultiStatus::parse(
            $wdavClient,
            file_get_contents(__DIR__ . '/../../fixtures/streamwrapper.opendir.xml')
        );

        $wdavClient->method('setThrowExceptions')->willReturn($this->client);
        $wdavClient->method('propfind')->willReturn($propfind);

        $this->client = $wdavClient;

        StreamWrapper::register(null, $this->client);
    }

    public function tearDown(): void
    {
        foreach (array('webdav', 'webdavs') as $wrapper) {
            if (in_array($wrapper, stream_get_wrappers())) {
                stream_wrapper_unregister($wrapper);
            }
        }
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage A stream wrapper already exists for the 'webdav' protocol
     */
    public function testRegisteringAlreadyExistingStreamWrapper()
    {
        StreamWrapper::register();
    }

    public function testRegisteringTheStreamWrapper()
    {
        $this->assertContains('webdav', stream_get_wrappers());
        $this->assertContains('webdavs', stream_get_wrappers());
    }

    public function testUnregisteringTheStreamWrapper()
    {
        $result = StreamWrapper::unregister();
        $this->assertTrue($result);
        $this->assertNotContains('webdav', stream_get_wrappers());
        $this->assertNotContains('webdavs', stream_get_wrappers());
    }

    /**
     * @dataProvider getUnsupportedModes
     */
    public function testStreamOpenWithUnsupportedPlusMode($mode)
    {
        $result = @fopen('webdav://test', $mode);
        $this->assertFalse($result);
    }

    /**
     * @dataProvider getUnsupportedModes
     *
     *
     */
    public function testStreamOpenWithUnsupportedModeTriggersError($mode)
    {
        $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        $this->expectExceptionMessage("ailed to open stream");
        fopen('webdav://test', $mode);
    }

    public function getUnsupportedModes()
    {
        return array(
            array('rw'),
            array('r+'),
            array('c+'),
            array('c'),
            array('plop')
        );
    }

    public function testTraversingDirectoryWithPhpInternalFunctions()
    {
        $dir = 'webdav://www.foo.bar/container';

        $dh = opendir($dir);
        $this->assertIsResource($dh, "Failed asserting that 'opendir' returned a 'resource'");

        $files = array();

        while (($file = readdir($dh)) !== false) {
            $files[] = $file;
        }

        $this->assertNotContains('container', $files);
        $this->assertContains('othercontainer', $files);
        $this->assertContains('front.html', $files);

        rewinddir($dh);

        $this->assertEquals('othercontainer', readdir($dh));

        closedir($dh);
    }

    public function testTraversingDirectoryWithScandir()
    {
        $files = scandir('webdav://www.foo.bar/container');

        $this->assertNotContains('container', $files);
        $this->assertContains('othercontainer', $files);
        $this->assertContains('front.html', $files);
    }

    public function testTraversingDirectoryWithDir()
    {
        $dir = dir('webdav://www.foo.bar/container');

        $this->assertInstanceOf('\Directory', $dir);

        $files = array();

        while (($file = $dir->read()) !== false) {
            $files[] = $file;
        }

        $this->assertNotContains('container', $files);
        $this->assertContains('othercontainer', $files);
        $this->assertContains('front.html', $files);

        $dir->close();
    }

    public function testWritingWithReadOnlyMode()
    {
        $stream = fopen('webdav://www.foo.bar', 'r');
        $bytes  = fwrite($stream, 'Data to write');
        $this->assertEquals(0, $bytes);
    }

    public function testUploadingData()
    {
        $stream = fopen('webdav://www.foo.bar', 'w');
        fwrite($stream, 'Hello World!');
        fclose($stream);
        $this->assertTrue(true);
    }

    public function testReadingWithWriteOnlyMode()
    {
        $stream = fopen('webdav://www.foo.bar', 'wb');
        $data = fread($stream, 1024);
        $this->assertEmpty($data);
    }

    public function testUnlink()
    {
        $this->client->expects($this->once())
                     ->method('delete')
                     ->willReturn(true);

        $result = unlink('webdav://www.foo.bar/front.html');
        $this->assertTrue($result);
    }

    public function testRename()
    {
        $this->client->expects($this->once())
                     ->method('move')
                     ->will($this->returnValue(true));

        $result = rename('webdav://www.foo.bar/front.html', 'webdav://www.foo.bar/home.html');
        $this->assertTrue($result);
    }

    public function testMakeDirectory()
    {
        $this->client->expects($this->once())
                     ->method('mkcol')
                     ->will($this->returnValue(true));

        $result = mkdir('webdav://www.foo.bar/newcontainer');
        $this->assertTrue($result);
    }

    public function testMakeDirectoryRecursively()
    {
        $this->expectExceptionMessage("WebDAV stream wrapper does not allow to create directories recursively");
        $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        $result = mkdir('webdav://www.foo.bar/newcontainer', 0777, true);
        $this->assertFalse($result);
    }

    public function testDeleteDirectory()
    {
        $this->client->expects($this->once())
                     ->method('delete')
                     ->willReturn(true);

        $result = rmdir('webdav://www.foo.bar/newcontainer');
        $this->assertTrue($result);
    }

    public function testCreateExclusiveLock()
    {
        $lock = new Lock(Lock::EXCLUSIVE);
        $lock->setToken('opaquelocktoken:e71d4fae-5dec-22d6-fea5-00a0c91e6be4');

        $this->client->expects($this->once())
                     ->method('createLock')
                     ->with(
                         $this->equalTo('http://www.foo.bar/front.html'),
                         $this->anything()
                     )
                     ->willReturn($lock);

        $stream = fopen('webdav://www.foo.bar/front.html', 'w');
        $result = flock($stream, LOCK_EX);

        $this->assertTrue($result);
    }

    public function testCreateSharedLock()
    {
        $lock = new Lock(Lock::SHARED);
        $lock->setToken('opaquelocktoken:e71d4fae-5dec-22d6-fea5-00a0c91e6be4');

        $this->client->expects($this->once())
                     ->method('createLock')
                     ->with(
                         $this->equalTo('http://www.foo.bar/front.html'),
                         $this->anything()
                     )
                     ->willReturn($lock);

        $stream = fopen('webdav://www.foo.bar/front.html', 'w');
        $result = flock($stream, LOCK_SH);

        $this->assertTrue($result);
    }

    public function testRefreshLock()
    {
        $fd = fopen('webdav://www.foo.bar/front.html', 'w');

        $lock = new Lock(Lock::EXCLUSIVE);
        $lock->setToken('opaquelocktoken:e71d4fae-5dec-22d6-fea5-00a0c91e6be4');

        $this->client->expects($this->once())->method('createLock')->willReturn($lock);

        $this->client->expects($this->once())
                     ->method('refreshLock')
                     ->with(
                         $this->equalTo('http://www.foo.bar/front.html'),
                         $this->equalTo($lock->getToken()),
                         $this->anything()
                     )
                     ->willReturn($lock);

        flock($fd, LOCK_EX);
        $result = flock($fd, LOCK_EX);

        $this->assertTrue($result);
    }

    public function testReleaseLock()
    {
        $lock = new Lock();
        $lock->setToken('opaquelocktoken:e71d4fae-5dec-22d6-fea5-00a0c91e6be4');

        $this->client->expects($this->once())->method('createLock')->willReturn($lock);

        $fd = fopen('webdav://www.foo.bar/front.html', 'w');
        flock($fd, LOCK_EX);

        $this->client->expects($this->once())
                     ->method('releaseLock')
                     ->with(
                         $this->equalTo('http://www.foo.bar/front.html'),
                         $this->equalTo($lock->getToken())
                     )
                     ->willReturn(true);

        $result = flock($fd, LOCK_UN);

        $this->assertTrue($result);
    }

    public function testFstat()
    {
        $fh = fopen('webdav://www.foo.bar/front.html', 'r');
        $stat = fstat($fh);

        $this->assertEquals(strlen('Hello World!'), $stat[7]);
        $this->assertEquals(strlen('Hello World!'), $stat['size']);
    }

    public function testIsDir()
    {
        $result = is_dir('webdav://www.foo.bar/container');
        $this->assertTrue($result);
    }
}
