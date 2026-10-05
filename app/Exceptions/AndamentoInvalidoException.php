<?php

namespace App\Exceptions;

use DomainException;

/**
 * Ação de andamento que a situação atual da luta não permite (ex.: encerrar uma luta
 * que não começou). A mensagem é escrita para ser exibida ao administrador.
 */
class AndamentoInvalidoException extends DomainException {}
