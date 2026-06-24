<?php

namespace Fedale\RbacBundle\Bridge\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthItemChildRepository;

/**
 * `auth_item_child` table (Yii2 RBAC): parent->child hierarchy between auth
 * items (role->role, role->permission, permission->permission). Composite PK
 * (parent, child); both FK -> auth_item.name with ON DELETE CASCADE.
 */
#[ORM\Entity(repositoryClass: AuthItemChildRepository::class)]
#[ORM\Table(name: 'auth_item_child')]
class AuthItemChild
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: AuthItem::class)]
    #[ORM\JoinColumn(name: 'parent', referencedColumnName: 'name', onDelete: 'CASCADE')]
    private AuthItem $parent;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: AuthItem::class)]
    #[ORM\JoinColumn(name: 'child', referencedColumnName: 'name', onDelete: 'CASCADE')]
    private AuthItem $child;

    public function __construct(AuthItem $parent, AuthItem $child)
    {
        $this->parent = $parent;
        $this->child = $child;
    }

    public function getParent(): AuthItem
    {
        return $this->parent;
    }

    public function getChild(): AuthItem
    {
        return $this->child;
    }
}
