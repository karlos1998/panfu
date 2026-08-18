<?php

namespace Tests\Feature\Panfu;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Imagick;
use Tests\TestCase;

class PlayercardTest extends TestCase
{
    use RefreshDatabase;

    public function test_playercard_is_rendered_as_the_native_panfu_canvas(): void
    {
        $user = User::factory()->create(['name' => 'Karlos']);

        $response = $this->get('/playercard?user=Karlos')->assertOk()->assertHeader('Content-Type', 'image/png');
        $image = new Imagick($response->baseResponse->getFile()->getPathname());

        $this->assertSame(230, $image->getImageWidth());
        $this->assertSame(240, $image->getImageHeight());
        $this->assertSame([65, 44], $this->opaqueOrigin($image));
    }

    public function test_unknown_user_gets_the_default_playercard(): void
    {
        $this->get('/playercard?user=NieistniejacaPanda')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    /** @return array{int, int} */
    private function opaqueOrigin(Imagick $image): array
    {
        $left = $image->getImageWidth();
        $top = $image->getImageHeight();

        foreach ($image->getPixelIterator() as $y => $row) {
            foreach ($row as $x => $pixel) {
                if (($pixel->getColor(true)['a'] ?? 1) <= 0.01) {
                    continue;
                }

                $left = min($left, $x);
                $top = min($top, $y);
            }
        }

        return [$left, $top];
    }
}
