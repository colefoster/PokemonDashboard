<?php

namespace Tests\Feature\Api;

use App\Models\Pokemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpriteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test Pokemon with various name formats
        $this->createTestPokemon();
    }

    private function createTestPokemon(): void
    {
        // Simple names
        Pokemon::create(['api_id' => 25, 'name' => 'pikachu', 'is_default' => true]);
        Pokemon::create(['api_id' => 1, 'name' => 'bulbasaur', 'is_default' => true]);
        Pokemon::create(['api_id' => 4, 'name' => 'charmander', 'is_default' => true]);
        Pokemon::create(['api_id' => 7, 'name' => 'squirtle', 'is_default' => true]);

        // Hyphenated names
        Pokemon::create(['api_id' => 785, 'name' => 'tapu-koko', 'is_default' => true]);
        Pokemon::create(['api_id' => 122, 'name' => 'mr-mime', 'is_default' => true]);
        Pokemon::create(['api_id' => 439, 'name' => 'mime-jr', 'is_default' => true]);

        // Regional forms
        Pokemon::create(['api_id' => 10100, 'name' => 'raichu-alola', 'is_default' => false]);
        Pokemon::create(['api_id' => 10161, 'name' => 'meowth-galar', 'is_default' => false]);

        // Mega forms
        Pokemon::create(['api_id' => 10034, 'name' => 'charizard-mega-x', 'is_default' => false]);
        Pokemon::create(['api_id' => 10035, 'name' => 'charizard-mega-y', 'is_default' => false]);

        // Gigantamax forms
        Pokemon::create(['api_id' => 10195, 'name' => 'pikachu-gmax', 'is_default' => false]);
    }

    // =========================================================================
    // Pokemon by ID Tests
    // =========================================================================

    public function test_get_pokemon_sprite_by_id(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/25');

        $response->assertOk()
            ->assertJsonStructure(['id', 'url', 'options'])
            ->assertJson([
                'id' => 25,
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png',
            ]);
    }

    public function test_get_pokemon_sprite_with_shiny_option(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/25?shiny=true');

        $response->assertOk()
            ->assertJson([
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/25.png',
                'options' => ['shiny' => true],
            ]);
    }

    public function test_get_pokemon_sprite_with_back_variant(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/25?variant=back');

        $response->assertOk()
            ->assertJson([
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/back/25.png',
                'options' => ['variant' => 'back'],
            ]);
    }

    public function test_get_pokemon_sprite_with_back_shiny(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/25?variant=back&shiny=true');

        $response->assertOk()
            ->assertJson([
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/back/shiny/25.png',
            ]);
    }

    public function test_get_pokemon_sprite_official_artwork(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/25?style=official-artwork');

        $response->assertOk()
            ->assertJson([
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/25.png',
            ]);
    }

    public function test_get_pokemon_sprite_home(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/25?style=home');

        $response->assertOk()
            ->assertJson([
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/home/25.png',
            ]);
    }

    public function test_get_pokemon_sprite_showdown_uses_gif(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/25?style=showdown');

        $response->assertOk()
            ->assertJsonPath('url', 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/showdown/25.gif');
    }

    public function test_get_pokemon_sprite_version_specific(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/25?generation=i&game=red-blue');

        $response->assertOk()
            ->assertJson([
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/versions/generation-i/red-blue/25.png',
            ]);
    }

    // =========================================================================
    // Pokemon by Name Tests - Simple Names
    // =========================================================================

    public function test_get_pokemon_sprite_by_simple_name(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/pikachu');

        $response->assertOk()
            ->assertJsonStructure(['id', 'url', 'name', 'showdown_name', 'options'])
            ->assertJson([
                'id' => 25,
                'name' => 'pikachu',
            ]);
    }

    public function test_get_pokemon_sprite_by_name_case_insensitive(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/PIKACHU');

        $response->assertOk()
            ->assertJson(['id' => 25]);
    }

    public function test_get_pokemon_sprite_by_name_mixed_case(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/PiKaChU');

        $response->assertOk()
            ->assertJson(['id' => 25]);
    }

    public function test_get_pokemon_sprite_by_name_with_spaces(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/tapu%20koko');

        $response->assertOk()
            ->assertJson([
                'id' => 785,
                'name' => 'tapu-koko',
            ]);
    }

    public function test_get_pokemon_sprite_by_name_with_underscores(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/tapu_koko');

        $response->assertOk()
            ->assertJson([
                'id' => 785,
                'name' => 'tapu-koko',
            ]);
    }

    // =========================================================================
    // Pokemon by Name Tests - Hyphenated Names
    // =========================================================================

    public function test_get_pokemon_sprite_by_hyphenated_name(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/tapu-koko');

        $response->assertOk()
            ->assertJson([
                'id' => 785,
                'name' => 'tapu-koko',
            ]);
    }

    public function test_get_pokemon_sprite_mr_mime(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/mr-mime');

        $response->assertOk()
            ->assertJson([
                'id' => 122,
                'name' => 'mr-mime',
            ]);
    }

    public function test_get_pokemon_sprite_mime_jr(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/mime-jr');

        $response->assertOk()
            ->assertJson([
                'id' => 439,
                'name' => 'mime-jr',
            ]);
    }

    // =========================================================================
    // Pokemon by Name Tests - Regional Forms
    // =========================================================================

    public function test_get_pokemon_sprite_alolan_form(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/raichu-alola');

        $response->assertOk()
            ->assertJson([
                'id' => 10100,
                'name' => 'raichu-alola',
            ]);
    }

    public function test_get_pokemon_sprite_galarian_form(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/meowth-galar');

        $response->assertOk()
            ->assertJson([
                'id' => 10161,
                'name' => 'meowth-galar',
            ]);
    }

    // =========================================================================
    // Pokemon by Name Tests - Mega Forms
    // =========================================================================

    public function test_get_pokemon_sprite_mega_x(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/charizard-mega-x');

        $response->assertOk()
            ->assertJson([
                'id' => 10034,
                'name' => 'charizard-mega-x',
            ]);
    }

    public function test_get_pokemon_sprite_mega_y(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/charizard-mega-y');

        $response->assertOk()
            ->assertJson([
                'id' => 10035,
                'name' => 'charizard-mega-y',
            ]);
    }

    // =========================================================================
    // Pokemon by Name Tests - Gigantamax Forms
    // =========================================================================

    public function test_get_pokemon_sprite_gmax_form(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/pikachu-gmax');

        $response->assertOk()
            ->assertJson([
                'id' => 10195,
                'name' => 'pikachu-gmax',
            ]);
    }

    // =========================================================================
    // Pokemon by Name Tests - Showdown Name Conversion
    // =========================================================================

    public function test_showdown_name_simple(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/pikachu?style=showdown');

        $response->assertOk()
            ->assertJson([
                'showdown_name' => 'pikachu',
            ]);
    }

    public function test_showdown_name_hyphenated(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/tapu-koko?style=showdown');

        $response->assertOk()
            ->assertJson([
                'showdown_name' => 'tapukoko',
            ]);
    }

    public function test_showdown_name_alolan(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/raichu-alola?style=showdown');

        $response->assertOk()
            ->assertJson([
                'showdown_name' => 'raichu-alola',
            ]);
    }

    public function test_showdown_name_mega_x(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/charizard-mega-x?style=showdown');

        $response->assertOk()
            ->assertJson([
                'showdown_name' => 'charizard-mega-x',
            ]);
    }

    public function test_showdown_sprite_url_uses_name(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/pikachu?style=showdown');

        $response->assertOk()
            ->assertJsonPath('url', 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/showdown/pikachu.gif');
    }

    // =========================================================================
    // Pokemon by Name Tests - Not Found
    // =========================================================================

    public function test_pokemon_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/notapokemon');

        $response->assertNotFound()
            ->assertJsonStructure(['error', 'name', 'normalized', 'suggestions']);
    }

    public function test_pokemon_not_found_includes_suggestions(): void
    {
        // Use a partial name that doesn't fuzzy match to any Pokemon
        $response = $this->getJson('/api/sprites/pokemon/name/zznotreal');

        $response->assertNotFound()
            ->assertJsonStructure(['suggestions']);
    }

    public function test_fuzzy_match_finds_pokemon_by_prefix(): void
    {
        // "pika" should fuzzy match to "pikachu" since it starts with that prefix
        $response = $this->getJson('/api/sprites/pokemon/name/pika');

        $response->assertOk()
            ->assertJson(['name' => 'pikachu']);
    }

    // =========================================================================
    // Pokemon by Name Tests - With Options
    // =========================================================================

    public function test_pokemon_by_name_with_shiny(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/pikachu?shiny=true');

        $response->assertOk()
            ->assertJson([
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/25.png',
                'options' => ['shiny' => true],
            ]);
    }

    public function test_pokemon_by_name_with_back_variant(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/name/bulbasaur?variant=back');

        $response->assertOk()
            ->assertJson([
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/back/1.png',
            ]);
    }

    // =========================================================================
    // Batch Endpoint Tests
    // =========================================================================

    public function test_batch_sprites(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/batch?ids=1,4,7,25');

        $response->assertOk()
            ->assertJsonStructure([
                'sprites' => ['1', '4', '7', '25'],
                'options',
            ]);
    }

    public function test_batch_sprites_with_options(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/batch?ids=1,25&shiny=true');

        $response->assertOk()
            ->assertJsonPath('sprites.1', 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/1.png')
            ->assertJsonPath('sprites.25', 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/25.png');
    }

    // =========================================================================
    // Item Sprite Tests
    // =========================================================================

    public function test_get_item_sprite(): void
    {
        $response = $this->getJson('/api/sprites/items/pokeball');

        $response->assertOk()
            ->assertJson([
                'name' => 'pokeball',
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/pokeball.png',
            ]);
    }

    public function test_get_item_sprite_normalizes_name(): void
    {
        $response = $this->getJson('/api/sprites/items/MASTER-BALL');

        $response->assertOk()
            ->assertJson([
                'name' => 'master-ball',
            ]);
    }

    // =========================================================================
    // Type Sprite Tests
    // =========================================================================

    public function test_get_type_sprite(): void
    {
        $response = $this->getJson('/api/sprites/types/electric');

        $response->assertOk()
            ->assertJson([
                'name' => 'electric',
                'generation' => 'ix',
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/types/generation-ix/electric.png',
            ]);
    }

    public function test_get_type_sprite_different_generation(): void
    {
        $response = $this->getJson('/api/sprites/types/fire?generation=iv');

        $response->assertOk()
            ->assertJson([
                'generation' => 'iv',
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/types/generation-iv/fire.png',
            ]);
    }

    // =========================================================================
    // Badge Sprite Tests
    // =========================================================================

    public function test_get_badge_sprite(): void
    {
        $response = $this->getJson('/api/sprites/badges/boulder-badge');

        $response->assertOk()
            ->assertJson([
                'name' => 'boulder-badge',
                'url' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/badges/boulder-badge.png',
            ]);
    }

    // =========================================================================
    // Meta Endpoint Tests
    // =========================================================================

    public function test_pokemon_styles_endpoint(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/styles');

        $response->assertOk()
            ->assertJsonStructure([
                'default',
                'official-artwork',
                'home',
                'dream-world',
                'showdown',
            ]);
    }

    public function test_pokemon_generations_endpoint(): void
    {
        $response = $this->getJson('/api/sprites/pokemon/generations');

        $response->assertOk()
            ->assertJsonStructure([
                'i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii',
            ]);
    }

    // =========================================================================
    // Redirect Tests
    // =========================================================================

    public function test_redirect_option_pokemon_by_id(): void
    {
        $response = $this->get('/api/sprites/pokemon/25?redirect=true');

        $response->assertRedirect('https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png');
    }

    public function test_redirect_option_pokemon_by_name(): void
    {
        $response = $this->get('/api/sprites/pokemon/name/pikachu?redirect=true');

        $response->assertRedirect('https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png');
    }

    public function test_redirect_option_item(): void
    {
        $response = $this->get('/api/sprites/items/pokeball?redirect=true');

        $response->assertRedirect('https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/pokeball.png');
    }
}