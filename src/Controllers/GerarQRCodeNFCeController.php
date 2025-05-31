<?php
namespace NexoNFe\Controllers;

require_once '../src/Services/NFCeService.php';
require_once '../src/Utils/ResponseHelper.php';

use NexoNFe\Services\NFCeService;
use NexoNFe\Utils\ResponseHelper;

class GerarQRCodeNFCeController
{
    private $nfceService;
    
    public function __construct()
    {
        $this->nfceService = new NFCeService();
    }
    
    public function handle()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['chave'])) {
                ResponseHelper::error('Chave é obrigatória', 400);
                return;
            }
            
            $qrCode = $this->nfceService->gerarQRCode(
                $input['chave'],
                $input['uf'] ?? 'SP',
                $input['valor_total'] ?? 0
            );
            
            ResponseHelper::success([
                'qr_code' => $qrCode,
                'chave' => $input['chave']
            ]);
            
        } catch (Exception $e) {
            error_log("Erro GerarQRCodeNFCeController: " . $e->getMessage());
            ResponseHelper::error('Erro interno do servidor', 500);
        }
    }
}
?>
