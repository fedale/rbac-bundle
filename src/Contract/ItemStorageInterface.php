<?php

namespace Fedale\RbacBundle\Contract;

use Fedale\RbacBundle\Dto\AuthItem;

/**
 * Source-agnostic access to auth items and their hierarchy
 * (auth_item + auth_item_child). Only a Doctrine implementation is provided,
 * but any provider (YAML, API, ...) can implement this contract.
 */
interface ItemStorageInterface
{
    public function getItem(string $name): ?AuthItem;

    /**
     * Names of the direct children of $name (auth_item_child edges with parent = $name).
     *
     * @return string[]
     */
    public function getChildren(string $name): array;

    /**
     * Names of the direct parents of $name (auth_item_child edges with child = $name).
     * Used by AccessManager to walk up from a permission to the assignments.
     *
     * @return string[]
     */
    public function getParents(string $name): array;

    /**
     * @return iterable<AuthItem>
     */
    public function allItems(): iterable;
}
