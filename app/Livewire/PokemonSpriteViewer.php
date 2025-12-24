<?php

namespace App\Livewire;

use App\Models\Pokemon;
use Livewire\Component;

class PokemonSpriteViewer extends Component
{
    public ?Pokemon $record = null;
    public string $variant = 'default';

    public function mount($record): void
    {
        $this->record = $record;
    }



    public function setVariant(string $variant): void
    {
        $this->variant = $variant;
    }

    public function getFrontSpriteUrlProperty(): ?string
    {
        if (!$this->record) {
            return null;
        }

        $spriteField = "sprite_front_{$this->variant}";

        return $this->record->{$spriteField};
    }

    public function getBackSpriteUrlProperty(): ?string
    {
        if (!$this->record) {
            return null;
        }

        $spriteField = "sprite_back_{$this->variant}";

        return $this->record->{$spriteField};
    }
    public function render()
    {
        return view('livewire.pokemon-sprite-viewer');
    }
}
