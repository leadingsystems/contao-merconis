<?php
declare(strict_types=1);

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

final class TagRecipeRegistry
{
    private array $recipes;

    public function __construct(array $recipes = [])
    {
        $this->recipes = $recipes;
    }

    public function has(string $name): bool
    {
        return isset($this->recipes[$name]);
    }

    public function get(string $name): ?TagRecipeInterface
    {
        return isset($this->recipes[$name]) ? $this->recipes[$name] : null;
    }

    public function list(): array
    {
        return array_keys($this->recipes);
    }
}


