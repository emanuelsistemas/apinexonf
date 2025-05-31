<?php
namespace NexoNFe\Controllers;

require_once '../src/Services/SefazService.php';
require_once '../src/Utils/ResponseHelper.php';

use NexoNFe\Services\SefazService;
use NexoNFe\Utils\ResponseHelper;

class EnviarSefazController
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
            
            if (!isset($input['xml']) || !isset($input['chave']) || !isset($input['empresa_id'])) {
                ResponseHelper::error('XML, chave e empresa_id são obrigatórios', 400);
                return;
            }
            
            $resultado = $this->sefazService->enviarNFe(
                $input['xml'], 
                $input['chave'], 
                $input['empresa_id']
            );
            
            if ($resultado['sucesso']) {
                ResponseHelper::success([
                    'protocolo' => $resultado['protocolo'],
                    'status' => $resultado['status'],
                    'motivo' => $resultado['motivo'],
                    'data_autorizacao' => $resultado['data_autorizacao'] ?? null
                ]);
            } else {
                ResponseHelper::error($resultado['erro'], 422);
            }
            
        } catch (Exception $e) {
            error_log("Erro EnviarSefazController: " . $e->getMessage());
            ResponseHelper::error('Erro interno do servidor', 500);
        }
    }
}
?>
