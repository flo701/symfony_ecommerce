<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\OneToMany(mappedBy: 'clientOrder', targetEntity: Reservation::class)]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        // Valeurs par défaut de nos attributs :
        $this->status = "panier";
        $this->address = "Non spécifiée";
        $this->creationDate = new \DateTime("now");
    }

    public function getTotalPrice(): float
    {
        // Cette méthode retourne le prix total de la commande, laquelle est calculée en multipliant la quantité de chaque Reservation enregistrée avec le prix (la valeur $price) de chaque Product lié à la Reservation. Le prix total est obtenu en additionnant le résultat fourni par chaque Reservation.
        // Les deux valeurs nécessaires sont la valeur $quantity de Reservation, et $price de Product.

        // On prépare la variable appelée à contenir le prix total :
        $totalPrice = 0;
        //  Dans une boucle foreach itérant à travers nos Reservations, nous ajoutons chaque calcul (prix*quantité) à notre variable $totalPrice :
        foreach ($this->reservations as $reservation) {
            // On récupère le prix rangé dans l'attribut $price du Product lié :
            if ($reservation->getProduct()) { // Sécurité : on vérifie que le Product existe toujours
                $price = $reservation->getProduct()->getPrice();
            } else $price = 0;
            //  On multiplie les deux valeurs et on ajoute le résultat à $totalPrice :
            $totalPrice += ($price * $reservation->getQuantity());
        }
        return $totalPrice;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

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
            $reservation->setClientOrder($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getClientOrder() === $this) {
                $reservation->setClientOrder(null);
            }
        }

        return $this;
    }
}
