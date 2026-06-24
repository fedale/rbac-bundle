<?php

namespace Fedale\RbacBundle\Bridge\Doctrine\Storage;

use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthItemChildRepository;
use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthItemRepository;
use Fedale\RbacBundle\Contract\ItemStorageInterface;
use Fedale\RbacBundle\Dto\AuthItem;

final class DoctrineItemStorage implements ItemStorageInterface
{
    public function __construct(
        private readonly AuthItemRepository $items,
        private readonly AuthItemChildRepository $children,
    ) {
    }

    public function getItem(string $name): ?AuthItem
    {
        return $this->items->find($name)?->toDto();
    }

    public function getChildren(string $name): array
    {
        return $this->children->findChildNames($name);
    }

    public function getParents(string $name): array
    {
        return $this->children->findParentNames($name);
    }

    public function allItems(): iterable
    {
        foreach ($this->items->findAll() as $entity) {
            yield $entity->toDto();
        }
    }
}
