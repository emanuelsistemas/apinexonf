<?php
namespace NexoNFe\Controllers;

require_once '../src/Services/NFCeService.php';
require_once '../src/Utils/ResponseHelper.php';

use NexoNFe\Services\NFCeService;
use NexoNFe\Utils\ResponseHelper;

class GerarNFCeController
{
    private $nfceService;
    
    public function __construct()
    {
        $this->nfceService = new NFCeService();
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
            
            // Gerar NFC-e
            $resultado = $this->nfceService->gerarNFCe(
                $input['empresa'],
                $input['consumidor'] ?? null,
                $input['produtos'],
                $input['totais'],
                $input['pagamentos']
            );
            
            if ($resultado['sucesso']) {
                ResponseHelper::success([
                    'xml' => $resultado['xml'],
                    'chave' => $resultado['chave'],
                    'numero_nfce' => $resultado['numero_nfce'],
                    'qr_code' => $resultado['qr_code'],
                    'url_consulta' => $resultado['url_consulta']
                ]);
            } else {
                ResponseHelper::error($resultado['erro'], 422);
            }
            
        } catch (Exception $e) {
            error_log("Erro GerarNFCeController: " . $e->getMessage());
            ResponseHelper::error('Erro interno do servidor', 500);
        }
    }
    
    private function validarDados($dados)
    {
        $required = ['empresa', 'produtos', 'totais', 'pagamentos'];
        
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
        
        // Validar pagamentos (obrigatório para NFC-e)
        if (empty($dados['pagamentos']) || !is_array($dados['pagamentos'])) {
            ResponseHelper::error('Formas de pagamento são obrigatórias para NFC-e', 400);
            exit();
        }
        
        // Validar valor máximo NFC-e
        if ($dados['totais']['valor_total'] > 5000.00) {
            ResponseHelper::error('Valor total não pode exceder R$ 5.000,00 para NFC-e', 400);
            exit();
        }
    }
}
?>
