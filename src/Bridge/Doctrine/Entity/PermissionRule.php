<?php

namespace Fedale\AccessControlVoterBundle\Bridge\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fedale\AccessControlVoterBundle\Bridge\Doctrine\Repository\PermissionRuleRepository;
use Fedale\AccessControlVoterBundle\Dto\PermissionRule as PermissionRuleDto;

#[ORM\Entity(repositoryClass: PermissionRuleRepository::class)]
#[ORM\Table(name: 'permission_rule')]
#[ORM\HasLifecycleCallbacks]
// Indice composito allineato a PermissionRuleRepository::findActive()
// (WHERE active = true ORDER BY sort ASC) e a findActiveByAttribute(): il
// voter parte sempre da li. Gli indici a colonna singola su boolean a bassa
// cardinalita (allow/active) non venivano usati dal planner.
#[ORM\Index(columns: ['active', 'sort'], name: 'idx_permission_rule_active_sort')]
class PermissionRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    // Attributo nativo Symfony valutato dal voter (es. EDIT_INVOICE), cosi
    // come compare in #[IsGranted('EDIT_INVOICE')] / isGranted('EDIT_INVOICE').
    #[ORM\Column(length: 128)]
    private string $attribute = '';

    // Predisposizione object-level: FQCN del soggetto a cui la regola si
    // applica. Letto ma NON valutato per ora (il match su $subject e' un no-op).
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subjectType = null;

    // Predisposizione "Rule" stile Yii2 RBAC: id del servizio che implementa
    // PermissionConditionInterface e valuta una condizione contestuale sul
    // soggetto. Letto ma NON eseguito per ora (estensione futura).
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $condition = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(options: ['default' => true])]
    private bool $allow = true;

    #[ORM\Column(options: ['default' => 0])]
    private int $sort = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

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

    public function toDto(): PermissionRuleDto
    {
        return new PermissionRuleDto(
            id: $this->id,
            name: $this->name,
            reason: $this->reason,
            attribute: $this->attribute,
            subjectType: $this->subjectType,
            condition: $this->condition,
            roles: $this->roles,
            allow: $this->allow,
            sort: $this->sort,
            active: $this->active,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason ? trim($reason) : null;

        return $this;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function setAttribute(string $attribute): static
    {
        $this->attribute = trim($attribute);

        return $this;
    }

    public function getSubjectType(): ?string
    {
        return $this->subjectType;
    }

    public function setSubjectType(?string $subjectType): static
    {
        $this->subjectType = $subjectType ? trim($subjectType) : null;

        return $this;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function setCondition(?string $condition): static
    {
        $this->condition = $condition ? trim($condition) : null;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function isAllow(): bool
    {
        return $this->allow;
    }

    public function setAllow(bool $allow): static
    {
        $this->allow = $allow;

        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(
        ?\DateTimeImmutable $createdAt
    ): static {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(
        ?\DateTimeImmutable $updatedAt
    ): static {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
