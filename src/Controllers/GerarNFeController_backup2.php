<?php

namespace NexoNFe\Controllers;

require_once '../src/Services/NFeService.php';
require_once '../src/Services/SupabaseService.php';
require_once '../src/Utils/LogHelper.php';

use NexoNFe\Services\NFeService;
use NexoNFe\Services\SupabaseService;
use NexoNFe\Utils\LogHelper;
use Exception;

/**
 * Controller para gerar NFe
 */
class GerarNFeController
{
    private $nfeService;
    private $supabaseService;
    
    public function __construct()
    {
        $this->nfeService = new NFeService();
        $this->supabaseService = new SupabaseService();
    }
    
    public function handle()
    {
        try {
            error_log("=== DEBUG GERAR NFE ===");
            
            // Receber dados do POST
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Dados JSON inválidos ou vazios');
            }
            
            error_log("Dados recebidos: " . json_encode($input));
            
            // Validar dados obrigatórios
            $this->validarDados($input);
            
            $empresa = $input['empresa'];
            $cliente = $input['cliente'];
            $produtos = $input['produtos'];
            $totais = $input['totais'];
            $pagamentos = $input['pagamentos'] ?? [];
            
            error_log("Empresa ID recebido: " . $empresa['id']);
            error_log("Certificado path: " . ($empresa['certificado_digital_path'] ?? 'NÃO INFORMADO'));
            
            // 1. Buscar certificado no Supabase
            error_log("Buscando certificado no Supabase...");
            $certificadoInfo = $this->supabaseService->buscarCertificadoEmpresa($empresa['id']);
            error_log("Certificado encontrado: " . ($certificadoInfo ? 'SIM' : 'NÃO'));
            
            if (!$certificadoInfo) {
                throw new Exception('Certificado digital não encontrado para esta empresa');
            }
            
            // 2. Baixar certificado temporariamente
            $certificadoTemp = $this->supabaseService->baixarCertificado($certificadoInfo['certificado_digital_path']);
            error_log("Certificado baixado temporariamente: " . $certificadoTemp);
            
            // 3. Gerar NFe
            error_log("Gerando XML da NFe...");
            $resultadoNFe = $this->nfeService->gerarNFe($empresa, $cliente, $produtos, $totais, $pagamentos);
            
            // 4. Limpar certificado temporário
            $this->supabaseService->deletarCertificadoTemporario($certificadoTemp);
            
            if (!$resultadoNFe['sucesso']) {
                throw new Exception('Erro ao gerar NFe: ' . $resultadoNFe['erro']);
            }
            
            error_log("XML gerado: " . ($resultadoNFe['xml'] ? 'SIM (' . strlen($resultadoNFe['xml']) . ' chars)' : 'NÃO'));
            error_log("Chave gerada: " . ($resultadoNFe['chave'] ? $resultadoNFe['chave'] : 'NÃO'));
            
            // 5. Preparar resposta
            $response = [
                'success' => true,
                'message' => 'NFe gerada com sucesso',
                'data' => [
                    'xml' => $resultadoNFe['xml'],
                    'chave' => $resultadoNFe['chave'],
                    'numero_nfe' => $resultadoNFe['numero_nfe'],
                    'codigo_nf' => substr($resultadoNFe['chave'], -8) // Últimos 8 dígitos da chave
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            error_log("Retornando: " . json_encode([
                'success' => $response['success'],
                'xml_length' => strlen($response['data']['xml']),
                'chave' => $response['data']['chave']
            ]));
            
            LogHelper::info('NFe gerada com sucesso via API', [
                'chave' => $resultadoNFe['chave'],
                'empresa_id' => $empresa['id']
            ]);
            
            header('Content-Type: application/json');
            echo json_encode($response, JSON_PRETTY_PRINT);
            
        } catch (Exception $e) {
            error_log("ERRO: " . $e->getMessage());
            
            LogHelper::error('Erro ao gerar NFe via API', [
                'erro' => $e->getMessage(),
                'empresa_id' => $input['empresa']['id'] ?? 'não informado'
            ]);
            
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    private function validarDados($input)
    {
        $camposObrigatorios = [
            'empresa' => 'Dados da empresa são obrigatórios',
            'cliente' => 'Dados do cliente são obrigatórios',
            'produtos' => 'Lista de produtos é obrigatória',
            'totais' => 'Totais da NFe são obrigatórios'
        ];
        
        foreach ($camposObrigatorios as $campo => $mensagem) {
            if (!isset($input[$campo]) || empty($input[$campo])) {
                throw new Exception($mensagem);
            }
        }
        
        // Validar empresa
        $empresa = $input['empresa'];
        if (empty($empresa['id'])) {
            throw new Exception('ID da empresa é obrigatório');
        }
        
        if (empty($empresa['cnpj'])) {
            throw new Exception('CNPJ da empresa é obrigatório');
        }
        
        // Validar cliente
        $cliente = $input['cliente'];
        if (empty($cliente['documento'])) {
            throw new Exception('CPF/CNPJ do cliente é obrigatório');
        }
        
        if (empty($cliente['name'])) {
            throw new Exception('Nome do cliente é obrigatório');
        }
        
        // Validar produtos
        if (!is_array($input['produtos']) || count($input['produtos']) === 0) {
            throw new Exception('Pelo menos um produto deve ser informado');
        }
        
        foreach ($input['produtos'] as $index => $produto) {
            if (empty($produto['descricao'])) {
                throw new Exception("Descrição do produto " . ($index + 1) . " é obrigatória");
            }
            
            if (!isset($produto['quantidade']) || $produto['quantidade'] <= 0) {
                throw new Exception("Quantidade do produto " . ($index + 1) . " deve ser maior que zero");
            }
            
            if (!isset($produto['valor_unitario']) || $produto['valor_unitario'] <= 0) {
                throw new Exception("Valor unitário do produto " . ($index + 1) . " deve ser maior que zero");
            }
        }
        
        // Validar totais
        $totais = $input['totais'];
        if (!isset($totais['valor_total']) || $totais['valor_total'] <= 0) {
            throw new Exception('Valor total da NFe deve ser maior que zero');
        }
    }
}
