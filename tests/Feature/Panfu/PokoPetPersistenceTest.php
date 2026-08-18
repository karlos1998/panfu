<?php

namespace Tests\Feature\Panfu;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PokoPetPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pokopets_have_the_fields_required_by_the_flash_value_object(): void
    {
        $this->assertTrue(Schema::hasColumns('pokopets', [
            'id',
            'user_id',
            'type',
            'name',
            'selected',
            'state',
            'x',
            'y',
            'health',
            'max_health',
            'speed',
            'agility',
            'power',
            'experience',
            'level',
            'abilities',
            'last_fed',
        ]));
    }

    public function test_player_cannot_own_the_same_pokopet_type_twice(): void
    {
        $user = User::factory()->create();
        $pet = [
            'user_id' => $user->id,
            'type' => 9,
            'name' => 'Marieta',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('pokopets')->insert($pet);

        $this->expectException(QueryException::class);
        DB::table('pokopets')->insert($pet);
    }

    public function test_pokopet_properties_are_sent_through_the_flash_compatibility_mapper(): void
    {
        require_once base_path('information-server/ClassLoader.php');
        require_once base_path('information-server/Plugins/Panfu/Panfu.php');

        $pet = \Panfu::getPokoPetVoFromRow([
            'id' => 17,
            'name' => 'Marieta',
            'type' => 9,
            'selected' => 1,
            'x' => 310,
            'y' => 290,
            'state' => 'idle',
            'abilities' => '[]',
            'health' => 5,
            'max_health' => 5,
            'speed' => 1,
            'agility' => 1,
            'power' => 1,
            'experience' => 0,
            'level' => 1,
            'last_fed' => null,
        ]);

        $this->assertInstanceOf(\stdClass::class, $pet);
        $this->assertInstanceOf(\stdClass::class, $pet->properties);
        $this->assertFalse(property_exists($pet, '_explicitType'));
        $this->assertFalse(property_exists($pet->properties, '_explicitType'));
        $this->assertSame(5, $pet->properties->health);

        $packet = new \Amfphp_Core_Amf_Packet;
        $packet->messages[] = new \Amfphp_Core_Amf_Message('/1/onResult', '', $pet);
        $payload = (new \Amfphp_Core_Amf_Serializer)->serialize($packet);

        $this->assertStringNotContainsString('PokoPetVO', $payload);
        $this->assertStringNotContainsString('PokoPetPropertiesVO', $payload);
    }
}
