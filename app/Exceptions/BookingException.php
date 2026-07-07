<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Erreur métier à message utilisateur (véhicule introuvable, trajet non calculable…).
 * Les contrôleurs API la convertissent en réponse 422.
 */
class BookingException extends RuntimeException {}
