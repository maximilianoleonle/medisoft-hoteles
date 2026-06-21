<?php

require_once __DIR__ . '/../models/Trabajador.php';

class TrabajadorNominaPeriodoService
{
    private $trabajadorModel;
    private $manageTransaction;

    public function __construct(?Trabajador $trabajadorModel = null, array $options = [])
    {
        $this->trabajadorModel = $trabajadorModel ?: new Trabajador();
        $this->manageTransaction = (bool)($options['manage_transaction'] ?? true);
    }

    public function cerrarPeriodo(int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        return $this->trabajadorModel->cerrarNominaPeriodoPersistenteParaHotel($hotelId, $datos, $usuarioId, $this->manageTransaction);
    }

    public function aprobarPeriodo(int $hotelId, int $periodoId, ?int $usuarioId = null): bool
    {
        return $this->trabajadorModel->aprobarNominaPeriodoParaHotel($periodoId, $hotelId, $usuarioId, $this->manageTransaction);
    }

    public function anularPeriodo(int $hotelId, int $periodoId, string $motivo, ?int $usuarioId = null): bool
    {
        return $this->trabajadorModel->anularNominaPeriodoParaHotel($periodoId, $hotelId, $motivo, $usuarioId, $this->manageTransaction);
    }
}
