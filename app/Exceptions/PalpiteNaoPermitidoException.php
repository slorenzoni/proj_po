<?php

namespace App\Exceptions;

use DomainException;

/**
 * Palpite (ou pontuação do placar dos fãs) fora da janela permitida. A mensagem é
 * escrita para ser exibida ao usuário.
 */
class PalpiteNaoPermitidoException extends DomainException {}
