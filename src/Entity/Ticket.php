<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\TicketRepository;
use App\State\TicketCreateProcessor;
use App\Validator\MaxOpenTickets;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[MaxOpenTickets(groups: ['ticket:create'])]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(
            security: "is_granted('ROLE_ADMIN') or object.getClient() == user or object.getAssignedTo() == user",
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: TicketCreateProcessor::class,
            validationContext: ['groups' => ['Default', 'ticket:create']],
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN') or object.getClient() == user or object.getAssignedTo() == user",
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['ticket:read', 'timestampable']],
    denormalizationContext: ['groups' => ['ticket:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 20,
    order: ['createdAt' => 'DESC'],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'status' => 'exact',
    'priority' => 'exact',
    'client.email' => 'partial',
])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'priority', 'status', 'title'])]
class Ticket
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ApiProperty(identifier: true)]
    #[Groups(['ticket:read', 'comment:read'])]
    private ?Uuid $uuid = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ticket:read', 'ticket:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['ticket:read', 'ticket:write'])]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Groups(['ticket:read', 'ticket:write'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['faible', 'normale', 'haute'], message: 'Priorité invalide. Valeurs acceptées : faible, normale, haute.')]
    private ?string $priority = 'normale';

    #[ORM\Column(length: 20)]
    #[Groups(['ticket:read', 'ticket:write'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['ouvert', 'en_cours', 'resolu', 'ferme'], message: 'Statut invalide. Valeurs acceptées : ouvert, en_cours, resolu, ferme.')]
    private ?string $status = 'ouvert';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'client_uuid', referencedColumnName: 'uuid', nullable: false)]
    #[Groups(['ticket:read'])]
    private ?User $client = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assigned_to_uuid', referencedColumnName: 'uuid', nullable: true)]
    #[Groups(['ticket:read', 'ticket:write'])]
    private ?User $assignedTo = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(name: 'category_uuid', referencedColumnName: 'uuid', nullable: false)]
    #[Groups(['ticket:read', 'ticket:write'])]
    #[Assert\NotNull(message: 'Une catégorie est requise.')]
    private ?Category $category = null;

    /** @var Collection<int, Comment> */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'ticket', cascade: ['remove'])]
    #[Groups(['ticket:read'])]
    private Collection $comments;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->comments = new ArrayCollection();
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    /** @return Collection<int, Comment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTicket($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getTicket() === $this) {
                $comment->setTicket(null);
            }
        }
        return $this;
    }
}
