<?php

namespace Fedale\RbacBundle\Bridge\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthItemRepository;
use Fedale\RbacBundle\Dto\AuthItem as AuthItemDto;
use Fedale\RbacBundle\Enum\AuthItemType;

/**
 * `auth_item` table (Yii2 RBAC): roles and permissions in the same table,
 * distinguished by `type`. `name` is the natural key.
 *
 * FK rule_name -> auth_rule.name with ON DELETE SET NULL (mapped as a nullable
 * ManyToOne association to AuthRule).
 */
#[ORM\Entity(repositoryClass: AuthItemRepository::class)]
#[ORM\Table(name: 'auth_item')]
#[ORM\Index(columns: ['type'], name: 'idx_auth_item_type')]
#[ORM\HasLifecycleCallbacks]
class AuthItem
{
    #[ORM\Id]
    #[ORM\Column(length: 64)]
    private string $name = '';

    #[ORM\Column(length: 32, enumType: AuthItemType::class)]
    private AuthItemType $type = AuthItemType::PERMISSION;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: AuthRule::class)]
    #[ORM\JoinColumn(name: 'rule_name', referencedColumnName: 'name', nullable: true, onDelete: 'SET NULL')]
    private ?AuthRule $rule = null;

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $data = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toDto(): AuthItemDto
    {
        return new AuthItemDto(
            name: $this->name,
            type: $this->type,
            description: $this->description,
            ruleName: $this->rule?->getName(),
            data: $this->data,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);

        return $this;
    }

    public function getType(): AuthItemType
    {
        return $this->type;
    }

    public function setType(AuthItemType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getRule(): ?AuthRule
    {
        return $this->rule;
    }

    public function setRule(?AuthRule $rule): static
    {
        $this->rule = $rule;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
