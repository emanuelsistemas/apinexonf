<?php

namespace NexoNFe\Controllers;

use Exception;

/**
 * Controller para consultar status dos serviços da SEFAZ
 */
class StatusSefazController
{
    /**
     * Handle da requisição
     * GET /api/status-sefaz?uf=SP&ambiente=2&servico=nfe
     */
    public function handle()
    {
        try {
            $uf = $_GET['uf'] ?? 'SP';
            $ambiente = (int)($_GET['ambiente'] ?? 2);
            $servico = $_GET['servico'] ?? 'all';
            
            // Resposta simulada para teste inicial
            $resultado = [
                'info' => [
                    'uf_consultada' => $uf,
                    'ambiente' => $ambiente === 1 ? 'Produção' : 'Homologação',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'servidor_api' => 'apinfe.nexopdv.com'
                ],
                'nfe' => [
                    'servico' => 'NFe',
                    'status' => '107',
                    'motivo' => 'Serviço em Operação',
                    'disponivel' => true,
                    'interpretacao' => 'Serviço em Operação',
                    'timestamp' => date('Y-m-d H:i:s')
                ],
                'nfce' => [
                    'servico' => 'NFC-e',
                    'status' => '107',
                    'motivo' => 'Serviço em Operação',
                    'disponivel' => true,
                    'interpretacao' => 'Serviço em Operação',
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $resultado,
                'message' => 'Status SEFAZ consultado com sucesso',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao consultar status SEFAZ: ' . $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
