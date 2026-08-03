<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pedido;
use App\Models\User;
use App\Repositories\ClienteRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Logica de negocio del modulo Clientes (analitica de compra, solo lectura).
 */
final class ClienteService
{
    public function __construct(
        private readonly ClienteRepository $clientes,
    ) {
    }

    /**
     * Lista clientes con estadisticas de compra.
     *
     * @return Collection<int, User>|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listarConEstadisticas(?int $porPagina = null, int $pagina = 1)
    {
        return $this->clientes->listarConEstadisticas($porPagina, $pagina);
    }

    /**
     * Lista historial de pedidos de un cliente.
     *
     * @return Collection<int, Pedido>
     */
    public function listarPedidosDeCliente(int $clienteId): Collection
    {
        $cliente = $this->clientes->buscarClientePorId($clienteId);

        if ($cliente === null) {
            throw ValidationException::withMessages([
                'id' => ['El cliente no existe o no pertenece a esta instancia.'],
            ]);
        }

        return $this->clientes->listarPedidosPorCliente($clienteId);
    }
}
