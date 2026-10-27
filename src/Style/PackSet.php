<?php

declare(strict_types=1);

namespace Bpmore\Copydesk\Style;

/**
 * Every pack a site can use, and the rules they add up to.
 *
 * Packs are given in the spec's order: the bundled packs, then the site's
 * own (resources/copydesk/*.yaml). Two packs with one name is refused, with
 * both files named, so a site extends a bundled pack instead of quietly
 * shadowing it.
 *
 * Resolving a pack walks what it extends first, in the order listed, then
 * applies its own rules (a rule whose id is already there replaces it, so a
 * later pack can rewrite an earlier pack's rule), then its `disable` list.
 * Collection changes are applied last, in the same order, and only for that
 * collection. A pack reached twice (two packs both extending
 * core-punctuation) is applied once, the first time.
 *
 * Every name must exist: an extended pack, a disabled rule. A name that
 * does not is a typo, and is refused with the nearest real name, because a
 * rule left on by a misspelt `disable` is a rule nobody chose.
 */
final class PackSet
{
    /** @var array<string, Pack> */
    private readonly array $packs;

    /**
     * @param  list<Pack>  $packs  bundled first, then the site's
     */
    public function __construct(array $packs)
    {
        $byName = [];

        foreach ($packs as $pack) {
            if (isset($byName[$pack->name])) {
                throw new InvalidPack("{$pack->source}: there is already a pack called \"{$pack->name}\" ({$byName[$pack->name]->source}). Give this one another name and extend that one instead.");
            }

            $byName[$pack->name] = $pack;
        }

        $this->packs = $byName;
    }

    public function has(string $name): bool
    {
        return isset($this->packs[$name]);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->packs);
    }

    /**
     * The rules a set of packs adds up to, for one collection or for all.
     *
     * @param  list<string>  $roots  the packs a site has turned on
     * @return list<RuleDefinition> in the order they were first defined
     */
    public function rules(array $roots, ?string $collection = null): array
    {
        $rules = [];
        $order = [];
        $done = [];

        foreach ($roots as $root) {
            if (! isset($this->packs[$root])) {
                throw new InvalidPack("There is no pack called \"{$root}\" to turn on.".Pack::nearest($root, $this->names()));
            }

            $this->apply($this->packs[$root], $rules, $order, [], $collection, $done);
        }

        return array_values(array_map(static fn (string $id): RuleDefinition => $rules[$id], array_filter($order, static fn (string $id): bool => isset($rules[$id]))));
    }

    /**
     * The runnable rules, for a RuleSet.
     *
     * @param  list<string>  $roots
     * @return list<StyleRule>
     */
    public function runnable(array $roots, ?string $collection = null): array
    {
        return array_map(RuleFactory::make(...), $this->rules($roots, $collection));
    }

    /**
     * @param  array<string, RuleDefinition>  $rules  by id, built up as packs are applied
     * @param  list<string>  $order  ids, in the order first seen
     * @param  list<string>  $path  the packs being resolved, to catch a circle
     * @param  array<string, true>  $done  packs already applied
     */
    private function apply(Pack $pack, array &$rules, array &$order, array $path, ?string $collection, array &$done): void
    {
        if (in_array($pack->name, $path, true)) {
            $circle = implode(' extends ', [...array_slice($path, (int) array_search($pack->name, $path, true)), $pack->name]);

            throw new InvalidPack("{$pack->source}: {$circle}. Packs cannot extend each other in a circle.");
        }

        if (isset($done[$pack->name])) {
            return;
        }

        $path[] = $pack->name;

        foreach ($pack->extends as $name) {
            if (! isset($this->packs[$name])) {
                throw new InvalidPack("{$pack->source}: extends \"{$name}\", and there is no pack called that.".Pack::nearest($name, $this->names()));
            }

            $this->apply($this->packs[$name], $rules, $order, $path, $collection, $done);
        }

        $this->add($pack->rules, $rules, $order);
        $this->disable($pack->disable, $rules, $pack->source);

        if ($collection !== null && isset($pack->collections[$collection])) {
            $changes = $pack->collections[$collection];
            $this->add($changes['rules'], $rules, $order);
            $this->disable($changes['disable'], $rules, "{$pack->source}, collection \"{$collection}\"");
        }

        $done[$pack->name] = true;
    }

    /**
     * @param  list<RuleDefinition>  $add
     * @param  array<string, RuleDefinition>  $rules
     * @param  list<string>  $order
     */
    private function add(array $add, array &$rules, array &$order): void
    {
        foreach ($add as $rule) {
            if (! in_array($rule->id, $order, true)) {
                $order[] = $rule->id;
            }

            $rules[$rule->id] = $rule;
        }
    }

    /**
     * @param  list<string>  $ids
     * @param  array<string, RuleDefinition>  $rules
     */
    private function disable(array $ids, array &$rules, string $where): void
    {
        foreach ($ids as $id) {
            if (! isset($rules[$id])) {
                throw new InvalidPack("{$where}: disables \"{$id}\", and no rule before it is called that.".Pack::nearest($id, array_keys($rules)));
            }

            unset($rules[$id]);
        }
    }
}
