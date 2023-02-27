<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'products')]
    private Collection $tags;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Category $category = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?ProductImg $productImg = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    public function getThumbnail(): string
    {
        //Cette méthode rend le nom d'un fichier vignette selon la catégorie de notre Product
        $address = "assets/img/";
        if ($this->productImg) {
            return $this->productImg->getFileAddress();
        } else if ($this->category) {
            //Dans cette variable Switch, nous récupérons le nom de l'objet Category lié afin de déterminer le nom de la vignette, avec un strtolower afin d'éviter les problèmes de casse :
            switch (strtolower($this->category->getName())) {
                case 'chaise':
                    $address .= 'placeholder_chaise.jpg';
                    break;
                case 'armoire':
                    $address .=  'placeholder_armoire.jpg';
                    break;
                case 'lit':
                    $address .=  'placeholder_lit.jpg';
                    break;
                case 'bureau':
                    $address .=  'placeholder_bureau.jpg';
                    break;
                case 'canape':
                    $address .=  'placeholder_canape.jpg';
                    break;
                default:
                    $address .=  'placeholder_none.jpg';
            }
        } else $address .= 'placeholder_none.jpg';
        return $address;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setProduct($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getProduct() === $this) {
                $reservation->setProduct(null);
            }
        }

        return $this;
    }

    public function getProductImg(): ?ProductImg
    {
        return $this->productImg;
    }

    public function setProductImg(?ProductImg $productImg): self
    {
        $this->productImg = $productImg;

        return $this;
    }
}
