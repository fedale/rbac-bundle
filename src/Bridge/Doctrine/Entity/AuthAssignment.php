<?php

namespace Fedale\RbacBundle\Bridge\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthAssignmentRepository;

/**
 * `auth_assignment` table (Yii2 RBAC): associates an auth item (role *or*
 * permission) with a user. The system's single user->item source (see README:
 * User::getRoles() reads this table). Composite PK (item_name, user_id);
 * item_name FK -> auth_item.name with ON DELETE CASCADE. Only `created_at`.
 *
 * `user_id` is VARCHAR(255) (not 64 as in Yii2) to accommodate Symfony
 * identifiers (e.g. email).
 */
#[ORM\Entity(repositoryClass: AuthAssignmentRepository::class)]
#[ORM\Table(name: 'auth_assignment')]
#[ORM\Index(columns: ['user_id'], name: 'idx_auth_assignment_user')]
#[ORM\HasLifecycleCallbacks]
class AuthAssignment
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: AuthItem::class)]
    #[ORM\JoinColumn(name: 'item_name', referencedColumnName: 'name', onDelete: 'CASCADE')]
    private AuthItem $item;

    #[ORM\Id]
    #[ORM\Column(name: 'user_id', length: 255)]
    private string $userId = '';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct(AuthItem $item, string $userId)
    {
        $this->item = $item;
        $this->userId = $userId;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
    }

    public function getItem(): AuthItem
    {
        return $this->item;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
