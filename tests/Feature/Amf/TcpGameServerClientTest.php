<?php

namespace Tests\Feature\Amf;

use App\Infrastructure\GameServer\TcpGameServerClient;
use App\Models\GameServer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TcpGameServerClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refuses_to_send_without_a_server_and_non_empty_secret(): void
    {
        $client = $this->app->make(TcpGameServerClient::class);

        $this->assertFalse($client->send('testConnection'));

        GameServer::query()->create([
            'id' => 1,
            'name' => 'No Secret',
            'player_count' => 0,
            'url' => '127.0.0.1',
            'port' => 9595,
            'goldpanda' => false,
            'secret_key' => '',
        ]);

        $this->assertFalse($client->send('testConnection'));
    }

    public function test_it_writes_the_exact_authenticated_internal_command_to_tcp(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for the loopback TCP contract test.');
        }

        $errno = 0;
        $error = '';
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $this->assertIsResource($server, $error);
        $address = stream_socket_get_name($server, false);
        $this->assertIsString($address);
        $port = (int) substr($address, strrpos($address, ':') + 1);
        $capture = tempnam(sys_get_temp_dir(), 'panfu-tcp-contract-');
        $this->assertIsString($capture);

        $pid = pcntl_fork();
        $this->assertGreaterThanOrEqual(0, $pid);
        if ($pid === 0) {
            $connection = stream_socket_accept($server, 5);
            $payload = is_resource($connection) ? stream_get_contents($connection) : '';
            if (is_resource($connection)) {
                fclose($connection);
            }
            file_put_contents($capture, $payload);
            fclose($server);
            exit(0);
        }

        try {
            GameServer::query()->create([
                'id' => 1,
                'name' => 'Loopback',
                'player_count' => 0,
                'url' => '127.0.0.1',
                'port' => $port,
                'goldpanda' => false,
                'secret_key' => 'test-secret',
            ]);
            config([
                'panfu.game_server.internal_host' => '127.0.0.1',
                'panfu.game_server.internal_port' => $port,
            ]);

            $this->assertTrue($this->app->make(TcpGameServerClient::class)->send(
                'updateBuddyStatus',
                7,
                9,
                1,
            ));
            pcntl_waitpid($pid, $status);
            $this->assertTrue(pcntl_wifexited($status));
            $this->assertSame(0, pcntl_wexitstatus($status));
            $this->assertSame(
                '900;test-secret;updateBuddyStatus;7;9;1|',
                file_get_contents($capture),
            );
        } finally {
            fclose($server);
            @unlink($capture);
        }
    }
}
