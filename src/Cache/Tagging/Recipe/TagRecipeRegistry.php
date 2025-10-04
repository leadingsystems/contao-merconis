<?php

namespace LeadingSystems\MerconisBundle\Cache\Tagging\Recipe;

final class TagRecipeRegistry
{
    private $recipes;

    public function __construct(array $recipes = array())
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


