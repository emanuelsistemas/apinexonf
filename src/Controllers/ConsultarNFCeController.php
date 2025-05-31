<?php
namespace NexoNFe\Controllers;

require_once '../src/Services/SefazService.php';
require_once '../src/Utils/ResponseHelper.php';

use NexoNFe\Services\SefazService;
use NexoNFe\Utils\ResponseHelper;

class ConsultarNFCeController
{
    private $sefazService;
    
    public function __construct()
    {
        $this->sefazService = new SefazService();
    }
    
    public function handle()
    {
        try {
            $chave = $_GET['chave'] ?? '';
            $empresaId = $_GET['empresa_id'] ?? '';
            
            if (empty($chave) || strlen($chave) !== 44) {
                ResponseHelper::error('Chave de acesso inválida', 400);
                return;
            }
            
            if (empty($empresaId)) {
                ResponseHelper::error('ID da empresa é obrigatório', 400);
                return;
            }
            
            $resultado = $this->sefazService->consultarNFCe($chave, $empresaId);
            
            if ($resultado['sucesso']) {
                ResponseHelper::success([
                    'chave' => $chave,
                    'status' => $resultado['status'],
                    'motivo' => $resultado['motivo'],
                    'protocolo' => $resultado['protocolo'] ?? null,
                    'data_consulta' => date('Y-m-d H:i:s')
                ]);
            } else {
                ResponseHelper::error($resultado['erro'], 422);
            }
            
        } catch (Exception $e) {
            error_log("Erro ConsultarNFCeController: " . $e->getMessage());
            ResponseHelper::error('Erro interno do servidor', 500);
        }
    }
}
?>
