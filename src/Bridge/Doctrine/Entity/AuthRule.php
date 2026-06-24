<?php

namespace Fedale\RbacBundle\Bridge\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fedale\RbacBundle\Bridge\Doctrine\Repository\AuthRuleRepository;
use Fedale\RbacBundle\Dto\AuthRule as AuthRuleDto;

/**
 * `auth_rule` table (Yii2 RBAC). `name` is the natural key.
 *
 * Intentional divergence from Yii2 (PHP object serialized in `data`): the logic
 * is referenced by `serviceId` (a RuleInterface service) OR `expression`
 * (an ExpressionLanguage string). Exactly one of the two is set.
 */
#[ORM\Entity(repositoryClass: AuthRuleRepository::class)]
#[ORM\Table(name: 'auth_rule')]
#[ORM\HasLifecycleCallbacks]
class AuthRule
{
    #[ORM\Id]
    #[ORM\Column(length: 64)]
    private string $name = '';

    // id of the service implementing RuleInterface (tag fedale_rbac.rule).
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serviceId = null;

    // ExpressionLanguage string (e.g. subject.getAuthor() == user).
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $expression = null;

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

    public function toDto(): AuthRuleDto
    {
        return new AuthRuleDto(
            name: $this->name,
            serviceId: $this->serviceId,
            expression: $this->expression,
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

    public function getServiceId(): ?string
    {
        return $this->serviceId;
    }

    public function setServiceId(?string $serviceId): static
    {
        $this->serviceId = $serviceId ? trim($serviceId) : null;

        return $this;
    }

    public function getExpression(): ?string
    {
        return $this->expression;
    }

    public function setExpression(?string $expression): static
    {
        $this->expression = $expression ?: null;

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
