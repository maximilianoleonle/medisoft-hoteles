<?php

require_once __DIR__ . '/../models/Trabajador.php';

class TrabajadorNominaPeriodoService
{
    private $trabajadorModel;

    public function __construct(?Trabajador $trabajadorModel = null)
    {
        $this->trabajadorModel = $trabajadorModel ?: new Trabajador();
    }

    public function cerrarPeriodo(int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        return $this->trabajadorModel->cerrarNominaPeriodoPersistenteParaHotel($hotelId, $datos, $usuarioId);
    }

    public function aprobarPeriodo(int $hotelId, int $periodoId, ?int $usuarioId = null): bool
    {
        return $this->trabajadorModel->aprobarNominaPeriodoParaHotel($periodoId, $hotelId, $usuarioId);
    }

    public function anularPeriodo(int $hotelId, int $periodoId, string $motivo, ?int $usuarioId = null): bool
    {
        return $this->trabajadorModel->anularNominaPeriodoParaHotel($periodoId, $hotelId, $motivo, $usuarioId);
    }
}
