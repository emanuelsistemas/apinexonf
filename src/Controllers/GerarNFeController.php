<?php
namespace NexoNFe\Controllers;

require_once '../src/Services/NFeService.php';
require_once '../src/Utils/ResponseHelper.php';

use NexoNFe\Services\NFeService;
use NexoNFe\Utils\ResponseHelper;

class GerarNFeController
{
    private $nfeService;
    
    public function __construct()
    {
        $this->nfeService = new NFeService();
    }
    
    public function handle()
    {
        try {
            // Validar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                ResponseHelper::error('Método não permitido', 405);
                return;
            }
            
            // Obter dados JSON
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                ResponseHelper::error('Dados JSON inválidos', 400);
                return;
            }
            
            // Validar dados obrigatórios
            $this->validarDados($input);
            
            // Gerar NFe
            $resultado = $this->nfeService->gerarNFe(
                $input['empresa'],
                $input['cliente'],
                $input['produtos'],
                $input['totais'],
                $input['pagamentos'] ?? []
            );
            
            if ($resultado['sucesso']) {
                ResponseHelper::success([
                    'xml' => $resultado['xml'],
                    'chave' => $resultado['chave'],
                    'numero_nfe' => $resultado['numero_nfe'],
                    'protocolo' => $resultado['protocolo'] ?? null
                ]);
            } else {
                ResponseHelper::error($resultado['erro'], 422);
            }
            
        } catch (Exception $e) {
            error_log("Erro GerarNFeController: " . $e->getMessage());
            ResponseHelper::error('Erro interno do servidor', 500);
        }
    }
    
    private function validarDados($dados)
    {
        $required = ['empresa', 'cliente', 'produtos', 'totais'];
        
        foreach ($required as $field) {
            if (!isset($dados[$field])) {
                ResponseHelper::error("Campo obrigatório: {$field}", 400);
                exit();
            }
        }
        
        // Validar empresa
        if (!isset($dados['empresa']['id'])) {
            ResponseHelper::error('ID da empresa é obrigatório', 400);
            exit();
        }
        
        // Validar produtos
        if (empty($dados['produtos']) || !is_array($dados['produtos'])) {
            ResponseHelper::error('Pelo menos um produto é obrigatório', 400);
            exit();
        }
    }
}
?>
