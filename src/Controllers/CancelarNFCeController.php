<?php
namespace NexoNFe\Controllers;

require_once '../src/Services/SefazService.php';
require_once '../src/Utils/ResponseHelper.php';

use NexoNFe\Services\SefazService;
use NexoNFe\Utils\ResponseHelper;

class CancelarNFCeController
{
    private $sefazService;
    
    public function __construct()
    {
        $this->sefazService = new SefazService();
    }
    
    public function handle()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['chave']) || empty($input['justificativa'])) {
                ResponseHelper::error('Chave e justificativa são obrigatórios', 400);
                return;
            }
            
            if (strlen($input['justificativa']) < 15) {
                ResponseHelper::error('Justificativa deve ter pelo menos 15 caracteres', 400);
                return;
            }
            
            $resultado = $this->sefazService->cancelarNFCe(
                $input['chave'],
                $input['justificativa'],
                $input['empresa_id'] ?? null
            );
            
            if ($resultado['sucesso']) {
                ResponseHelper::success([
                    'chave' => $input['chave'],
                    'status' => 'Cancelada',
                    'protocolo' => $resultado['protocolo'] ?? null,
                    'data_cancelamento' => date('Y-m-d H:i:s')
                ]);
            } else {
                ResponseHelper::error($resultado['erro'], 422);
            }
            
        } catch (Exception $e) {
            error_log("Erro CancelarNFCeController: " . $e->getMessage());
            ResponseHelper::error('Erro interno do servidor', 500);
        }
    }
}
?>
